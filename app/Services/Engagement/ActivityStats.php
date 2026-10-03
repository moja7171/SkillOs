<?php

namespace App\Services\Engagement;

use App\Models\Activity;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Cross-course activity rollups for Home's weekly/monthly recap (DECISIONS.md §22) and
 * the admin-only «دوستان» progress view (DECISIONS.md §44).
 */
class ActivityStats
{
    /**
     * @return array{count: int, minutes: int}
     */
    public function since(User $user, Carbon $since): array
    {
        $row = $user->attempts()
            ->join('activities', 'activities.id', '=', 'attempts.activity_id')
            ->whereNotNull('attempts.completed_at')
            ->where('attempts.completed_at', '>=', $since)
            ->selectRaw('count(*) as count, coalesce(sum(activities.estimated_minutes), 0) as minutes')
            ->first();

        return ['count' => (int) $row->count, 'minutes' => (int) $row->minutes];
    }

    /**
     * Same shape as since(), with no date floor — every finalized attempt ever.
     *
     * @return array{count: int, minutes: int}
     */
    public function allTime(User $user): array
    {
        $row = $user->attempts()
            ->join('activities', 'activities.id', '=', 'attempts.activity_id')
            ->whereNotNull('attempts.completed_at')
            ->selectRaw('count(*) as count, coalesce(sum(activities.estimated_minutes), 0) as minutes')
            ->first();

        return ['count' => (int) $row->count, 'minutes' => (int) $row->minutes];
    }

    /**
     * Distinct lessons this learner has finished — the lesson item only (DECISIONS.md §65):
     * every video watched, or the explicit mark for a lesson without videos. Practices and
     * mastery level are separate signals.
     */
    public function lessonsDoneCount(User $user): int
    {
        return Activity::where('type', 'learn')
            ->whereHas('attempts', fn ($q) => $q->where('user_id', $user->id)->where('evidence->source', 'lesson_done'))
            ->distinct('lesson_id')
            ->count('lesson_id');
    }

    /**
     * Distinct practice activities this learner has ever answered correctly
     * (with or without a hint) — not a count of attempts, so repeated reviews
     * of the same practice don't inflate the number.
     */
    public function practicesSolvedCount(User $user): int
    {
        return Activity::where('type', 'practice')
            ->whereHas('attempts', fn ($q) => $q->where('user_id', $user->id)->whereIn('result_status', ['correct', 'correct_with_hint']))
            ->count();
    }

    /**
     * Lessons the learner has finished (lesson item) in a course they're enrolled in whose
     * practices aren't all passed yet — the «تمرین‌های عقب‌افتاده» queue (DECISIONS.md §65).
     * Ordered by course then lesson order; `$limit` caps the rows shown, `total` is the full count.
     *
     * @return array{items: Collection<int, array{lesson: Lesson, passed: int, total: int}>, total: int}
     */
    public function practiceBacklog(User $user, int $limit = 5): array
    {
        $enrolledCourseIds = $user->enrollments()->pluck('course_id');

        $doneLessonIds = Activity::where('type', 'learn')
            ->whereHas('lesson', fn ($q) => $q->whereIn('course_id', $enrolledCourseIds))
            ->whereHas('attempts', fn ($q) => $q->where('user_id', $user->id)->where('evidence->source', 'lesson_done'))
            ->pluck('lesson_id');

        $practices = Activity::where('type', 'practice')->whereIn('lesson_id', $doneLessonIds)->get(['id', 'lesson_id']);
        $passedIds = $user->attempts()
            ->whereIn('activity_id', $practices->pluck('id'))
            ->whereIn('result_status', ['correct', 'correct_with_hint'])
            ->pluck('activity_id')
            ->unique();

        $pending = $practices->groupBy('lesson_id')
            ->map(fn (Collection $group) => ['passed' => $group->pluck('id')->intersect($passedIds)->count(), 'total' => $group->count()])
            ->filter(fn (array $progress) => $progress['passed'] < $progress['total']);

        $lessons = Lesson::with('course')->whereIn('id', $pending->keys())->orderBy('course_id')->orderBy('order')->get();

        return [
            'items' => $lessons->take($limit)->map(fn (Lesson $lesson) => ['lesson' => $lesson] + $pending[$lesson->id])->values(),
            'total' => $lessons->count(),
        ];
    }
}
