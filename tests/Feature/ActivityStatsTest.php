<?php

namespace Tests\Feature;

use App\Models\Attempt;
use App\Models\Lesson;
use App\Models\User;
use App\Services\Engagement\ActivityStats;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_counts_and_sums_minutes_only_for_attempts_completed_since_the_cutoff(): void
    {
        $user = User::factory()->create();
        $lesson = Lesson::factory()->withActivities()->create();
        $practice = $lesson->practices()->first();

        Attempt::create(['activity_id' => $practice->id, 'user_id' => $user->id, 'result_status' => 'correct', 'completed_at' => now()->subDays(2)]);
        Attempt::create(['activity_id' => $practice->id, 'user_id' => $user->id, 'result_status' => 'incorrect', 'completed_at' => now()->subDays(10)]);
        Attempt::create(['activity_id' => $practice->id, 'user_id' => $user->id, 'result_status' => 'started']); // still open, no completed_at

        $stats = app(ActivityStats::class);

        $this->assertSame(['count' => 1, 'minutes' => 8], $stats->since($user, now()->subDays(7)));
        $this->assertSame(['count' => 2, 'minutes' => 16], $stats->since($user, now()->subDays(30)));
    }
}
