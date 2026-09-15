<?php

namespace Tests\Feature;

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

    public function test_a_second_due_review_from_a_lower_priority_course_gets_a_quick_shortcut(): void
    {
        $user = User::factory()->create();

        // Created first (so its plan item gets the smaller id) but LOWER priority —
        // this is what makes the test meaningful: a naive "first scheduled review by
        // id" pick would surface this one, while the correct priority-ordered pick
        // (matching $primary) surfaces the other course's review instead.
        $lowPriorityCourse = Course::factory()->create();
        $lowPriorityLesson = Lesson::factory()->for($lowPriorityCourse)->withActivities()->create();
        Enrollment::factory()->for($user)->for($lowPriorityCourse)->scheduled(30)->create(['priority' => 2]);
        MasteryRecord::create(['user_id' => $user->id, 'lesson_id' => $lowPriorityLesson->id, 'numeric_mastery' => 300, 'level' => MasteryService::levelFor(300), 'next_review_due_at' => now()->subDay()]);

        $highPriorityCourse = Course::factory()->create();
        $highPriorityLesson = Lesson::factory()->for($highPriorityCourse)->withActivities()->create();
        Enrollment::factory()->for($user)->for($highPriorityCourse)->scheduled(30)->create(['priority' => 1]);
        MasteryRecord::create(['user_id' => $user->id, 'lesson_id' => $highPriorityLesson->id, 'numeric_mastery' => 300, 'level' => MasteryService::levelFor(300), 'next_review_due_at' => now()->subDay()]);

        $response = $this->actingAs($user)->get(route('home'))->assertOk();

        $highItem = PlanItem::whereHas('activity.lesson', fn ($q) => $q->where('id', $highPriorityLesson->id))->sole();
        $lowItem = PlanItem::whereHas('activity.lesson', fn ($q) => $q->where('id', $lowPriorityLesson->id))->sole();

        $response->assertSee(route('session.start-planned', $highItem))
            ->assertSee('فقط یه مرور سریع')
            ->assertSee(route('session.start-planned', $lowItem));
    }

    public function test_todays_list_is_grouped_by_course_with_a_visible_header_each(): void
    {
        $user = User::factory()->create();

        $courseA = Course::factory()->create(['title' => 'دوره‌ی آ']);
        Lesson::factory()->for($courseA)->withActivities()->create();
        Enrollment::factory()->for($user)->for($courseA)->scheduled(30)->create();

        $courseB = Course::factory()->create(['title' => 'دوره‌ی ب']);
        Lesson::factory()->for($courseB)->withActivities()->create();
        Enrollment::factory()->for($user)->for($courseB)->scheduled(30)->create();

        $this->actingAs($user)->get(route('home'))->assertOk()
            ->assertSee('دوره‌ی آ')
            ->assertSee('دوره‌ی ب')
            ->assertSee('۰ از ۲');
    }

    public function test_no_shortcut_when_the_only_due_review_is_already_primary(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $lesson = Lesson::factory()->for($course)->withActivities()->create();
        Enrollment::factory()->for($user)->for($course)->scheduled(30)->create();
        MasteryRecord::create(['user_id' => $user->id, 'lesson_id' => $lesson->id, 'numeric_mastery' => 300, 'level' => MasteryService::levelFor(300), 'next_review_due_at' => now()->subDay()]);

        $this->actingAs($user)->get(route('home'))->assertOk()->assertDontSee('فقط یه مرور سریع');
    }
}
