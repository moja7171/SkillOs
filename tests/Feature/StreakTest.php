<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StreakTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_activity_ever_starts_the_streak_at_one(): void
    {
        $user = User::factory()->create();

        $user->recordActivityToday();

        $this->assertSame(1, $user->streak_count);
        $this->assertTrue($user->streak_last_date->isToday());
    }

    public function test_activity_the_next_day_bumps_the_streak(): void
    {
        $user = User::factory()->create(['streak_count' => 4, 'streak_last_date' => today()->subDay()]);

        $user->recordActivityToday();

        $this->assertSame(5, $user->streak_count);
    }

    public function test_a_gap_resets_the_streak_to_one(): void
    {
        $user = User::factory()->create(['streak_count' => 10, 'streak_last_date' => today()->subDays(3)]);

        $user->recordActivityToday();

        $this->assertSame(1, $user->streak_count);
    }

    public function test_a_second_activity_the_same_day_does_not_double_count(): void
    {
        $user = User::factory()->create(['streak_count' => 4, 'streak_last_date' => today()]);

        $user->recordActivityToday();

        $this->assertSame(4, $user->streak_count);
    }
}
