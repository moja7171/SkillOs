<?php

namespace App\Services\Planning;

use App\Models\Activity;
use App\Models\Attempt;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\MasteryRecord;
use App\Models\PlanItem;
use App\Models\User;
use App\Services\Content\ReviewPracticeGenerator;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Today's plan, computed from current state (DECISIONS.md §2) and materialized as
 * plan_items for today only. Nothing about the future is stored; a gap in usage
 * changes nothing except which reviews are due.
 */
class Planner
{
    public const MAX_REVIEWS_PER_COURSE = 3;

    public const RECENT_FAILURES_FOR_RELEARN = 3;

    public const REACTIVATION_GAP_DAYS = 14;

    public function __construct(protected ReviewPracticeGenerator $reviewPracticeGenerator) {}

    /**
     * Today's plan items for the user, computing them for any scheduled enrollment
     * that has none yet (first visit of the day, or a course scheduled later today).
     *
     * @return Collection<int, PlanItem>
     */
    public function today(User $user): Collection
    {
        $enrollments = $user->enrollments()->with('course')->get();
        $existing = $this->todayItems($user);

        foreach ($enrollments as $enrollment) {
            if ($enrollment->isScheduled() && ! $existing->contains('enrollment_id', $enrollment->id)) {
                $this->materialize($user, $enrollment, $existing);
                $existing = $this->todayItems($user);
            }
        }

        return $existing;
    }

    /**
     * Rebuild today's open items for one enrollment after its config changed.
     * Completed items stay so the day's record is honest.
     */
    public function recompute(Enrollment $enrollment): void
    {
        DB::transaction(function () use ($enrollment) {
            $enrollment->planItems()->whereDate('scheduled_for', today())->where('status', 'scheduled')->delete();

            if ($enrollment->isScheduled()) {
                $this->materialize($enrollment->user, $enrollment, $this->todayItems($enrollment->user));
            }
        });
    }

    /**
     * Skip a planned activity for today. Nothing is rescheduled: tomorrow's plan is
     * computed fresh, and a skipped review is simply still due.
     */
    public function skip(PlanItem $item): void
    {
        if ($item->status === 'scheduled') {
            $item->update(['status' => 'skipped']);
        }
    }

    /**
     * Returning after a long gap: check retention before advancing (PRD §15).
     * Every lesson at Familiar or above gets a review due today.
     */
    public function reactivate(Enrollment $enrollment): int
    {
        $gap = $enrollment->last_activity_at;
        if ($gap === null || $gap->gt(now()->subDays(self::REACTIVATION_GAP_DAYS))) {
            return 0;
        }

        return MasteryRecord::where('user_id', $enrollment->user_id)
            ->whereIn('lesson_id', $enrollment->course->lessons()->select('id'))
            ->whereIn('level', ['familiar', 'proficient', 'mastered'])
            ->update(['next_review_due_at' => today()]);
    }

    /**
     * Read-only week: today's plan, then reviews known to come due on each of the next days.
     *
     * @return array<int, array{date: CarbonInterface, items: Collection<int, PlanItem>, reviews: Collection<int, MasteryRecord>}>
     */
    public function week(User $user, int $days = 7): array
    {
        $items = $this->today($user);
        $scheduledLessonIds = $user->enrollments()->get()->filter->isScheduled()
            ->flatMap(fn (Enrollment $e) => $e->course->lessons()->pluck('id'));

        $reviews = MasteryRecord::with('lesson.course')
            ->where('user_id', $user->id)
            ->whereIn('lesson_id', $scheduledLessonIds)
            ->whereNotNull('next_review_due_at')
            ->whereDate('next_review_due_at', '>', today())
            ->whereDate('next_review_due_at', '<', today()->addDays($days))
            ->orderBy('next_review_due_at')
            ->get()
            ->groupBy(fn (MasteryRecord $r) => $r->next_review_due_at->toDateString());

        $week = [];
        for ($i = 0; $i < $days; $i++) {
            $date = today()->addDays($i);
            $week[] = [
                'date' => $date,
                'items' => $i === 0 ? $this->orderForLearner($items) : collect(),
                'reviews' => $i === 0 ? collect() : ($reviews[$date->toDateString()] ?? collect()),
            ];
        }

        return $week;
    }

    /**
     * One recommendation plus up to two alternatives (PRD §14).
     *
     * @return array{primary: ?PlanItem, alternatives: Collection<int, PlanItem|Activity>, items: Collection<int, PlanItem>}
     */
    public function continueLearning(User $user): array
    {
        $items = $this->today($user);
        $open = $this->orderForLearner($items->where('status', 'scheduled'));

        $primary = $open->first();
        $alternatives = collect();

        if ($primary) {
            $second = $open->skip(1)->first();
            if ($second) {
                $alternatives->push($second);
            }

            // A free practice of the primary's lesson that isn't already planned.
            $plannedIds = $items->pluck('activity_id');
            $free = $primary->activity->lesson->practices()->whereNotIn('id', $plannedIds)->first();
            if ($free) {
                $alternatives->push($free);
            }
        }

        return ['primary' => $primary, 'alternatives' => $alternatives->take(2)->values(), 'items' => $items];
    }

    /**
     * Reviews first, then by course priority, then by planner order.
     *
     * @param  Collection<int, PlanItem>  $items
     * @return Collection<int, PlanItem>
     */
    public function orderForLearner(Collection $items): Collection
    {
        return $items->sortBy([
            fn (PlanItem $a, PlanItem $b) => ($a->source === 'review' ? 0 : 1) <=> ($b->source === 'review' ? 0 : 1),
            fn (PlanItem $a, PlanItem $b) => $a->enrollment->priority <=> $b->enrollment->priority,
            fn (PlanItem $a, PlanItem $b) => $a->id <=> $b->id,
        ])->values();
    }

    /**
     * @return Collection<int, PlanItem>
     */
    protected function todayItems(User $user): Collection
    {
        return PlanItem::with(['activity.lesson.course', 'enrollment'])
            ->where('user_id', $user->id)
            ->whereDate('scheduled_for', today())
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  Collection<int, PlanItem>  $existing
     */
    protected function materialize(User $user, Enrollment $enrollment, Collection $existing): void
    {
        $existingForCourse = $existing->where('enrollment_id', $enrollment->id);
        $alreadyPlanned = $existingForCourse->pluck('activity_id')->all();
        // Minutes already on today's plan (completed or still open) count against the budget.
        $budget = max(0, $enrollment->daily_time_minutes - $existingForCourse->sum('duration_minutes'));

        foreach ($this->candidatesFor($user, $enrollment, $budget, $alreadyPlanned) as $candidate) {
            PlanItem::create([
                'user_id' => $user->id,
                'enrollment_id' => $enrollment->id,
                'activity_id' => $candidate['activity']->id,
                'scheduled_for' => today(),
                'duration_minutes' => $candidate['activity']->estimated_minutes,
                'status' => 'scheduled',
                'source' => $candidate['source'],
                'reason' => $candidate['reason'],
            ]);
        }
    }

    /**
     * The pure part: which activities fill this course's daily budget today.
     *
     * @param  array<int, int>  $excludeActivityIds
     * @return array<int, array{activity: Activity, source: string, reason: string}>
     */
    public function candidatesFor(User $user, Enrollment $enrollment, int $budget, array $excludeActivityIds = []): array
    {
        $lessons = $enrollment->course->lessons()
            ->with(['prerequisites', 'activities'])
            ->get();

        $mastery = MasteryRecord::where('user_id', $user->id)->whereIn('lesson_id', $lessons->pluck('id'))->get()->keyBy('lesson_id');
        $levelIndex = fn (Lesson $l) => MasteryRecord::LEVEL_INDEX[$mastery[$l->id]->level ?? 'not_started'];

        $candidates = [];
        $lessonsCovered = [];

        // 1. Due reviews, oldest first, capped so a long absence never floods the day.
        $due = $lessons
            ->filter(fn (Lesson $l) => isset($mastery[$l->id]) && $mastery[$l->id]->next_review_due_at?->lte(now()))
            ->sortBy(fn (Lesson $l) => $mastery[$l->id]->next_review_due_at)
            ->take(self::MAX_REVIEWS_PER_COURSE);

        foreach ($due as $lesson) {
            if ($practice = $this->pickReviewPractice($user, $lesson)) {
                $candidates[] = ['activity' => $practice, 'source' => 'review', 'reason' => 'مرور سررسید'];
                $lessonsCovered[] = $lesson->id;
            }
        }

        // Maintenance mode: keep what was learned, never advance.
        if ($enrollment->isReviewsOnly()) {
            return $this->fillBudget($candidates, $budget, $excludeActivityIds);
        }

        // 2. The current lesson: first below proficient whose prerequisites are at least familiar.
        $current = $lessons->first(fn (Lesson $l) => $levelIndex($l) < 3
            && $l->prerequisites->every(fn (Lesson $p) => $levelIndex($p) >= 2));

        if ($current && ! in_array($current->id, $lessonsCovered, true)) {
            $learn = $current->activities->firstWhere('type', 'learn');
            $recent = Attempt::where('user_id', $user->id)
                ->whereIn('activity_id', $current->activities->where('type', 'practice')->pluck('id'))
                ->whereNot('result_status', 'started')
                ->latest('id')->limit(self::RECENT_FAILURES_FOR_RELEARN)->pluck('result_status');

            $learned = $learn && Attempt::where('user_id', $user->id)->where('activity_id', $learn->id)->where('result_status', 'completed')->exists();
            $isVideoDay = $enrollment->isVideoDay();

            if ($isVideoDay && $learn && $recent->count() === self::RECENT_FAILURES_FOR_RELEARN && $recent->every(fn ($s) => $s === 'incorrect')) {
                $candidates[] = ['activity' => $learn, 'source' => 'plan', 'reason' => 'یادگیری دوباره بعد از سه اشتباه'];
            } elseif ($isVideoDay && $learn && ! $learned) {
                $candidates[] = ['activity' => $learn, 'source' => 'plan', 'reason' => 'شروع درس'];
            }

            // A lesson not yet learned has nothing to practice on a non-video day; wait for it.
            if (($learned || $isVideoDay) && $practice = $this->pickPractice($user, $current)) {
                $candidates[] = ['activity' => $practice, 'source' => 'plan', 'reason' => 'تمرین درس فعلی'];
            }
        }

        // 3. Nothing due and nothing to advance: keep the weakest lesson sharp.
        if ($candidates === []) {
            $weakest = $lessons->sortBy(fn (Lesson $l) => $mastery[$l->id]->numeric_mastery ?? 0)->first();
            if ($weakest && ($practice = $this->pickPractice($user, $weakest))) {
                $candidates[] = ['activity' => $practice, 'source' => 'plan', 'reason' => 'حفظ آمادگی'];
            }
        }

        return $this->fillBudget($candidates, $budget, $excludeActivityIds);
    }

    /**
     * Fill the budget in order; never split an activity; allow one generous overflow.
     *
     * @param  array<int, array{activity: Activity, source: string, reason: string}>  $candidates
     * @param  array<int, int>  $excludeActivityIds
     * @return array<int, array{activity: Activity, source: string, reason: string}>
     */
    protected function fillBudget(array $candidates, int $budget, array $excludeActivityIds): array
    {
        $chosen = [];
        $seen = $excludeActivityIds;
        foreach ($candidates as $candidate) {
            $activity = $candidate['activity'];
            if (in_array($activity->id, $seen, true)) {
                continue;
            }
            $minutes = $activity->estimated_minutes;
            if ($minutes <= $budget || $budget >= $minutes / 2) {
                $chosen[] = $candidate;
                $seen[] = $activity->id;
                $budget -= $minutes;
            }
            if ($budget <= 0) {
                break;
            }
        }

        return $chosen;
    }

    /**
     * A review practice for this lesson. Once the learner has attempted every practice
     * in the pool at least once, a new one is generated and added to it for good
     * (DECISIONS.md §18), so repeat reviews don't just cycle the same authored 2-3
     * questions; a generation failure (no API key, network) is swallowed and the
     * learner still gets the normal rotation.
     */
    protected function pickReviewPractice(User $user, Lesson $lesson): ?Activity
    {
        $practices = $lesson->activities->where('type', 'practice');

        if ($practices->isNotEmpty() && $this->allAttempted($user, $practices)) {
            try {
                $generated = $this->reviewPracticeGenerator->generate($lesson);
                $lesson->setRelation('activities', $lesson->activities->push($generated));
            } catch (Throwable $e) {
                report($e);
            }
        }

        return $this->pickPractice($user, $lesson);
    }

    /**
     * @param  Collection<int, Activity>  $practices
     */
    protected function allAttempted(User $user, Collection $practices): bool
    {
        $attempted = Attempt::where('user_id', $user->id)
            ->whereIn('activity_id', $practices->pluck('id'))
            ->distinct()->pluck('activity_id');

        return $practices->pluck('id')->diff($attempted)->isEmpty();
    }

    /**
     * The practice the learner has attempted least recently (unattempted first).
     */
    protected function pickPractice(User $user, Lesson $lesson): ?Activity
    {
        $practices = $lesson->activities->where('type', 'practice')->sortBy('id')->values();
        if ($practices->isEmpty()) {
            return null;
        }

        $lastAttempt = Attempt::where('user_id', $user->id)
            ->whereIn('activity_id', $practices->pluck('id'))
            ->selectRaw('activity_id, max(id) as last_id')
            ->groupBy('activity_id')
            ->pluck('last_id', 'activity_id');

        return $practices->sortBy(fn (Activity $p) => $lastAttempt[$p->id] ?? 0)->first();
    }
}
