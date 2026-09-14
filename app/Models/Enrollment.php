<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'course_id', 'status', 'priority', 'daily_time_minutes', 'preferred_time', 'last_activity_at', 'video_days'])]
class Enrollment extends Model
{
    use HasFactory;

    /** @var array<string, string> */
    public const STATUS_LABELS = [
        'active' => 'فعال',
        'paused' => 'متوقف',
        'archived' => 'بایگانی',
        'maintenance' => 'نگه‌داری',
    ];

    /** Carbon's dayOfWeek (0=Sunday..6=Saturday) → Persian label, in Iran's week order. */
    public const WEEKDAY_LABELS = [
        6 => 'شنبه',
        0 => 'یکشنبه',
        1 => 'دوشنبه',
        2 => 'سه‌شنبه',
        3 => 'چهارشنبه',
        4 => 'پنجشنبه',
        5 => 'جمعه',
    ];

    protected function casts(): array
    {
        return [
            'last_activity_at' => 'datetime',
            'video_days' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function planItems(): HasMany
    {
        return $this->hasMany(PlanItem::class);
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    /**
     * Whether the planner should put anything on today's plan for this course.
     * Maintenance is scheduled but limited to due reviews (see Planner::candidatesFor).
     */
    public function isScheduled(): bool
    {
        return in_array($this->status, ['active', 'maintenance'], true) && $this->daily_time_minutes > 0;
    }

    public function isReviewsOnly(): bool
    {
        return $this->status === 'maintenance';
    }

    /**
     * Whether the planner may put a new lesson (video) on today's plan. Empty/null means
     * every day is a video day; reviews and practice of already-learned lessons are never
     * gated by this (DECISIONS.md §19).
     */
    public function isVideoDay(): bool
    {
        return empty($this->video_days) || in_array(now()->dayOfWeek, $this->video_days, true);
    }
}
