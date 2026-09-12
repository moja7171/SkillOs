<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['learning_item_id', 'skill_id', 'type', 'title', 'estimated_minutes', 'payload'])]
class Activity extends Model
{
    /** Practice forms the AI may choose (payload.form) and their learner-facing labels. */
    public const FORM_LABELS = [
        'mcq' => 'چندگزینه‌ای',
        'short_answer' => 'پاسخ کوتاه',
        'coding' => 'کدنویسی',
        'explanation' => 'توضیح',
        'scenario' => 'سناریو',
    ];

    /** payload.difficulty labels. */
    public const DIFFICULTY_LABELS = [
        'intro' => 'مقدماتی',
        'core' => 'اصلی',
        'stretch' => 'پیشرفته',
    ];

    public function isLearn(): bool
    {
        return $this->type === 'learn';
    }

    public function formLabel(): string
    {
        return self::FORM_LABELS[$this->payload['form'] ?? ''] ?? '';
    }

    public function difficultyLabel(): string
    {
        return self::DIFFICULTY_LABELS[$this->payload['difficulty'] ?? ''] ?? '';
    }

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    public function learningItem(): BelongsTo
    {
        return $this->belongsTo(LearningItem::class);
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(Attempt::class);
    }

    public function planItems(): HasMany
    {
        return $this->hasMany(PlanItem::class);
    }
}
