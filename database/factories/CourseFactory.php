<?php

namespace Database\Factories;

use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slug' => 'course-'.Str::lower(Str::random(6)),
            'title' => 'دوره‌ی '.fake()->words(2, true),
            'description' => fake()->sentence(),
            'outcome_statement' => 'بتونی '.fake()->sentence(),
            'source_note' => null,
        ];
    }
}
