<?php

namespace App\Services\Insights;

use App\Models\Lesson;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Lessons the learner keeps getting wrong (DECISIONS.md §28) — cross-course, so the
 * whole catalog gets scanned for recurring trouble spots, not just the current one.
 */
class WeakSpots
{
    public const MIN_INCORRECT = 2;

    /**
     * @return Collection<int, array{lesson: Lesson, incorrect: int, total: int}>
     */
    public function forUser(User $user, int $limit = 10): Collection
    {
        $rows = DB::table('attempts')
            ->join('activities', 'activities.id', '=', 'attempts.activity_id')
            ->where('attempts.user_id', $user->id)
            ->whereNotIn('attempts.result_status', ['started'])
            ->selectRaw('activities.lesson_id, count(*) as total, sum(case when attempts.result_status = ? then 1 else 0 end) as incorrect', ['incorrect'])
            ->groupBy('activities.lesson_id')
            ->having('incorrect', '>=', self::MIN_INCORRECT)
            ->orderByDesc('incorrect')
            ->limit($limit)
            ->get();

        $lessons = Lesson::with('course')->whereIn('id', $rows->pluck('lesson_id'))->get()->keyBy('id');

        return $rows->map(fn ($row) => [
            'lesson' => $lessons[$row->lesson_id],
            'incorrect' => (int) $row->incorrect,
            'total' => (int) $row->total,
        ]);
    }
}
