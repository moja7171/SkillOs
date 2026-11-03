<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Attempt;
use App\Services\Evaluation\AttemptSession;
use App\Services\Mastery\MasteryService;
use App\Services\Review\ReviewQueue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class SessionController extends Controller
{
    public function __construct(protected AttemptSession $session, protected ReviewQueue $reviews, protected MasteryService $mastery) {}

    public function start(Request $request, Activity $activity): RedirectResponse
    {
        $attempt = $this->session->start($request->user(), $activity);

        return redirect()->route('session.show', $attempt);
    }

    /**
     * Smart entry point for a free activity: resumes an open attempt, or shows
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

    public function show(Request $request, Attempt $attempt): View|RedirectResponse
    {
        $this->authorizeOwner($attempt);

        $attempt->load('activity.lesson.course', 'activity.lesson.videos');
        $lesson = $attempt->activity->lesson;

        // Watching a lesson happens on the lesson page only (DECISIONS.md §70).
        if ($attempt->activity->isLearn()) {
            return redirect($lesson->url());
        }

        $open = $this->session->isOpen($attempt);
        $isReview = ($attempt->evidence['source'] ?? null) === 'review';

        // What comes after: a review leads to the next review, a practice to the next
        // unsolved practice of the same lesson — never to another lesson.
        $next = null;
        $reviewsLeft = 0;
        if (! $open) {
            if ($isReview) {
                $reviewsLeft = $this->reviews->due($request->user())->count();
            } else {
                $next = $this->nextPractice($attempt);
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
            'isReview' => $isReview,
            'next' => $next,
            'reviewsLeft' => $reviewsLeft,
            'history' => $history,
        ]);
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

        $destination = ($attempt->evidence['source'] ?? null) === 'review' ? route('home') : $attempt->activity->lesson->url();
        $attempt->delete();

        return redirect($destination);
    }

    /**
     * «این سوال مشکل داره» on an AI-written review question (DECISIONS.md §70): the
     * learner's attempt and its effect on mastery are taken back, the question is retired
     * for everyone, and the lesson is due again so its next review writes a new one.
     */
    public function report(Attempt $attempt): RedirectResponse
    {
        $this->authorizeOwner($attempt);
        $activity = $attempt->activity;
        abort_unless($activity->generated && ! $this->session->isOpen($attempt), 422);

        $this->mastery->revertAttempt($attempt);
        $attempt->delete();
        $activity->update(['payload' => $activity->payload + ['retired' => true]]);

        return redirect()->route('home')->with('status', 'سوال کنار گذاشته شد. مرور بعدی این درس یه سوال تازه می‌گیره.');
    }

    /**
     * The next practice of the same lesson the learner hasn't passed yet: after this one
     * if there is one, else the earliest left.
     */
    protected function nextPractice(Attempt $attempt): ?Activity
    {
        $passed = Attempt::where('user_id', $attempt->user_id)
            ->whereIn('result_status', ['correct', 'correct_with_hint'])
            ->pluck('activity_id');

        $unsolved = $attempt->activity->lesson->practices()
            ->where('id', '!=', $attempt->activity_id)
            ->whereNotIn('id', $passed)
            ->get();

        return $unsolved->first(fn (Activity $a) => $a->id > $attempt->activity_id) ?? $unsolved->first();
    }

    protected function authorizeOwner(Attempt $attempt): void
    {
        abort_unless($attempt->user_id === auth()->id(), 403);
    }
}
