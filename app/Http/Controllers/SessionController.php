<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Attempt;
use App\Models\PlanItem;
use App\Services\Evaluation\AttemptSession;
use App\Services\Planning\Planner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class SessionController extends Controller
{
    public function __construct(protected AttemptSession $session, protected Planner $planner) {}

    public function start(Request $request, Activity $activity): RedirectResponse
    {
        $attempt = $this->session->start($request->user(), $activity);

        return redirect()->route('session.show', $attempt);
    }

    /**
     * Smart entry point for a free (non-plan) activity: resumes an open attempt, or shows
     * the most recent finished one (with its result and the full history below it) instead
     * of silently starting a blank new attempt over an already-answered practice. Starting
     * genuinely fresh is a deliberate separate action — the "دوباره همین تمرین" button on a
     * finished attempt, which still calls `start()`.
     */
    public function open(Request $request, Activity $activity): RedirectResponse
    {
        $latest = $request->user()->attempts()
            ->where('activity_id', $activity->id)
            ->latest('id')
            ->first();

        if ($latest) {
            return redirect()->route('session.show', $latest);
        }

        $attempt = $this->session->start($request->user(), $activity);

        return redirect()->route('session.show', $attempt);
    }

    public function startPlanned(Request $request, PlanItem $planItem): RedirectResponse
    {
        abort_unless($planItem->user_id === $request->user()->id, 403);
        abort_unless($planItem->status === 'scheduled', 422);

        $attempt = $this->session->startPlanned($request->user(), $planItem);

        return redirect()->route('session.show', $attempt);
    }

    /**
     * Browser Back/Forward or a refresh can replay an action URL as GET. Instead of a 405,
     * resume the learner's open attempt for that activity, or send them home.
     */
    public function resumeOrHome(Request $request, Activity $activity): RedirectResponse
    {
        $open = $request->user()->attempts()
            ->where('activity_id', $activity->id)
            ->where('result_status', 'started')
            ->latest('id')
            ->first();

        return $open
            ? redirect()->route('session.show', $open)
            : redirect()->route('home');
    }

    public function show(Request $request, Attempt $attempt): View
    {
        $this->authorizeOwner($attempt);

        $attempt->load('activity.lesson.course', 'activity.lesson.videos');
        $lesson = $attempt->activity->lesson;
        $open = $this->session->isOpen($attempt);

        // Next Best Action: the planner's recommendation once this attempt is done;
        // fall back to the lesson's own practices when nothing is planned.
        $nextPlanItem = null;
        $nextActivity = null;
        if (! $open) {
            $nextPlanItem = $this->planner->continueLearning($request->user())['primary'];
            if (! $nextPlanItem) {
                $nextActivity = $attempt->activity->isLearn()
                    ? $lesson->practices()->first()
                    : $lesson->practices()->where('id', '>', $attempt->activity_id)->first();
            }
        }

        // Every other attempt at this same activity, finished or still open, newest first —
        // so a learner re-opening a practice they've already tried sees they've been here
        // before instead of it looking like a fresh, unattempted exercise.
        $history = $attempt->user->attempts()
            ->where('activity_id', $attempt->activity_id)
            ->where('id', '!=', $attempt->id)
            ->latest('id')
            ->get();

        return view('session.show', [
            'attempt' => $attempt,
            'activity' => $attempt->activity,
            'lesson' => $lesson,
            'open' => $open,
            'nextPlanItem' => $nextPlanItem,
            'next' => $nextActivity,
            'history' => $history,
        ]);
    }

    public function complete(Attempt $attempt): RedirectResponse
    {
        $this->authorizeOwner($attempt);
        abort_unless($attempt->activity->isLearn() && $this->session->isOpen($attempt), 422);

        $this->session->completeLearn($attempt);

        return redirect()->route('session.show', $attempt);
    }

    public function submit(Request $request, Attempt $attempt): RedirectResponse
    {
        $this->authorizeOwner($attempt);
        abort_unless(! $attempt->activity->isLearn() && $this->session->isOpen($attempt), 422);

        $validated = $request->validate([
            'response' => ['required', 'string', 'max:20000'],
        ], [], ['response' => 'پاسخ']);

        try {
            $this->session->submit($attempt, $validated['response']);
        } catch (RuntimeException $e) {
            report($e);

            return redirect()->route('session.show', $attempt)
                ->withInput()
                ->with('error', 'الان نتونستم پاسخت رو ارزیابی کنم. چند لحظه بعد دوباره ارسال کن — پاسخت پاک نشده.');
        }

        return redirect()->route('session.show', $attempt);
    }

    public function giveUp(Attempt $attempt): RedirectResponse
    {
        $this->authorizeOwner($attempt);
        abort_unless(! $attempt->activity->isLearn() && $this->session->isOpen($attempt), 422);

        $this->session->giveUp($attempt);

        return redirect()->route('session.show', $attempt);
    }

    /**
     * A true, no-penalty exit: unlike `giveUp` (finalizes as incorrect and reveals the
     * answer), this discards the open attempt entirely — nothing is recorded, mastery is
     * untouched, and it's as if the learner never clicked "شروع".
     */
    public function cancel(Attempt $attempt): RedirectResponse
    {
        $this->authorizeOwner($attempt);
        abort_unless($this->session->isOpen($attempt), 422);

        $lesson = $attempt->activity->lesson;
        $attempt->delete();

        return redirect($lesson->url());
    }

    protected function authorizeOwner(Attempt $attempt): void
    {
        abort_unless($attempt->user_id === auth()->id(), 403);
    }
}
