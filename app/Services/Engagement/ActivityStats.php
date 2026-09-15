<?php

namespace App\Services\Engagement;

use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Cross-course activity rollups for Home's weekly/monthly recap (DECISIONS.md §22).
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
}
