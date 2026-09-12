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

        return view('courses.show', compact('course', 'enrollment'));
    }
}
