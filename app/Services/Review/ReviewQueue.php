<?php

namespace App\Services\Review;

use App\Models\Activity;
use App\Models\Attempt;
use App\Models\Lesson;
use App\Models\MasteryRecord;
use App\Models\User;
use App\Services\Content\ReviewPracticeGenerator;
use App\Services\Evaluation\AttemptSession;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Today's reviews as their own queue (DECISIONS.md §70): lessons whose spaced review is
 * due, oldest first, at most DAILY_LIMIT a day. Each review is one 4-option question the
 * learner has never answered — an authored one if any is left, else a freshly generated one.
 */
class ReviewQueue
{
    public const DAILY_LIMIT = 10;

    public function __construct(protected AttemptSession $sessions, protected ReviewPracticeGenerator $generator) {}

    /**
     * Due lessons still within today's allowance, in review order.
     *
     * @return Collection<int, MasteryRecord>
     */
    public function due(User $user): Collection
    {
        $remaining = max(0, self::DAILY_LIMIT - $this->doneToday($user));
        if ($remaining === 0) {
            return collect();
        }

        return MasteryRecord::with('lesson.course')
            ->where('user_id', $user->id)
            ->whereHas('lesson', fn ($q) => $q->whereIn('course_id', $this->activeCourseIds($user)))
            ->where('next_review_due_at', '<=', now())
            ->orderBy('next_review_due_at')
            ->limit($remaining)
            ->get();
    }

    public function doneToday(User $user): int
    {
        return $user->attempts()
            ->where('evidence->source', 'review')
            ->whereNotIn('result_status', ['started', 'abandoned'])
            ->whereDate('completed_at', today())
            ->count();
    }

    public function dueTomorrow(User $user): int
    {
        return MasteryRecord::where('user_id', $user->id)
            ->whereHas('lesson', fn ($q) => $q->whereIn('course_id', $this->activeCourseIds($user)))
            ->whereDate('next_review_due_at', today()->addDay())
            ->count();
    }

    /**
     * Opens the next review: resumes an open one, else the first due lesson that yields a
     * question. Null when nothing is due or no lesson could produce a question right now.
     */
    public function start(User $user): ?Attempt
    {
        $open = $user->attempts()
            ->where('result_status', 'started')
            ->where('evidence->source', 'review')
            ->latest('id')
            ->first();
        if ($open) {
            return $open;
        }

        foreach ($this->due($user) as $record) {
            if ($question = $this->questionFor($user, $record->lesson)) {
                return $this->sessions->start($user, $question, 'review');
            }
        }

        return null;
    }

    /**
     * A 4-option question of this lesson the learner has not answered yet: authored first,
     * then one generated earlier, else a new one (null if generation fails — the lesson
     * simply stays due).
     */
    public function questionFor(User $user, Lesson $lesson): ?Activity
    {
        $questions = $lesson->activities()->where('type', 'practice')->where('payload->form', 'mcq')->get();

        $answered = Attempt::where('user_id', $user->id)
            ->whereIn('activity_id', $questions->pluck('id'))
            ->whereNotIn('result_status', ['started', 'abandoned'])
            ->pluck('activity_id');

        $fresh = $questions->reject(fn (Activity $a) => $answered->contains($a->id) || ! empty($a->payload['retired']))
            ->sortBy([['generated', 'asc'], ['id', 'asc']])
            ->first();
        if ($fresh) {
            return $fresh;
        }

        try {
            return $this->generator->generate($lesson);
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    /**
     * @return Collection<int, int>
     */
    protected function activeCourseIds(User $user): Collection
    {
        return $user->enrollments()->whereIn('status', ['active', 'maintenance'])->pluck('course_id');
    }
}
