<?php

namespace App\Services\Engagement;

use App\Models\Activity;
use App\Models\MasteryRecord;
use App\Models\User;
use Illuminate\Support\Carbon;

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
     * Distinct lessons this learner has finished, by either signal (DECISIONS.md §43):
     * real mastery at "آشنا" or above, or the explicit "انجام دادم" mark.
     */
    public function lessonsDoneCount(User $user): int
    {
        $viaMastery = MasteryRecord::where('user_id', $user->id)
            ->whereIn('level', ['familiar', 'proficient', 'mastered'])
            ->pluck('lesson_id');

        $viaMarkDone = Activity::where('type', 'learn')
            ->whereHas('attempts', fn ($q) => $q->where('user_id', $user->id)->where('evidence->source', 'lesson_done'))
            ->pluck('lesson_id');

        return $viaMastery->merge($viaMarkDone)->unique()->count();
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
}
