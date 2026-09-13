<?php

namespace App\Services\Mastery;

use App\Models\Attempt;
use App\Models\MasteryRecord;
use Illuminate\Support\Facades\DB;

/**
 * The only writer of mastery_records.numeric_mastery (DESIGN.md §1, §5).
 * Numbers come from DECISIONS.md §3 and are expected to be tuned after real use.
 */
class MasteryService
{
    public const MAX = 1000;

    /** Delta per evidence type: [practice, review]. */
    public const DELTAS = [
        'correct' => [150, 120],
        'correct_with_hint' => [70, 50],
        'partial' => [30, 30],
        'incorrect' => [-100, -150],
        'completed' => [0, 0],
        'abandoned' => [0, 0],
    ];

    /** Lower bound of each level on the 0–1000 scale, highest first. */
    public const THRESHOLDS = [
        'mastered' => 800,
        'proficient' => 500,
        'familiar' => 250,
        'learning' => 0,
    ];

    /** Days until the next spaced review, by level. */
    public const REVIEW_INTERVAL_DAYS = [
        'learning' => 1,
        'familiar' => 3,
        'proficient' => 7,
        'mastered' => 21,
    ];

    /**
     * Apply one finalized attempt to the learner's record for the attempt's lesson.
     *
     * @return array{old_level: string, new_level: string, delta: int}
     */
    public function applyAttempt(Attempt $attempt): array
    {
        if ($attempt->result_status === 'started') {
            throw new \LogicException('Only finalized attempts carry evidence.');
        }

        return DB::transaction(function () use ($attempt) {
            $record = MasteryRecord::firstOrCreate(
                ['user_id' => $attempt->user_id, 'lesson_id' => $attempt->activity->lesson_id],
                ['numeric_mastery' => 0, 'level' => 'not_started'],
            );

            $oldLevel = $record->level;
            $delta = $this->deltaFor($attempt);
            $numeric = max(0, min(self::MAX, $record->numeric_mastery + $delta));
            $level = self::levelFor($numeric);

            $record->fill([
                'numeric_mastery' => $numeric,
                'level' => $level,
                'last_evaluated_at' => now(),
                'next_review_due_at' => now()->addDays(self::REVIEW_INTERVAL_DAYS[$level])->startOfDay(),
            ])->save();

            return ['old_level' => $oldLevel, 'new_level' => $level, 'delta' => $delta];
        });
    }

    public static function levelFor(int $numeric): string
    {
        foreach (self::THRESHOLDS as $level => $min) {
            if ($numeric >= $min) {
                return $level;
            }
        }

        return 'learning';
    }

    protected function deltaFor(Attempt $attempt): int
    {
        $evidence = $attempt->evidence ?? [];
        $isReview = ($evidence['source'] ?? 'free') === 'review';

        // A learner who gave up after a partial verdict still showed partial understanding.
        $kind = $attempt->result_status === 'incorrect' && ($evidence['verdict'] ?? null) === 'partial'
            ? 'partial'
            : $attempt->result_status;

        return self::DELTAS[$kind][$isReview ? 1 : 0] ?? 0;
    }
}
