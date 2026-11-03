<?php

namespace Tests\Feature;

use App\Models\Attempt;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\MasteryRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_my_courses_come_first_latest_progress_on_top_then_the_rest_of_the_catalog(): void
    {
        $user = User::factory()->create();
        $older = Course::factory()->create(['title' => 'دوره‌ی قدیمی']);
        $newer = Course::factory()->create(['title' => 'دوره‌ی تازه']);
        $notMine = Course::factory()->create(['title' => 'دوره‌ی برنداشته']);
        Enrollment::factory()->for($user)->for($older)->create(['last_activity_at' => now()->subDays(3)]);
        Enrollment::factory()->for($user)->for($newer)->create(['last_activity_at' => now()->subHour()]);

        $html = $this->actingAs($user)->get(route('home'))->assertOk()
            ->assertSeeInOrder(['دوره‌های من', 'دوره‌ی تازه', 'دوره‌ی قدیمی', 'بقیه‌ی دوره‌ها', 'دوره‌ی برنداشته'])
            ->getContent();

        $this->assertSame(1, substr_count($html, 'دوره‌ی برنداشته'));
    }

    public function test_a_course_card_continues_at_the_first_lesson_not_done(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['slug' => 'demo']);
        $first = Lesson::factory()->for($course)->withActivities()->create(['order' => 0, 'title' => 'درس اول']);
        Lesson::factory()->for($course)->withActivities()->create(['order' => 1, 'title' => 'درس دوم']);
        Enrollment::factory()->for($user)->for($course)->create();
        $this->actingAs($user)->post(route('lessons.mark-done', $first));

        $this->get(route('home'))->assertOk()
            ->assertSee('درس ۲ از ۲')
            ->assertSee('درس دوم')
            ->assertSee(route('courses.learn', $course), false)
            ->assertSee('۵۰٪');
    }

    public function test_due_reviews_show_a_count_by_course_and_a_start_button(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['title' => 'دوره‌ی مرور']);
        $lessons = Lesson::factory()->count(3)->for($course)->withActivities()->create();
        Enrollment::factory()->for($user)->for($course)->create();
        foreach ($lessons as $i => $lesson) {
            MasteryRecord::create(['user_id' => $user->id, 'lesson_id' => $lesson->id, 'numeric_mastery' => 300, 'level' => 'familiar', 'next_review_due_at' => $i === 2 ? now()->addDay() : now()->subDay()]);
        }

        $this->actingAs($user)->get(route('home'))->assertOk()
            ->assertSee('۲')
            ->assertSee('مرور سررسیده')
            ->assertSee(route('reviews.start'), false);
    }

    public function test_a_finished_review_day_shows_the_done_state_and_tomorrows_count(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $lesson = Lesson::factory()->for($course)->withActivities()->create();
        Enrollment::factory()->for($user)->for($course)->create();
        MasteryRecord::create(['user_id' => $user->id, 'lesson_id' => $lesson->id, 'numeric_mastery' => 300, 'level' => 'familiar', 'next_review_due_at' => today()->addDay()]);
        Attempt::create(['activity_id' => $lesson->practices()->first()->id, 'user_id' => $user->id, 'result_status' => 'correct', 'completed_at' => now(), 'evidence' => ['source' => 'review']]);

        $this->actingAs($user)->get(route('home'))->assertOk()
            ->assertSee('مرور امروز تموم شد')
            ->assertSee('فردا ۱ مرور منتظرته.')
            ->assertDontSee(route('reviews.start'), false);
    }

    public function test_without_any_course_home_invites_picking_one(): void
    {
        $this->actingAs(User::factory()->create())->get(route('home'))->assertOk()
            ->assertSee('هنوز دوره‌ای برنداشتی');
    }

    public function test_a_streak_broken_by_a_long_gap_is_not_shown(): void
    {
        $user = User::factory()->create(['streak_count' => 9, 'streak_last_date' => today()->subDays(5)]);

        $this->actingAs($user)->get(route('home'))->assertOk()
            ->assertDontSee('۹ روز')
            ->assertSee('استریک');
    }

    public function test_a_live_streak_is_shown(): void
    {
        $user = User::factory()->create(['streak_count' => 4, 'streak_last_date' => today()->subDay()]);

        $this->actingAs($user)->get(route('home'))->assertOk()->assertSee('۴ روز');
    }
}
