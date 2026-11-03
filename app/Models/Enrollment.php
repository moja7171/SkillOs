<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }
}
