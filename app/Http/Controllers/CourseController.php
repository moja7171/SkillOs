<?php

namespace App\Http\Controllers;

use App\Models\Activity;
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

        // Display-only grouping (DECISIONS.md §58) — just makes the catalog page easier to
        // scan, no effect on enrollment/planner/streak. Category section order is fixed here;
        // uncategorized courses (a local fixture, say) fall into a trailing group with no label.
        $categoryOrder = array_flip(['مسیر رهبری فنی', 'اسکرام و اجایل', 'برنامه‌نویسی']);
        $coursesByCategory = $courses->groupBy(fn ($c) => $c->category ?? '')
            ->sortBy(fn ($group, $category) => $categoryOrder[$category] ?? count($categoryOrder));

        return view('courses.index', compact('courses', 'coursesByCategory'));
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

        // "Seen" (watched/read) is distinct from "level" — a lesson can sit at "learning"
        // either because it's been watched but not yet practiced, or practiced but not
        // yet mastered; the course table marks the two differently (DECISIONS §28).
        $seenLessonIds = Activity::where('type', 'learn')
            ->whereIn('lesson_id', $course->lessons->pluck('id'))
            ->whereHas('attempts', fn ($q) => $q->where('user_id', $user->id)->where('result_status', 'completed'))
            ->pluck('lesson_id');

        // The explicit "انجام دادم" mark — sticky, independent of mastery level (DECISIONS.md).
        $doneLessonIds = Activity::where('type', 'learn')
            ->whereIn('lesson_id', $course->lessons->pluck('id'))
            ->whereHas('attempts', fn ($q) => $q->where('user_id', $user->id)->where('evidence->source', 'lesson_done'))
            ->pluck('lesson_id');

        return view('courses.show', compact('course', 'enrollment', 'seenLessonIds', 'doneLessonIds'));
    }
}
