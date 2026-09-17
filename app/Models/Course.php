<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
}
