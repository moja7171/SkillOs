<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['numeric_mastery', 'level', 'last_evaluated_at', 'next_review_due_at'])]
class MasteryRecord extends Model
{
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
