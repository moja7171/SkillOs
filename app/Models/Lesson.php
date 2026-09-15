<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['course_id', 'slug', 'order', 'title', 'section', 'summary', 'content', 'key_points', 'common_mistakes', 'attachments', 'estimated_minutes'])]
class Lesson extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'key_points' => 'array',
            'common_mistakes' => 'array',
            'attachments' => 'array',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function videos(): HasMany
    {
        return $this->hasMany(LessonVideo::class)->orderBy('order');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function learnActivity(): HasOne
    {
        return $this->hasOne(Activity::class)->where('type', 'learn');
    }

    public function practices(): HasMany
    {
        return $this->hasMany(Activity::class)->where('type', 'practice')->orderBy('id');
    }

    public function masteryRecords(): HasMany
    {
        return $this->hasMany(MasteryRecord::class);
    }

    public function prerequisites(): BelongsToMany
    {
        return $this->belongsToMany(Lesson::class, 'lesson_prerequisites', 'lesson_id', 'prerequisite_lesson_id')->orderBy('order');
    }

    public function dependents(): BelongsToMany
    {
        return $this->belongsToMany(Lesson::class, 'lesson_prerequisites', 'prerequisite_lesson_id', 'lesson_id');
    }

    /**
     * Lesson pages live under their course: /courses/{course}/lessons/{lesson}.
     */
    public function url(): string
    {
        return route('lessons.show', [$this->course, $this]);
    }

    /**
     * The learner-facing level for one user. Loads mastery lazily if not eager-loaded.
     */
    public function levelFor(User $user): string
    {
        $record = $this->relationLoaded('masteryRecords')
            ? $this->masteryRecords->firstWhere('user_id', $user->id)
            : $this->masteryRecords()->where('user_id', $user->id)->first();

        return $record?->level ?? 'not_started';
    }

    /**
     * Gate for the "انجام دادم" button: every practice has at least one correct
     * (or correct-with-hint) attempt from this user. A lesson with no practices has
     * nothing to gate on.
     */
    public function allPracticesPassedBy(User $user): bool
    {
        $practices = $this->relationLoaded('practices') ? $this->practices : $this->practices()->get();
        if ($practices->isEmpty()) {
            return true;
        }

        $passedActivityIds = Attempt::where('user_id', $user->id)
            ->whereIn('activity_id', $practices->pluck('id'))
            ->whereIn('result_status', ['correct', 'correct_with_hint'])
            ->pluck('activity_id')
            ->unique();

        return $practices->pluck('id')->diff($passedActivityIds)->isEmpty();
    }

    /**
     * Marked done via the explicit "انجام دادم" action — sticky, independent of any
     * later dip in numeric mastery from a failed review.
     */
    public function isMarkedDoneBy(User $user): bool
    {
        $learnActivityId = $this->relationLoaded('learnActivity') ? $this->learnActivity?->id : $this->learnActivity()->value('id');
        if (! $learnActivityId) {
            return false;
        }

        return Attempt::where('user_id', $user->id)
            ->where('activity_id', $learnActivityId)
            ->where('evidence->source', 'lesson_done')
            ->exists();
    }
}
