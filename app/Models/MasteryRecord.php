<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['numeric_mastery', 'level', 'last_evaluated_at', 'next_review_due_at'])]
class MasteryRecord extends Model
{
    /** Learner-facing level labels (Persian). The numeric value is never shown. */
    public const LEVEL_LABELS = [
        'not_started' => 'شروع‌نشده',
        'learning' => 'در حال یادگیری',
        'familiar' => 'آشنا',
        'proficient' => 'ماهر',
        'mastered' => 'مسلط',
    ];

    /** Level -> badge/levelbar token index (l0..l4). */
    public const LEVEL_INDEX = [
        'not_started' => 0,
        'learning' => 1,
        'familiar' => 2,
        'proficient' => 3,
        'mastered' => 4,
    ];

    protected function casts(): array
    {
        return [
            'last_evaluated_at' => 'datetime',
            'next_review_due_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }
}
