<?php

namespace App\Services\Evaluation;

use App\Models\Activity;
use App\Models\Attempt;
use App\Models\PlanItem;
use App\Models\User;
use App\Services\Mastery\MasteryService;
use Illuminate\Support\Facades\DB;

/**
 * State machine for one attempt (DESIGN.md §2.2). Everything the session page needs is
 * kept in attempts.evidence:
 *   response        last submitted answer
 *   verdict         last verdict (correct|partial|incorrect)
 *   feedback        last feedback text
 *   hints_shown     number of hints revealed so far (== hint_level)
 *   partial_retry   true once a partial verdict granted a retry
 *   revealed        true when the expected outcome was shown
 *   source          free|plan|review — where the attempt was launched from
 *   plan_item_id    set when launched from today's plan; that item completes on finalize
 *   history         [{response, verdict, feedback}]
 *   level_change    {from, to} when the lesson's level moved on finalize
 */
class AttemptSession
{
    public const MAX_HINTS = 2;

    public function __construct(protected Evaluator $evaluator, protected MasteryService $mastery) {}

    public function start(User $user, Activity $activity, string $source = 'free', ?PlanItem $planItem = null): Attempt
    {
        $evidence = ['source' => $source, 'hints_shown' => 0, 'history' => []];
        if ($planItem) {
            $evidence['plan_item_id'] = $planItem->id;
        }

        return $user->attempts()->create([
            'activity_id' => $activity->id,
            'started_at' => now(),
            'result_status' => 'started',
            'hint_level' => 0,
            'evidence' => $evidence,
        ]);
    }

    /**
     * Launch from today's plan: the attempt inherits the item's source (plan|review)
     * and completes it when finalized. Outside-plan attempts never complete plan items.
     */
    public function startPlanned(User $user, PlanItem $planItem): Attempt
    {
        return $this->start($user, $planItem->activity, $planItem->source === 'review' ? 'review' : 'plan', $planItem);
    }

    /**
     * Learn activities have no right or wrong: the learner marks them done.
     */
    public function completeLearn(Attempt $attempt): void
    {
        $this->finalize($attempt, 'completed');
    }

    /**
     * The explicit "انجام دادم" action on the lesson page (DECISIONS.md): the learner
     * confirms a lesson is done, once every one of its practices has been answered
     * correctly. Recorded as its own attempt (evidence.source = 'lesson_done') so it can
     * be told apart from a plan-driven "آماده‌ام" and stays a permanent record even if a
     * later review knocks the numeric mastery back down.
     */
    public function markLessonDone(User $user, Activity $learnActivity): Attempt
    {
        $attempt = $this->start($user, $learnActivity, 'lesson_done');
        $this->finalize($attempt, 'completed');

        return $attempt;
    }

    /**
     * Evaluate a practice response and move the attempt to its next state.
     */
    public function submit(Attempt $attempt, string $response): Verdict
    {
        $verdict = $this->evaluator->evaluate($attempt->activity, $response, $attempt->hint_level);

        $evidence = $attempt->evidence;
        $evidence['response'] = $response;
        $evidence['verdict'] = $verdict->verdict;
        $evidence['feedback'] = $verdict->feedback;
        $evidence['history'][] = ['response' => $response, 'verdict' => $verdict->verdict, 'feedback' => $verdict->feedback];

        if ($verdict->isCorrect()) {
            $assisted = $attempt->hint_level > 0 || ! empty($evidence['partial_retry']);
            $attempt->evidence = $evidence;
            $this->finalize($attempt, $assisted ? 'correct_with_hint' : 'correct');

            return $verdict;
        }

        if ($verdict->isPartial() && empty($evidence['partial_retry'])) {
            // One free retry after a partial answer; a later success counts as assisted.
            $evidence['partial_retry'] = true;
            $attempt->update(['evidence' => $evidence]);

            return $verdict;
        }

        if ($attempt->hint_level < self::MAX_HINTS) {
            $attempt->hint_level++;
            $evidence['hints_shown'] = $attempt->hint_level;
            $attempt->evidence = $evidence;
            $attempt->save();

            return $verdict;
        }

        $evidence['revealed'] = true;
        $attempt->evidence = $evidence;
        $this->finalize($attempt, 'incorrect');

        return $verdict;
    }

    public function giveUp(Attempt $attempt): void
    {
        $evidence = $attempt->evidence;
        $evidence['revealed'] = true;
        $attempt->evidence = $evidence;
        $this->finalize($attempt, 'incorrect');
    }

    public function isOpen(Attempt $attempt): bool
    {
        return $attempt->result_status === 'started';
    }

    protected function finalize(Attempt $attempt, string $resultStatus): void
    {
        DB::transaction(function () use ($attempt, $resultStatus) {
            $attempt->result_status = $resultStatus;
            $attempt->completed_at = now();
            $attempt->save();

            $change = $this->mastery->applyAttempt($attempt);
            if ($change['old_level'] !== $change['new_level']) {
                $evidence = $attempt->evidence;
                $evidence['level_change'] = ['from' => $change['old_level'], 'to' => $change['new_level']];
                $attempt->evidence = $evidence;
                $attempt->save();
            }

            if ($planItemId = $attempt->evidence['plan_item_id'] ?? null) {
                PlanItem::where('id', $planItemId)->where('user_id', $attempt->user_id)->where('status', 'scheduled')
                    ->update(['status' => 'completed']);
            }

            $attempt->activity->lesson->course->enrollments()
                ->where('user_id', $attempt->user_id)
                ->update(['last_activity_at' => now()]);

            $attempt->user->recordActivityToday();
        });
    }
}
