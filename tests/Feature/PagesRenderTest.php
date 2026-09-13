<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\MasteryRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PagesRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_pages_render_in_persian_rtl(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('dir="rtl"', false)->assertSee('ورود');
        $this->get(route('register'))->assertOk()->assertSee('ثبت‌نام');
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_home_catalog_course_lesson_and_profile_render(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['title' => 'دوره‌ی آزمایشی']);
        $first = Lesson::factory()->for($course)->withActivities()->create(['order' => 0, 'title' => 'درس اول']);
        $second = Lesson::factory()->for($course)->withActivities()->create(['order' => 1, 'title' => 'درس دوم']);
        $second->prerequisites()->attach($first);

        $this->actingAs($user);

        $this->get(route('home'))->assertOk()->assertSee('هنوز دوره‌ای برنداشتی');
        $this->get(route('courses.index'))->assertOk()->assertSee('دوره‌ی آزمایشی')->assertSee('۲ درس');
        $this->get(route('courses.show', $course))->assertOk()->assertSee('برداشتن دوره')->assertSee('درس اول');
        $this->get(route('lessons.show', [$course, $second]))->assertOk()->assertSee('درس اول')->assertSee('تمرین کد')->assertSee('<h2>مقدمه</h2>', false);

        $enrollment = Enrollment::factory()->for($user)->for($course)->create();
        $this->get(route('home'))->assertOk()->assertSee('دوره‌ی آزمایشی')->assertSee('بدون زمان روزانه');
        $this->get(route('courses.show', $course))->assertOk()->assertSee('زمان‌بندی')->assertSee('نیاز به درس اول');
        $this->get(route('lessons.show', [$course, $second]))->assertOk()->assertSee('باید حداقل «آشنا» بشه');
        $this->get(route('enrollments.edit', $enrollment))->assertOk();
        $this->get(route('profile.edit'))->assertOk();
    }

    public function test_lesson_urls_are_nested_under_the_course_and_continue_picks_the_first_unfamiliar_lesson(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['slug' => 'demo']);
        $first = Lesson::factory()->for($course)->withActivities()->create(['order' => 0, 'slug' => 'one', 'title' => 'درس اول']);
        $second = Lesson::factory()->for($course)->withActivities()->create(['order' => 1, 'slug' => 'two', 'title' => 'درس دوم']);
        $other = Lesson::factory()->for(Course::factory()->create(['slug' => 'other']))->create(['slug' => 'one']);

        $this->actingAs($user);

        $this->assertSame(url('/courses/demo/lessons/two'), $second->url());
        $this->get('/courses/demo/lessons/two')->assertOk()->assertSee('درس دوم')->assertSee('درس اول'); // sidebar lists every lesson
        $this->get('/courses/demo/lessons/nope')->assertNotFound();
        $this->get('/courses/other/lessons/two')->assertNotFound(); // scoped to the course
        $this->get("/lessons/{$second->id}")->assertRedirect($second->url()); // old bookmarks

        $this->get(route('courses.learn', $course))->assertRedirect($first->url());
        MasteryRecord::create(['user_id' => $user->id, 'lesson_id' => $first->id, 'level' => 'familiar', 'numeric_mastery' => 500]);
        $this->get(route('courses.learn', $course))->assertRedirect($second->url());
    }

    public function test_validation_errors_are_in_persian(): void
    {
        $enrollment = Enrollment::factory()->create();

        $this->actingAs($enrollment->user)
            ->from(route('enrollments.edit', $enrollment))
            ->put(route('enrollments.update', $enrollment), ['priority' => 9, 'status' => 'active'])
            ->assertSessionHasErrors(['priority' => 'اولویت نباید بزرگ‌تر از 5 باشد.']);
    }
}
