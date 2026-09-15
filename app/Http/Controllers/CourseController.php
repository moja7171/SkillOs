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
            ->with(['enrollments' => fn ($q) => $q->where('user_id', $request->user()->id)])
            ->orderBy('title')
            ->get();

        return view('courses.index', compact('courses'));
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

        return view('courses.show', compact('course', 'enrollment', 'seenLessonIds'));
    }
}
