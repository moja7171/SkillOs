<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Services\Planning\Planner;
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

        // A brand-new enrollment always needs a priority + daily time to actually enter
        // the plan (DESIGN §4), so go straight to that form instead of just hinting at it
        // in a flash message — same landing spot no matter where "برداشتن" was clicked
        // from (catalog page or the course page itself).
        if ($enrollment->wasRecentlyCreated) {
            return redirect()->route('enrollments.edit', $enrollment)
                ->with('status', 'به دوره‌های تو اضافه شد. حالا زمان روزانه‌ش رو تنظیم کن.');
        }

        return redirect()->route('courses.show', $course)->with('status', 'قبلاً این دوره رو برداشتی.');
    }

    public function edit(Enrollment $enrollment): View
    {
        $this->authorizeOwner($enrollment);

        $enrollment->load('course');

        return view('enrollments.edit', compact('enrollment'));
    }

    public function update(Request $request, Enrollment $enrollment, Planner $planner): RedirectResponse
    {
        $this->authorizeOwner($enrollment);

        $validated = $request->validate([
            'priority' => ['required', 'integer', 'min:1', 'max:5'],
            'daily_time_minutes' => ['nullable', 'integer', 'min:5', 'max:480'],
            'preferred_time' => ['nullable', 'date_format:H:i'],
            'status' => ['required', 'in:active,paused,archived,maintenance'],
            'video_days' => ['nullable', 'array'],
            'video_days.*' => ['integer', 'between:0,6'],
        ]);
        // Unchecked = no restriction; the "video_days" key is absent from an empty checkbox group.
        $validated['video_days'] = $request->filled('video_days') ? array_values(array_map('intval', $validated['video_days'])) : null;

        $wasInactive = in_array($enrollment->status, ['paused', 'archived'], true);
        $enrollment->update($validated);

        $queued = 0;
        if ($wasInactive && $enrollment->status === 'active') {
            $queued = $planner->reactivate($enrollment);
        }
        $planner->recompute($enrollment);

        if ($queued > 0) {
            return redirect()->route('courses.show', $enrollment->course)
                ->with('status', 'خوش برگشتی. چون مدتی نبودی، '.fa_num($queued).' درس برای مرور امروز آماده شده.');
        }

        return redirect()->route('courses.show', $enrollment->course)->with('status', 'زمان‌بندی ذخیره شد.');
    }

    protected function authorizeOwner(Enrollment $enrollment): void
    {
        abort_unless($enrollment->user_id === auth()->id(), 403);
    }
}
