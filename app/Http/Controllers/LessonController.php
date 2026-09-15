<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\MasteryRecord;
use App\Services\Evaluation\AttemptSession;
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
            'learnActivity',
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

        $lessonDone = $lesson->isMarkedDoneBy($user);
        $practicesPassed = $lesson->allPracticesPassedBy($user);

        // Sidebar circle: "marked done" is a separate, sticky signal from mastery level
        // (DECISIONS.md) — batched here for the whole curriculum to avoid an N+1 in the loop.
        $doneLessonIds = Activity::where('type', 'learn')
            ->whereIn('lesson_id', $siblings->pluck('id'))
            ->whereHas('attempts', fn ($q) => $q->where('user_id', $user->id)->where('evidence->source', 'lesson_done'))
            ->pluck('lesson_id');

        return view('lessons.show', compact('lesson', 'course', 'enrollment', 'previous', 'next', 'lessonDone', 'practicesPassed', 'doneLessonIds'));
    }

    /**
     * The explicit "انجام دادم" action (DECISIONS.md). Gate is re-checked server-side —
     * the button is only rendered clickable once it already passes, but a stale page or
     * a direct request shouldn't be trusted to have enforced that.
     */
    public function markDone(Request $request, Lesson $lesson, AttemptSession $sessions): RedirectResponse
    {
        $user = $request->user();
        $lesson->loadMissing('practices', 'learnActivity');

        abort_unless($lesson->allPracticesPassedBy($user), 422);

        if (! $lesson->isMarkedDoneBy($user)) {
            $sessions->markLessonDone($user, $lesson->learnActivity);
        }

        return redirect($lesson->url())->with('status', 'این درس رو «انجام‌شده» علامت زدی.');
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
