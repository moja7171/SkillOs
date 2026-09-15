<?php

namespace Tests\Feature;

use App\Models\Attempt;
use App\Models\Lesson;
use App\Models\User;
use App\Services\Insights\WeakSpots;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeakSpotsTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_lessons_with_at_least_two_incorrect_finished_attempts_show_up(): void
    {
        $user = User::factory()->create();

        $struggling = Lesson::factory()->withActivities()->create(['title' => 'درس سخت']);
        $strugglingPractice = $struggling->practices()->first();
        Attempt::create(['activity_id' => $strugglingPractice->id, 'user_id' => $user->id, 'result_status' => 'incorrect']);
        Attempt::create(['activity_id' => $strugglingPractice->id, 'user_id' => $user->id, 'result_status' => 'incorrect']);
        Attempt::create(['activity_id' => $strugglingPractice->id, 'user_id' => $user->id, 'result_status' => 'correct']);

        $fine = Lesson::factory()->withActivities()->create(['title' => 'درس آسون']);
        $finePractice = $fine->practices()->first();
        Attempt::create(['activity_id' => $finePractice->id, 'user_id' => $user->id, 'result_status' => 'incorrect']);
        Attempt::create(['activity_id' => $finePractice->id, 'user_id' => $user->id, 'result_status' => 'correct']);
        Attempt::create(['activity_id' => $finePractice->id, 'user_id' => $user->id, 'result_status' => 'started']); // open, ignored

        $spots = app(WeakSpots::class)->forUser($user);

        $this->assertCount(1, $spots);
        $this->assertSame('درس سخت', $spots->first()['lesson']->title);
        $this->assertSame(2, $spots->first()['incorrect']);
        $this->assertSame(3, $spots->first()['total']);
    }

    public function test_the_page_lists_weak_spots_and_links_to_the_lesson(): void
    {
        $user = User::factory()->create();
        $lesson = Lesson::factory()->withActivities()->create();
        $practice = $lesson->practices()->first();
        Attempt::create(['activity_id' => $practice->id, 'user_id' => $user->id, 'result_status' => 'incorrect']);
        Attempt::create(['activity_id' => $practice->id, 'user_id' => $user->id, 'result_status' => 'incorrect']);

        $this->actingAs($user)->get(route('weak-spots'))->assertOk()
            ->assertSee($lesson->title)
            ->assertSee('۲ از ۲ غلط');
    }
}
