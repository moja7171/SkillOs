<?php

namespace Tests\Feature;

use App\Models\Attempt;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\MasteryRecord;
use App\Models\PlanItem;
use App\Models\User;
use App\Services\Mastery\MasteryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_todays_plan_is_one_merged_queue_with_reviews_first_and_one_start_button(): void
    {
        $user = User::factory()->create();

        // The lower-priority course is created first (smaller plan-item ids) so a naive
        // by-id or by-course order would put its items first.
        $lowCourse = Course::factory()->create(['title' => 'دوره‌ی آ']);
        $lowLesson = Lesson::factory()->for($lowCourse)->withActivities()->create();
        Enrollment::factory()->for($user)->for($lowCourse)->scheduled(30)->create(['priority' => 2]);
        MasteryRecord::create(['user_id' => $user->id, 'lesson_id' => $lowLesson->id, 'numeric_mastery' => 300, 'level' => MasteryService::levelFor(300), 'next_review_due_at' => now()->subDay()]);

        $highCourse = Course::factory()->create(['title' => 'دوره‌ی ب']);
        $highLesson = Lesson::factory()->for($highCourse)->withActivities()->create();
        Enrollment::factory()->for($user)->for($highCourse)->scheduled(30)->create(['priority' => 1]);
        MasteryRecord::create(['user_id' => $user->id, 'lesson_id' => $highLesson->id, 'numeric_mastery' => 300, 'level' => MasteryService::levelFor(300), 'next_review_due_at' => now()->subDay()]);

        $response = $this->actingAs($user)->get(route('home'))->assertOk()
            ->assertSee('شروع امروز')
            ->assertSee('دوره‌ی آ')
            ->assertSee('دوره‌ی ب');

        $highReview = PlanItem::where('source', 'review')->whereHas('activity.lesson', fn ($q) => $q->where('id', $highLesson->id))->sole();
        $lowReview = PlanItem::where('source', 'review')->whereHas('activity.lesson', fn ($q) => $q->where('id', $lowLesson->id))->sole();

        // The big button and the first queue row start the same item: the higher-priority review.
        $startUrls = [];
        preg_match_all('#action="([^"]*plan-items/\d+/start)"#', $response->getContent(), $startUrls);
        $this->assertSame(route('session.start-planned', $highReview), $startUrls[1][0]);
        $this->assertSame(route('session.start-planned', $highReview), $startUrls[1][1]);
        $this->assertSame(route('session.start-planned', $lowReview), $startUrls[1][2]);
    }

    public function test_remaining_count_ignores_skipped_items_and_progress_counts_only_planned_work(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        Lesson::factory()->for($course)->withActivities()->create();
        Enrollment::factory()->for($user)->for($course)->scheduled(30)->create();

        $this->actingAs($user)->get(route('home'))->assertOk()->assertSee('۲ کار مونده')->assertSee('۰ از ۲');

        $item = PlanItem::where('source', 'plan')->orderBy('id')->first();
        $this->actingAs($user)->post(route('plan-items.skip', $item))->assertRedirect(route('home'));

        $this->actingAs($user)->get(route('home'))->assertOk()->assertSee('۱ کار مونده')->assertSee('۰ از ۱');
    }

    public function test_finishing_everything_shows_the_done_state_with_tomorrows_reviews(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $lesson = Lesson::factory()->for($course)->withActivities()->create();
        Enrollment::factory()->for($user)->for($course)->scheduled(30)->create();
        MasteryRecord::create(['user_id' => $user->id, 'lesson_id' => $lesson->id, 'numeric_mastery' => 300, 'level' => MasteryService::levelFor(300), 'next_review_due_at' => today()->addDay()]);

        $this->actingAs($user)->get(route('home'))->assertOk();
        PlanItem::query()->update(['status' => 'completed']);

        $this->actingAs($user)->get(route('home'))->assertOk()
            ->assertSee('امروز تموم شد')
            ->assertSee('فردا ۱ مرور منتظرته')
            ->assertDontSee('شروع امروز')
            ->assertSee(route('courses.learn', $course));
    }

    public function test_a_streak_broken_by_a_long_gap_is_not_shown_but_the_welcome_back_line_is(): void
    {
        $user = User::factory()->create(['streak_count' => 10, 'streak_last_date' => today()->subDays(20)]);
        $course = Course::factory()->create();
        Lesson::factory()->for($course)->withActivities()->create();
        Enrollment::factory()->for($user)->for($course)->scheduled(30)->create();

        $this->actingAs($user)->get(route('home'))->assertOk()
            ->assertDontSee('۱۰ روز')
            ->assertSee('خوش برگشتی');
    }

    public function test_a_live_streak_is_shown_as_a_chip(): void
    {
        $user = User::factory()->create(['streak_count' => 4, 'streak_last_date' => today()->subDay()]);

        $this->actingAs($user)->get(route('home'))->assertOk()->assertSee('۴ روز');
    }

    public function test_an_active_course_without_daily_time_is_called_out_next_to_the_plan(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['title' => 'دوره‌ی بی‌زمان']);
        Lesson::factory()->for($course)->withActivities()->create();
        $enrollment = Enrollment::factory()->for($user)->for($course)->create(['daily_time_minutes' => null]);

        $this->actingAs($user)->get(route('home'))->assertOk()
            ->assertSee('برای «دوره‌ی بی‌زمان» زمان روزانه تعیین نکردی')
            ->assertSee(route('enrollments.edit', $enrollment))
            ->assertDontSee('شروع امروز');
    }

    public function test_without_any_course_home_invites_picking_one(): void
    {
        $this->actingAs(User::factory()->create())->get(route('home'))->assertOk()
            ->assertSee('اولین دوره‌ت رو انتخاب کن')
            ->assertDontSee('شروع امروز');
    }

    public function test_finished_lessons_with_pending_practices_appear_in_the_backlog_until_passed(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $lesson = Lesson::factory()->for($course)->withActivities()->create(['title' => 'درس عقب‌افتاده']);
        Enrollment::factory()->for($user)->for($course)->scheduled(30)->create();

        $this->actingAs($user)->get(route('home'))->assertOk()->assertDontSee('تمرین‌های عقب‌افتاده');

        // Practices passed but lesson not finished: not a backlog item.
        $practice = $lesson->practices()->first();
        Attempt::create(['activity_id' => $practice->id, 'user_id' => $user->id, 'result_status' => 'correct']);
        $this->actingAs($user)->get(route('home'))->assertOk()->assertDontSee('تمرین‌های عقب‌افتاده');

        Attempt::where('activity_id', $practice->id)->delete();
        $this->actingAs($user)->post(route('lessons.mark-done', $lesson));
        $this->actingAs($user)->get(route('home'))->assertOk()
            ->assertSee('تمرین‌های عقب‌افتاده')->assertSee('درس عقب‌افتاده');

        Attempt::create(['activity_id' => $practice->id, 'user_id' => $user->id, 'result_status' => 'correct']);
        $this->actingAs($user)->get(route('home'))->assertOk()->assertDontSee('تمرین‌های عقب‌افتاده');
    }
}
