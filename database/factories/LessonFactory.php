<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Lesson>
 */
class LessonFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'slug' => 'lesson-'.Str::lower(Str::random(6)),
            'order' => 0,
            'title' => 'درس '.fake()->words(2, true),
            'summary' => fake()->sentence(),
            'content' => "## مقدمه\n\nمتن درس.\n\n```python\nprint(1)\n```",
            'key_points' => ['نکته‌ی یک'],
            'common_mistakes' => [],
            'estimated_minutes' => 8,
        ];
    }

    /**
     * Adds the learn activity and one coding practice, like the importer would.
     */
    public function withActivities(): static
    {
        return $this->afterCreating(function (Lesson $lesson) {
            $lesson->activities()->create(['key' => 'learn', 'type' => 'learn', 'title' => $lesson->title, 'estimated_minutes' => $lesson->estimated_minutes, 'payload' => []]);
            $lesson->activities()->create(['key' => 'p1', 'type' => 'practice', 'title' => 'تمرین کد', 'estimated_minutes' => 8, 'payload' => [
                'form' => 'coding', 'prompt' => 'تابعی بنویس که ...', 'expected_outcome' => 'استفاده از strip', 'hints' => ['راهنمایی ۱', 'راهنمایی ۲'], 'rubric' => 'Correct if strip is used.', 'difficulty' => 'core',
            ]]);
        });
    }
}
