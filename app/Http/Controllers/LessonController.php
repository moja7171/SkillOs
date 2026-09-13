<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\MasteryRecord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LessonController extends Controller
{
    public function show(Request $request, Course $course, Lesson $lesson): View
    {
        $user = $request->user();

        $lesson->load([
            'videos',
            'practices',
            'prerequisites.masteryRecords' => fn ($q) => $q->where('user_id', $user->id),
            'masteryRecords' => fn ($q) => $q->where('user_id', $user->id),
        ]);
        $lesson->setRelation('course', $course);
        $lesson->prerequisites->each->setRelation('course', $course);

        // The sidebar curriculum: every lesson of the course with the learner's level.
        $course->load(['lessons' => fn ($q) => $q->withCount('videos')->with([
            'masteryRecords' => fn ($q) => $q->where('user_id', $user->id),
        ])]);

        $enrollment = $course->enrollmentFor($user);
        $siblings = $course->lessons;
        $previous = $siblings->where('order', '<', $lesson->order)->last();
        $next = $siblings->where('order', '>', $lesson->order)->first();

        return view('lessons.show', compact('lesson', 'course', 'enrollment', 'previous', 'next'));
    }

    /**
     * "Continue": the first lesson the learner has not reached «آشنا» on, else the first lesson.
     */
    public function continue(Request $request, Course $course): RedirectResponse
    {
        $user = $request->user();
        $course->load(['lessons.masteryRecords' => fn ($q) => $q->where('user_id', $user->id)]);

        $lesson = $course->lessons->first(fn (Lesson $l) => MasteryRecord::LEVEL_INDEX[$l->levelFor($user)] < 2)
            ?? $course->lessons->first();

        abort_if($lesson === null, 404);

        return redirect()->to($lesson->url());
    }
}
