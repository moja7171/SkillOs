<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Attempt;
use App\Services\Evaluation\AttemptSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class SessionController extends Controller
{
    public function __construct(protected AttemptSession $session) {}

    public function start(Request $request, Activity $activity): RedirectResponse
    {
        $attempt = $this->session->start($request->user(), $activity);

        return redirect()->route('session.show', $attempt);
    }

    public function show(Attempt $attempt): View
    {
        $this->authorizeOwner($attempt);

        $attempt->load('activity.lesson.course', 'activity.lesson.videos');
        $lesson = $attempt->activity->lesson;

        $next = $attempt->activity->isLearn()
            ? $lesson->practices()->first()
            : $lesson->practices()->where('id', '>', $attempt->activity_id)->first();

        return view('session.show', [
            'attempt' => $attempt,
            'activity' => $attempt->activity,
            'lesson' => $lesson,
            'open' => $this->session->isOpen($attempt),
            'next' => $next,
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

    protected function authorizeOwner(Attempt $attempt): void
    {
        abort_unless($attempt->user_id === auth()->id(), 403);
    }
}
