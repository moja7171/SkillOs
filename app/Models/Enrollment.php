<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'course_id', 'status', 'priority', 'daily_time_minutes', 'preferred_time', 'last_activity_at'])]
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

    protected function casts(): array
    {
        return [
            'last_activity_at' => 'datetime',
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
}
