<?php

namespace Database\Factories;

use App\Models\LearningItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LearningItem>
 */
class LearningItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->words(2, true),
        ];
    }

    /**
     * An AI draft is staged and waiting for the learner's decision.
     */
    public function pendingReview(): static
    {
        return $this->state(fn () => [
            'design_status' => 'pending_review',
            'design_draft' => [
                'outcome_statement' => 'Write small command-line tools.',
                'skills' => [
                    ['key' => 's1', 'name' => 'Variables', 'description' => 'Store values.', 'prerequisite_keys' => []],
                    ['key' => 's2', 'name' => 'Loops', 'description' => 'Repeat work.', 'prerequisite_keys' => ['s1']],
                ],
            ],
        ]);
    }

    /**
     * The design has been approved; skills are expected to exist.
     */
    public function approved(): static
    {
        return $this->state(fn () => [
            'design_status' => 'approved',
            'outcome_statement' => 'Write small command-line tools.',
            'design_approved_at' => now(),
        ]);
    }
}
