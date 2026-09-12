<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['scheduled_for', 'duration_minutes', 'status', 'source', 'reason'])]
class PlanItem extends Model
{
    protected function casts(): array
    {
        return [
            'scheduled_for' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function learningItem(): BelongsTo
    {
        return $this->belongsTo(LearningItem::class);
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }
}
