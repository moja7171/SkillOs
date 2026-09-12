<?php

namespace App\Services\Ai;

use App\Models\LearningItem;
use App\Models\Skill;
use Illuminate\Support\Facades\DB;

class LearningDesignGenerator
{
    public function __construct(protected GeminiClient $gemini) {}

    /**
     * Generate a proposed Outcome + Skill structure for a Learning Item.
     * This is a draft only — it must go through human approval before
     * it becomes real Skill/SkillDependency rows.
     *
     * @return array{outcome_statement: string, skills: array<int, array{key: string, name: string, description: string, prerequisite_keys: array<int, string>}>}
     */
    public function generate(LearningItem $item): array
    {
        $startingPoint = filled($item->starting_point)
            ? "\nThe learner describes their current level as: \"{$item->starting_point}\". Skip what they already know and start from there.\n"
            : '';

        $prompt = <<<PROMPT
            You are a learning design architect. A learner wants to learn: "{$item->title}".
            {$startingPoint}

            Propose:
            1. A concrete, measurable Outcome statement: what the learner will be able to DO after completing this Learning Item.
            2. A breakdown into 4-10 Skills that compose that Outcome. Each Skill needs a short unique key (like "s1"), a name, a one-sentence description, and the keys of any other skills it depends on (prerequisites), if any.

            Order skills so prerequisites generally come before dependents. Keep it practical and scoped to a self-taught learner, not an exhaustive curriculum.

            Write the outcome_statement, every skill name and every description in Persian (Farsi). Keep standard English technical terms as they are (e.g. "virtual environment", "DataFrame", "try/except") instead of forcing a translation. Skill keys stay short ASCII identifiers.
            PROMPT;

        $schema = [
            'type' => 'OBJECT',
            'properties' => [
                'outcome_statement' => ['type' => 'STRING'],
                'skills' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'key' => ['type' => 'STRING'],
                            'name' => ['type' => 'STRING'],
                            'description' => ['type' => 'STRING'],
                            'prerequisite_keys' => [
                                'type' => 'ARRAY',
                                'items' => ['type' => 'STRING'],
                            ],
                        ],
                        'required' => ['key', 'name', 'description', 'prerequisite_keys'],
                    ],
                ],
            ],
            'required' => ['outcome_statement', 'skills'],
        ];

        /** @var array{outcome_statement: string, skills: array<int, array{key: string, name: string, description: string, prerequisite_keys: array<int, string>}>} $result */
        $result = $this->gemini->generateJson($prompt, $schema);

        return $result;
    }

    /**
     * Turn an approved draft (from $item->design_draft) into real Skill and
     * SkillDependency rows. This is the only place skills get created — it
     * only runs after a human has clicked Approve.
     */
    public function applyDraft(LearningItem $item): void
    {
        $draft = $item->design_draft;

        DB::transaction(function () use ($item, $draft) {
            $skillIdsByKey = [];

            foreach ($draft['skills'] as $order => $skillDraft) {
                $skill = $item->skills()->create([
                    'name' => $skillDraft['name'],
                    'description' => $skillDraft['description'] ?? null,
                    'order' => $order,
                ]);

                $skillIdsByKey[$skillDraft['key']] = $skill->id;
            }

            foreach ($draft['skills'] as $skillDraft) {
                $skillId = $skillIdsByKey[$skillDraft['key']];

                foreach ($skillDraft['prerequisite_keys'] ?? [] as $prereqKey) {
                    if (! isset($skillIdsByKey[$prereqKey]) || $skillIdsByKey[$prereqKey] === $skillId) {
                        continue;
                    }

                    Skill::find($skillId)->prerequisites()->syncWithoutDetaching([$skillIdsByKey[$prereqKey]]);
                }
            }

            $item->update([
                'outcome_statement' => $draft['outcome_statement'],
                'design_status' => 'approved',
                'design_approved_at' => now(),
            ]);
        });
    }
}
