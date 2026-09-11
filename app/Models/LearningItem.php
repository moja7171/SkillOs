<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['title', 'outcome_statement', 'design_status', 'design_draft', 'design_approved_at', 'status', 'priority', 'daily_time_minutes', 'preferred_time', 'last_activity_at'])]
class LearningItem extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'design_draft' => 'array',
            'design_approved_at' => 'datetime',
            'last_activity_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function skills(): HasMany
    {
        return $this->hasMany(Skill::class)->orderBy('order');
    }

    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function planItems(): HasMany
    {
        return $this->hasMany(PlanItem::class);
    }

    public function isDesignApproved(): bool
    {
        return $this->design_status === 'approved';
    }
}
