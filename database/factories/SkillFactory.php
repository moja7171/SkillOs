<?php

namespace Database\Factories;

use App\Models\LearningItem;
use App\Models\Skill;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Skill>
 */
class SkillFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'learning_item_id' => LearningItem::factory()->approved(),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'order' => 0,
        ];
    }
}
