<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LessonController extends Controller
{
    public function show(Request $request, Lesson $lesson): View
    {
        $user = $request->user();

        $lesson->load([
            'course',
            'videos',
            'practices',
            'prerequisites.masteryRecords' => fn ($q) => $q->where('user_id', $user->id),
            'masteryRecords' => fn ($q) => $q->where('user_id', $user->id),
        ]);

        $enrollment = $lesson->course->enrollmentFor($user);
        $siblings = $lesson->course->lessons()->get(['id', 'order', 'title']);
        $previous = $siblings->where('order', '<', $lesson->order)->last();
        $next = $siblings->where('order', '>', $lesson->order)->first();

        return view('lessons.show', compact('lesson', 'enrollment', 'previous', 'next'));
    }
}
