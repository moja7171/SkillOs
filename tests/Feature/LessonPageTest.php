<?php

namespace Tests\Feature;

use App\Models\Attempt;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_review_retention_row_only_shows_up_once_a_review_has_happened(): void
    {
        $user = User::factory()->create();
        $lesson = Lesson::factory()->withActivities()->create();
        $practice = $lesson->practices()->first();

        $this->actingAs($user)->get($lesson->url())->assertOk()->assertDontSee('نتیجه‌ی مرورها');

        Attempt::create(['activity_id' => $practice->id, 'user_id' => $user->id, 'result_status' => 'correct', 'evidence' => ['source' => 'review']]);
        Attempt::create(['activity_id' => $practice->id, 'user_id' => $user->id, 'result_status' => 'incorrect', 'evidence' => ['source' => 'review']]);
        Attempt::create(['activity_id' => $practice->id, 'user_id' => $user->id, 'result_status' => 'correct', 'evidence' => ['source' => 'free']]); // not a review, shouldn't count
        Attempt::create(['activity_id' => $practice->id, 'user_id' => $user->id, 'result_status' => 'started', 'evidence' => ['source' => 'review']]); // still open, shouldn't count

        $this->actingAs($user)->get($lesson->url())->assertOk()
            ->assertSee('نتیجه‌ی مرورها')
            ->assertSee('۱ از ۲ بار بلد بودی');
    }
}
