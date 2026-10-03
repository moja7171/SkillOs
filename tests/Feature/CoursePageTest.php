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

    public function test_lesson_and_practice_items_are_shown_independently(): void
    {
        $user = User::factory()->create();
        $lesson = Lesson::factory()->withActivities()->create();
        Enrollment::factory()->for($user)->for($lesson->course)->create();
        $practice = $lesson->practices()->first();

        $this->actingAs($user)->get(route('courses.show', $lesson->course))->assertOk()
            ->assertDontSee('این درس رو انجام دادی')
            ->assertSee('تمرین ۰/۱')
            ->assertSee('۰ از ۱ درس انجام‌شده');

        // Lesson done, practices pending.
        $this->actingAs($user)->post(route('lessons.mark-done', $lesson));
        $this->actingAs($user)->get(route('courses.show', $lesson->course))->assertOk()
            ->assertSee('این درس رو انجام دادی')
            ->assertSee('تمرین ۰/۱')
            ->assertSee('۱ از ۱ درس انجام‌شده');

        // Practices passed too.
        Attempt::create(['activity_id' => $practice->id, 'user_id' => $user->id, 'result_status' => 'correct']);
        $this->actingAs($user)->get(route('courses.show', $lesson->course))->assertOk()
            ->assertSee('تمرین ۱/۱');
    }

    public function test_passing_practices_does_not_mark_the_lesson_done(): void
    {
        $user = User::factory()->create();
        $lesson = Lesson::factory()->withActivities()->create();
        Enrollment::factory()->for($user)->for($lesson->course)->create();
        Attempt::create(['activity_id' => $lesson->practices()->first()->id, 'user_id' => $user->id, 'result_status' => 'correct']);

        $this->actingAs($user)->get(route('courses.show', $lesson->course))->assertOk()
            ->assertDontSee('این درس رو انجام دادی')
            ->assertSee('تمرین ۱/۱');
    }

    public function test_a_lesson_without_practices_shows_no_practice_item(): void
    {
        $user = User::factory()->create();
        $lesson = Lesson::factory()->create();
        Enrollment::factory()->for($user)->for($lesson->course)->create();

        $this->actingAs($user)->get(route('courses.show', $lesson->course))->assertOk()
            ->assertDontSee('تمرین ۰/');
    }

    public function test_the_lesson_sidebar_flags_a_finished_lesson_with_practices_left(): void
    {
        $user = User::factory()->create();
        $lesson = Lesson::factory()->withActivities()->create();
        Enrollment::factory()->for($user)->for($lesson->course)->create();

        $this->actingAs($user)->get($lesson->url())->assertOk()
            ->assertSee('۰ از ۱ درس انجام‌شده')
            ->assertDontSee('تمرین مونده');

        $this->actingAs($user)->post(route('lessons.mark-done', $lesson));
        $this->actingAs($user)->get($lesson->url())->assertOk()
            ->assertSee('۱ از ۱ درس انجام‌شده')
            ->assertSee('۰ از ۱ تمرین پاس‌شده')
            ->assertSee('تمرین مونده');
    }
}
