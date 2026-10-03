<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

#[Fillable(['slug', 'title', 'description', 'outcome_statement', 'source_note', 'category', 'category_order'])]
class Course extends Model
{
    use HasFactory;

    // Display-only catalog grouping (DECISIONS.md §58) — fixed section order for the
    // catalog page and the nav's courses menu. Not a DB table; just a few known labels.
    public const CATEGORIES = ['مسیر رهبری فنی', 'اسکرام و اجایل', 'برنامه‌نویسی'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('order')->chaperone();
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function enrollmentFor(User $user): ?Enrollment
    {
        return $this->enrollments->firstWhere('user_id', $user->id)
            ?? $this->enrollments()->where('user_id', $user->id)->first();
    }

    /**
     * Ids of this course's lessons whose lesson item is done for the learner (DECISIONS.md §65)
     * — batched for a whole curriculum so lists don't N+1 over Lesson::isMarkedDoneBy().
     *
     * @return Collection<int, int>
     */
    public function doneLessonIdsFor(User $user): Collection
    {
        return Activity::where('type', 'learn')
            ->whereIn('lesson_id', $this->lessons()->pluck('id'))
            ->whereHas('attempts', fn ($q) => $q->where('user_id', $user->id)->where('evidence->source', 'lesson_done'))
            ->pluck('lesson_id');
    }

    /**
     * Practice-item progress per lesson, keyed by lesson id; lessons without practices are
     * absent (they have no practice item). Same pass rule as Lesson::practiceProgressFor().
     *
     * @return Collection<int, array{passed: int, total: int}>
     */
    public function practiceProgressFor(User $user): Collection
    {
        $practices = Activity::where('type', 'practice')
            ->whereIn('lesson_id', $this->lessons()->pluck('id'))
            ->get(['id', 'lesson_id']);

        $passedIds = Attempt::where('user_id', $user->id)
            ->whereIn('activity_id', $practices->pluck('id'))
            ->whereIn('result_status', ['correct', 'correct_with_hint'])
            ->pluck('activity_id')
            ->unique();

        return $practices->groupBy('lesson_id')->map(fn (Collection $group) => [
            'passed' => $group->pluck('id')->intersect($passedIds)->count(),
            'total' => $group->count(),
        ]);
    }
}
