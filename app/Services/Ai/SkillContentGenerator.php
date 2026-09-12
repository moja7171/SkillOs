<?php

namespace App\Services\Ai;

use App\Models\Skill;
use Illuminate\Support\Facades\DB;

/**
 * Generates a Skill's learning assets in one Gemini call: an AI Text resource,
 * one learn activity and 2-3 practice activities (DECISIONS.md §6, §7).
 * Lazy and idempotent: guarded by skills.content_generated_at.
 */
class SkillContentGenerator
{
    public function __construct(protected GeminiClient $gemini) {}

    public function generate(Skill $skill): void
    {
        if ($skill->content_generated_at !== null) {
            return;
        }

        $skill->loadMissing(['learningItem', 'prerequisites']);

        $result = $this->gemini->generateJson($this->prompt($skill), $this->schema());

        DB::transaction(function () use ($skill, $result) {
            // Re-check inside the transaction so a double submit cannot duplicate content.
            if ($skill->fresh()->content_generated_at !== null) {
                return;
            }

            $learn = $result['learn'];

            $resource = $skill->resources()->create([
                'learning_item_id' => $skill->learning_item_id,
                'type' => 'text',
                'title' => $skill->name,
                'content' => $learn['explanation'],
                // AI text is the default recommendation unless a video is already attached.
                'is_recommended' => ! $skill->resources()->where('type', 'video')->exists(),
            ]);

            $skill->activities()->create([
                'learning_item_id' => $skill->learning_item_id,
                'type' => 'learn',
                'title' => $skill->name,
                'estimated_minutes' => max(3, (int) ($learn['estimated_minutes'] ?? 10)),
                'payload' => [
                    'resource_id' => $resource->id,
                    'key_points' => $learn['key_points'] ?? [],
                    'common_mistakes' => $learn['common_mistakes'] ?? [],
                ],
            ]);

            foreach ($result['practices'] as $practice) {
                $form = $this->resolveForm($practice);

                $payload = [
                    'form' => $form,
                    'prompt' => $practice['prompt'],
                    'expected_outcome' => $practice['expected_outcome'],
                    'hints' => array_slice($practice['hints'] ?? [], 0, 2),
                    'rubric' => $practice['rubric'],
                    'difficulty' => $practice['difficulty'],
                ];

                if ($form === 'mcq') {
                    $payload['options'] = array_map('trim', $practice['options']);
                    $payload['correct_option'] = (int) $practice['correct_option'];
                }

                $skill->activities()->create([
                    'learning_item_id' => $skill->learning_item_id,
                    'type' => 'practice',
                    'title' => $practice['title'],
                    'estimated_minutes' => max(3, (int) ($practice['estimated_minutes'] ?? 10)),
                    'payload' => $payload,
                ]);
            }

            $skill->update(['content_generated_at' => now()]);
        });
    }

    /**
     * MCQs are graded by rule, so an inconsistent one would silently mark right answers wrong.
     * Accept the mcq form only when the model's index and its own answer text agree;
     * otherwise grade the same prompt as a short answer via the rubric instead.
     *
     * @param  array<string, mixed>  $practice
     */
    protected function resolveForm(array $practice): string
    {
        if ($practice['form'] !== 'mcq') {
            return $practice['form'];
        }

        $options = array_map('trim', $practice['options'] ?? []);
        $index = $practice['correct_option'] ?? null;
        $text = trim((string) ($practice['correct_option_text'] ?? ''));

        $consistent = count($options) === 4
            && is_int($index) && $index >= 0 && $index < 4
            && $text !== '' && $options[$index] === $text;

        return $consistent ? 'mcq' : 'short_answer';
    }

    protected function prompt(Skill $skill): string
    {
        $item = $skill->learningItem;
        $prereqs = $skill->prerequisites->pluck('name')->join(', ') ?: 'none';
        $startingPoint = filled($item->starting_point) ? $item->starting_point : 'not specified';

        return <<<PROMPT
            You are a learning content designer for a self-taught learner.

            Learning topic: "{$item->title}"
            Final outcome of the topic: "{$item->outcome_statement}"
            The learner's own description of their starting level: "{$startingPoint}"

            Target skill: "{$skill->name}" — {$skill->description}
            Skills the learner has already covered before this one: {$prereqs}

            Produce:
            1. "learn": a focused instructional text for exactly this skill (Markdown, 300-600 words): explain the concept, show 1-3 concrete examples (use fenced code blocks for code), and state when to use it. Then "key_points" (3-5 short bullets) and "common_mistakes" (2-4 short bullets). Estimate reading minutes.
            2. "practices": 2 or 3 practice tasks that make the learner APPLY the skill. Choose the "form" that fits the topic: "coding" for programming, "scenario" for architecture/design decisions, "explanation" for conceptual skills, "short_answer" for factual recall, "mcq" for quick checks. Exactly one practice must have difficulty "intro"; the others "core" or "stretch". For every practice give: a short "title", the task "prompt", the "expected_outcome" (what a correct answer contains — shown to the learner only after they give up), exactly two "hints" (first gentle, second stronger; neither reveals the answer), a "rubric" the grader uses to judge correct / partial / incorrect, and estimated minutes. For "mcq" also give exactly 4 "options" (answer choices only — never put field names or labels in the list), "correct_option" (the 0-based index of the right choice in "options") and "correct_option_text" (the exact text of that same choice, copied verbatim). The expected_outcome and rubric must agree with correct_option.

            Write every learner-facing text (learn explanation, key points, mistakes, titles, prompts, hints, expected outcomes, options) in Persian (Farsi). Keep standard English technical terms and all code as they are; do not translate identifiers, commands or library names. Rubrics may be in English.
            PROMPT;
    }

    /**
     * @return array<string, mixed>
     */
    protected function schema(): array
    {
        $stringList = ['type' => 'ARRAY', 'items' => ['type' => 'STRING']];

        return [
            'type' => 'OBJECT',
            'properties' => [
                'learn' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'explanation' => ['type' => 'STRING'],
                        'key_points' => $stringList,
                        'common_mistakes' => $stringList,
                        'estimated_minutes' => ['type' => 'INTEGER'],
                    ],
                    'required' => ['explanation', 'key_points', 'common_mistakes', 'estimated_minutes'],
                ],
                'practices' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'title' => ['type' => 'STRING'],
                            'form' => ['type' => 'STRING', 'enum' => ['mcq', 'short_answer', 'coding', 'explanation', 'scenario']],
                            'prompt' => ['type' => 'STRING'],
                            'options' => $stringList,
                            'correct_option' => ['type' => 'INTEGER'],
                            'correct_option_text' => ['type' => 'STRING'],
                            'expected_outcome' => ['type' => 'STRING'],
                            'hints' => $stringList,
                            'rubric' => ['type' => 'STRING'],
                            'difficulty' => ['type' => 'STRING', 'enum' => ['intro', 'core', 'stretch']],
                            'estimated_minutes' => ['type' => 'INTEGER'],
                        ],
                        'required' => ['title', 'form', 'prompt', 'expected_outcome', 'hints', 'rubric', 'difficulty', 'estimated_minutes'],
                    ],
                ],
            ],
            'required' => ['learn', 'practices'],
        ];
    }
}
