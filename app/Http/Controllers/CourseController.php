<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(Request $request): View
    {
        $courses = Course::withCount('lessons')
            ->withSum('lessons as lessons_minutes_sum', 'estimated_minutes')
            ->with(['enrollments' => fn ($q) => $q->where('user_id', $request->user()->id)])
            ->orderByRaw('category_order is null')
            ->orderBy('category_order')
            ->orderBy('title')
            ->get();

        // "دوره‌های من" (nav submenu) reuses this same page/view, just pre-filtered to
        // courses the user already enrolled in — no separate controller/route needed.
        $mine = $request->boolean('mine');
        if ($mine) {
            $courses = $courses->filter(fn ($c) => $c->enrollments->isNotEmpty())->values();
        }

        // Display-only grouping (DECISIONS.md §58) — just makes the catalog page easier to
        // scan, no effect on enrollment/planner/streak. Category section order is fixed here;
        // uncategorized courses (a local fixture, say) fall into a trailing group with no label.
        $categoryOrder = array_flip(Course::CATEGORIES);
        $coursesByCategory = $courses->groupBy(fn ($c) => $c->category ?? '')
            ->sortBy(fn ($group, $category) => $categoryOrder[$category] ?? count($categoryOrder));

        // Clicking a category in the nav's courses menu (or a header on this page) can
        // pre-filter which section is shown; validated against the known list so a stale/
        // bogus query value just falls back to "all" instead of silently matching nothing.
        $activeCategory = in_array($request->query('category'), Course::CATEGORIES, true)
            ? $request->query('category')
            : 'all';

        return view('courses.index', compact('courses', 'coursesByCategory', 'activeCategory', 'mine'));
    }

    public function show(Request $request, Course $course): View
    {
        $user = $request->user();

        $course->load([
            'lessons.prerequisites.masteryRecords' => fn ($q) => $q->where('user_id', $user->id),
            'lessons.masteryRecords' => fn ($q) => $q->where('user_id', $user->id),
            'lessons.videos',
        ])->loadCount('lessons');

        $enrollment = $course->enrollmentFor($user);

        // Lesson item and practice item are independent (DECISIONS.md §65).
        $doneLessonIds = $course->doneLessonIdsFor($user);
        $practiceProgress = $course->practiceProgressFor($user);

        return view('courses.show', compact('course', 'enrollment', 'doneLessonIds', 'practiceProgress'));
    }
}
