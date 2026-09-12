<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EnrollmentController extends Controller
{
    public function store(Request $request, Course $course): RedirectResponse
    {
        $enrollment = Enrollment::firstOrCreate(
            ['user_id' => $request->user()->id, 'course_id' => $course->id],
            ['status' => 'active', 'priority' => 3],
        );

        return redirect()->route('courses.show', $course)
            ->with('status', $enrollment->wasRecentlyCreated ? 'به دوره‌های تو اضافه شد. حالا زمان روزانه‌ش رو تنظیم کن.' : 'قبلاً این دوره رو برداشتی.');
    }

    public function edit(Enrollment $enrollment): View
    {
        $this->authorizeOwner($enrollment);

        $enrollment->load('course');

        return view('enrollments.edit', compact('enrollment'));
    }

    public function update(Request $request, Enrollment $enrollment): RedirectResponse
    {
        $this->authorizeOwner($enrollment);

        $validated = $request->validate([
            'priority' => ['required', 'integer', 'min:1', 'max:5'],
            'daily_time_minutes' => ['nullable', 'integer', 'min:5', 'max:480'],
            'preferred_time' => ['nullable', 'date_format:H:i'],
            'status' => ['required', 'in:active,paused,archived,maintenance'],
        ]);

        $enrollment->update($validated);

        return redirect()->route('courses.show', $enrollment->course)->with('status', 'زمان‌بندی ذخیره شد.');
    }

    protected function authorizeOwner(Enrollment $enrollment): void
    {
        abort_unless($enrollment->user_id === auth()->id(), 403);
    }
}
