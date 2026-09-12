<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enrollment>
 */
class EnrollmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'course_id' => Course::factory(),
            'status' => 'active',
            'priority' => 3,
            'daily_time_minutes' => null,
        ];
    }

    public function scheduled(int $minutes = 30): static
    {
        return $this->state(fn () => ['daily_time_minutes' => $minutes]);
    }
}
