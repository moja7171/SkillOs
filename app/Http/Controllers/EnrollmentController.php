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
}
