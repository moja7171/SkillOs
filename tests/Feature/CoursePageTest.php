<?php

namespace Tests\Feature;

use App\Models\Attempt;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoursePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_seen_mark_only_shows_up_after_the_learn_activity_is_completed(): void
    {
        $user = User::factory()->create();
        $lesson = Lesson::factory()->withActivities()->create();
        Enrollment::factory()->for($user)->for($lesson->course)->create();

        $before = $this->actingAs($user)->get(route('courses.show', $lesson->course))->assertOk();
        $before->assertDontSee('ویدیو/متن این درس رو دیدی', false);

        Attempt::create(['activity_id' => $lesson->learnActivity->id, 'user_id' => $user->id, 'result_status' => 'completed']);

        $this->actingAs($user)->get(route('courses.show', $lesson->course))->assertOk()
            ->assertSee('ویدیو/متن این درس رو دیدی', false);
    }
}
