<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'description', 'order'])]
class Skill extends Model
{
    use HasFactory;

    public function learningItem(): BelongsTo
    {
        return $this->belongsTo(LearningItem::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class);
    }

    public function masteryRecords(): HasMany
    {
        return $this->hasMany(MasteryRecord::class);
    }

    public function prerequisites(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'skill_dependencies', 'skill_id', 'prerequisite_skill_id');
    }

    public function dependents(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'skill_dependencies', 'prerequisite_skill_id', 'skill_id');
    }
}
