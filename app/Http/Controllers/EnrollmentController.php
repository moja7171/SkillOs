<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    public function store(Request $request, Course $course): RedirectResponse
    {
        $enrollment = Enrollment::firstOrCreate(
            ['user_id' => $request->user()->id, 'course_id' => $course->id],
            ['status' => 'active', 'priority' => 3],
        );

        if ($enrollment->wasRecentlyCreated) {
            return redirect()->route('courses.learn', $course)->with('status', 'به دوره‌های تو اضافه شد.');
        }

        return redirect()->route('courses.show', $course)->with('status', 'قبلاً این دوره رو برداشتی.');
    }

    /**
     * Drop the course from the learner's list. Only the enrollment goes: mastery records and
     * attempts stay, so taking the course again resumes where they stopped.
     */
    public function destroy(Request $request, Course $course): RedirectResponse
    {
        $request->user()->enrollments()->where('course_id', $course->id)->delete();

        return redirect()->route('courses.show', $course)->with('status', 'دوره از لیست دوره‌های تو برداشته شد. پیشرفتت نگه داشته شده.');
    }
}
