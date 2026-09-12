<?php

namespace App\Services\Evaluation;

use App\Models\Activity;
use App\Services\Ai\GeminiClient;

/**
 * Picks the evaluator per practice form (DECISIONS.md §5): mcq is graded by rule,
 * everything else by Gemini against the stored rubric — the only runtime AI call.
 */
class Evaluator
{
    public function __construct(protected GeminiClient $gemini) {}

    public function evaluate(Activity $activity, string $response, int $hintLevel): Verdict
    {
        $payload = $activity->payload;

        if ($payload['form'] === 'mcq') {
            return $this->evaluateMcq($payload, $response);
        }

        return $this->evaluateWithRubric($payload, $response, $hintLevel);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function evaluateMcq(array $payload, string $response): Verdict
    {
        $chosen = (int) $response;
        $correct = (int) $payload['correct_option'];

        return $chosen === $correct
            ? new Verdict(Verdict::CORRECT, 'درسته.')
            : new Verdict(Verdict::INCORRECT, 'این گزینه درست نیست.');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function evaluateWithRubric(array $payload, string $response, int $hintLevel): Verdict
    {
        $prompt = <<<PROMPT
            You are grading one practice answer from a self-taught learner. Judge strictly by the rubric.

            Task given to the learner:
            {$payload['prompt']}

            What a correct answer contains (reference, never shown to the learner before they give up):
            {$payload['expected_outcome']}

            Rubric:
            {$payload['rubric']}

            Hints already shown to the learner: {$hintLevel} of 2.

            Learner's answer:
            <<<
            {$response}
            >>>

            Return a verdict: "correct" if the answer satisfies the rubric, "partial" if it is on the right track but misses something the rubric requires, "incorrect" otherwise. Then write feedback for the learner in Persian (Farsi), at most 80 words, keeping English technical terms and code as they are: say what is right, and what is missing or wrong. If the verdict is not "correct", do NOT reveal the answer or the reference solution — point at the gap only. Never mention scores, points, grades or percentages.
            PROMPT;

        $schema = [
            'type' => 'OBJECT',
            'properties' => [
                'verdict' => ['type' => 'STRING', 'enum' => [Verdict::CORRECT, Verdict::PARTIAL, Verdict::INCORRECT]],
                'feedback' => ['type' => 'STRING'],
            ],
            'required' => ['verdict', 'feedback'],
        ];

        $result = $this->gemini->generateJson($prompt, $schema);

        $verdict = in_array($result['verdict'] ?? null, [Verdict::CORRECT, Verdict::PARTIAL, Verdict::INCORRECT], true)
            ? $result['verdict']
            : Verdict::INCORRECT;

        return new Verdict($verdict, trim((string) ($result['feedback'] ?? '')));
    }
}
