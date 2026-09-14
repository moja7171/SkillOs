<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Attempt;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\MasteryRecord;
use App\Models\PlanItem;
use App\Models\User;
use App\Services\Ai\GeminiClient;
use App\Services\Mastery\MasteryService;
use App\Services\Planning\Planner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlannerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Course $course;

    /** @var array<int, Lesson> */
    private array $lessons = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->course = Course::factory()->create();
        // Three sequential lessons: learn 8m + one 8m practice each (LessonFactory::withActivities).
        foreach ([0, 1, 2] as $i) {
            $this->lessons[$i] = Lesson::factory()->for($this->course)->withActivities()->create(['order' => $i, 'title' => "درس $i"]);
            if ($i > 0) {
                $this->lessons[$i]->prerequisites()->attach($this->lessons[$i - 1]);
            }
        }
    }

    private function mastery(Lesson $lesson, int $numeric, ?string $reviewDue = null): MasteryRecord
    {
        return MasteryRecord::create([
            'user_id' => $this->user->id, 'lesson_id' => $lesson->id,
            'numeric_mastery' => $numeric, 'level' => MasteryService::levelFor($numeric),
            'next_review_due_at' => $reviewDue ? now()->parse($reviewDue) : null,
        ]);
    }

    private function completedLearn(Lesson $lesson): void
    {
        Attempt::create(['activity_id' => $lesson->learnActivity->id, 'user_id' => $this->user->id, 'result_status' => 'completed', 'completed_at' => now()]);
    }

    public function test_fresh_course_plans_learn_then_practice_of_the_first_lesson(): void
    {
        Enrollment::factory()->for($this->user)->for($this->course)->scheduled(30)->create();

        $items = app(Planner::class)->today($this->user);

        $this->assertSame(
            [[$this->lessons[0]->learnActivity->id, 'plan', 'شروع درس'], [$this->lessons[0]->practices()->first()->id, 'plan', 'تمرین درس فعلی']],
            $items->map(fn (PlanItem $i) => [$i->activity_id, $i->source, $i->reason])->all(),
        );
        $this->assertSame([8, 8], $items->pluck('duration_minutes')->all());
        $this->assertTrue($items->every(fn (PlanItem $i) => $i->scheduled_for->isToday()));

        // Second call the same day reads the same rows.
        $this->assertSame($items->pluck('id')->all(), app(Planner::class)->today($this->user)->pluck('id')->all());
    }

    public function test_budget_is_filled_in_order_without_splitting_activities(): void
    {
        Enrollment::factory()->for($this->user)->for($this->course)->scheduled(5)->create();

        // 5 minutes: learn (8m) fits only via the half-budget rule (5 >= 4), then nothing else.
        $items = app(Planner::class)->today($this->user);

        $this->assertCount(1, $items);
        $this->assertSame($this->lessons[0]->learnActivity->id, $items->first()->activity_id);
    }

    public function test_due_reviews_come_first_and_the_current_lesson_advances_past_familiar_prerequisites(): void
    {
        Enrollment::factory()->for($this->user)->for($this->course)->scheduled(30)->create();
        $this->mastery($this->lessons[0], 600, '-1 day');       // proficient, review overdue
        $this->completedLearn($this->lessons[0]);

        $items = app(Planner::class)->today($this->user);

        $this->assertSame('review', $items[0]->source);
        $this->assertSame($this->lessons[0]->id, $items[0]->activity->lesson_id);
        // Lesson 0 is proficient → current is lesson 1 (its prerequisite is ≥ familiar), unlearned → learn + practice.
        $this->assertSame([$this->lessons[1]->learnActivity->id, $this->lessons[1]->practices()->first()->id], $items->skip(1)->pluck('activity_id')->all());
        $this->assertSame(['شروع درس', 'تمرین درس فعلی'], $items->skip(1)->pluck('reason')->all());
    }

    public function test_review_of_the_current_lesson_replaces_its_practice(): void
    {
        Enrollment::factory()->for($this->user)->for($this->course)->scheduled(30)->create();
        $this->mastery($this->lessons[0], 300, 'yesterday');    // familiar, still current, review due
        $this->completedLearn($this->lessons[0]);

        $items = app(Planner::class)->today($this->user);

        $this->assertCount(1, $items);
        $this->assertSame('review', $items[0]->source);
    }

    public function test_review_generates_a_new_practice_once_the_pool_is_exhausted(): void
    {
        Enrollment::factory()->for($this->user)->for($this->course)->scheduled(30)->create();
        $this->mastery($this->lessons[0], 300, 'yesterday');
        $this->completedLearn($this->lessons[0]);
        $practice = $this->lessons[0]->practices()->first();
        Attempt::create(['activity_id' => $practice->id, 'user_id' => $this->user->id, 'result_status' => 'correct', 'completed_at' => now()]);

        $this->mock(GeminiClient::class)->shouldReceive('generateJson')->once()->andReturn([
            'title' => 'تمرین تازه', 'form' => 'short_answer', 'difficulty' => 'core',
            'prompt' => 'سوال جدید', 'expected_outcome' => 'x', 'rubric' => 'y', 'hints' => ['h1', 'h2'],
        ]);

        $items = app(Planner::class)->today($this->user);

        $this->assertCount(1, $items);
        $this->assertSame('review', $items[0]->source);
        $generated = $this->lessons[0]->activities()->where('generated', true)->sole();
        $this->assertSame($generated->id, $items[0]->activity_id, 'the fresh, never-attempted practice is picked first');
    }

    public function test_review_falls_back_to_the_normal_pool_when_generation_fails(): void
    {
        Enrollment::factory()->for($this->user)->for($this->course)->scheduled(30)->create();
        $this->mastery($this->lessons[0], 300, 'yesterday');
        $this->completedLearn($this->lessons[0]);
        $practice = $this->lessons[0]->practices()->first();
        Attempt::create(['activity_id' => $practice->id, 'user_id' => $this->user->id, 'result_status' => 'correct', 'completed_at' => now()]);

        $this->mock(GeminiClient::class)->shouldReceive('generateJson')->once()->andThrow(new \RuntimeException('no api key'));

        $items = app(Planner::class)->today($this->user);

        $this->assertCount(1, $items);
        $this->assertSame('review', $items[0]->source);
        $this->assertSame($practice->id, $items[0]->activity_id);
        $this->assertSame(0, Activity::where('generated', true)->count());
    }

    public function test_overdue_reviews_are_capped_per_course(): void
    {
        Enrollment::factory()->for($this->user)->for($this->course)->scheduled(120)->create();
        $extra = Lesson::factory()->for($this->course)->withActivities()->create(['order' => 3]);
        foreach ([...$this->lessons, $extra] as $lesson) {
            $this->mastery($lesson, 900, '-10 days');
        }

        $items = app(Planner::class)->today($this->user);

        $this->assertSame(Planner::MAX_REVIEWS_PER_COURSE, $items->where('source', 'review')->count());
        $this->assertSame(0, $items->where('source', 'plan')->count(), 'all lessons mastered: nothing to advance');
    }

    public function test_three_consecutive_failures_schedule_a_relearn(): void
    {
        Enrollment::factory()->for($this->user)->for($this->course)->scheduled(30)->create();
        $this->mastery($this->lessons[0], 100);
        $this->completedLearn($this->lessons[0]);
        $practice = $this->lessons[0]->practices()->first();
        foreach (range(1, 3) as $_) {
            Attempt::create(['activity_id' => $practice->id, 'user_id' => $this->user->id, 'result_status' => 'incorrect']);
        }

        $items = app(Planner::class)->today($this->user);

        $this->assertSame('یادگیری دوباره بعد از سه اشتباه', $items[0]->reason);
        $this->assertSame($this->lessons[0]->learnActivity->id, $items[0]->activity_id);
    }

    public function test_all_proficient_with_no_reviews_keeps_the_weakest_sharp(): void
    {
        Enrollment::factory()->for($this->user)->for($this->course)->scheduled(30)->create();
        $this->mastery($this->lessons[0], 900, '+5 days');
        $this->mastery($this->lessons[1], 520, '+5 days');
        $this->mastery($this->lessons[2], 700, '+5 days');

        $items = app(Planner::class)->today($this->user);

        $this->assertCount(1, $items);
        $this->assertSame('حفظ آمادگی', $items[0]->reason);
        $this->assertSame($this->lessons[1]->id, $items[0]->activity->lesson_id);
    }

    public function test_unscheduled_paused_and_missing_daily_time_enrollments_get_no_plan(): void
    {
        Enrollment::factory()->for($this->user)->for($this->course)->create(['daily_time_minutes' => null]);
        Enrollment::factory()->for($this->user)->for(Course::factory()->create())->scheduled(30)->create(['status' => 'paused']);

        $this->assertCount(0, app(Planner::class)->today($this->user));
    }

    public function test_continue_learning_orders_reviews_first_then_priority_and_offers_alternatives(): void
    {
        $low = Enrollment::factory()->for($this->user)->for($this->course)->scheduled(30)->create(['priority' => 4]);
        $otherCourse = Course::factory()->create();
        $otherLesson = Lesson::factory()->for($otherCourse)->withActivities()->create();
        $high = Enrollment::factory()->for($this->user)->for($otherCourse)->scheduled(30)->create(['priority' => 1]);
        $this->mastery($this->lessons[0], 300, 'yesterday');
        $this->completedLearn($this->lessons[0]);

        $plan = app(Planner::class)->continueLearning($this->user);

        $this->assertSame('review', $plan['primary']->source, 'due review beats a higher-priority course');
        $this->assertSame($low->id, $plan['primary']->enrollment_id);
        $this->assertSame($high->id, $plan['alternatives'][0]->enrollment_id);
        $this->assertCount(1, $plan['alternatives'], 'the review lesson has no second practice to offer freely');
    }

    public function test_planned_attempt_completes_its_item_but_free_attempt_does_not(): void
    {
        Enrollment::factory()->for($this->user)->for($this->course)->scheduled(30)->create();
        $items = app(Planner::class)->today($this->user);
        $learnItem = $items->firstWhere('source', 'plan');

        // Free launch of the same activity from the lesson page: the item stays open.
        $this->actingAs($this->user)->post(route('session.start', $learnItem->activity));
        $free = Attempt::latest('id')->first();
        $this->actingAs($this->user)->post(route('session.complete', $free));
        $this->assertSame('scheduled', $learnItem->fresh()->status);
        $this->assertSame('free', $free->fresh()->evidence['source']);

        // Launch from the plan: completes it and the home page shows it ticked.
        $this->actingAs($this->user)->post(route('session.start-planned', $learnItem))->assertRedirect();
        $planned = Attempt::latest('id')->first();
        $this->assertSame('plan', $planned->evidence['source']);
        $this->actingAs($this->user)->post(route('session.complete', $planned));
        $this->assertSame('completed', $learnItem->fresh()->status);

        $this->actingAs($this->user)->post(route('session.start-planned', $learnItem))->assertStatus(422);
        $this->actingAs($this->user)->get(route('home'))->assertOk()->assertSee('۱ از ۲ انجام شده');
    }

    public function test_review_launched_from_plan_is_review_evidence(): void
    {
        Enrollment::factory()->for($this->user)->for($this->course)->scheduled(30)->create();
        $this->mastery($this->lessons[0], 300, 'yesterday');
        $this->completedLearn($this->lessons[0]);
        $review = app(Planner::class)->today($this->user)->firstWhere('source', 'review');
        $this->mock(GeminiClient::class)->shouldReceive('generateJson')->once()->andReturn(['verdict' => 'correct', 'feedback' => 'خوب']);

        $this->actingAs($this->user)->post(route('session.start-planned', $review));
        $attempt = Attempt::latest('id')->first();
        $this->actingAs($this->user)->post(route('session.submit', $attempt), ['response' => 'x']);

        $this->assertSame('review', $attempt->fresh()->evidence['source']);
        $this->assertSame(420, MasteryRecord::where('lesson_id', $this->lessons[0]->id)->sole()->numeric_mastery, '300 + 120 review success');
        $this->assertSame('completed', $review->fresh()->status);
    }

    public function test_config_change_recomputes_open_items_and_keeps_completed_ones(): void
    {
        $enrollment = Enrollment::factory()->for($this->user)->for($this->course)->scheduled(30)->create();
        $items = app(Planner::class)->today($this->user);
        $items[0]->update(['status' => 'completed']);

        $this->actingAs($this->user)->put(route('enrollments.update', $enrollment), ['priority' => 2, 'daily_time_minutes' => 5, 'status' => 'active']);

        $today = app(Planner::class)->today($this->user);
        $this->assertTrue($today->contains('id', $items[0]->id), 'completed item is kept');
        $this->assertCount(1, $today, '5-minute budget already spent on nothing new: the practice (8m) no longer fits');

        $this->actingAs($this->user)->put(route('enrollments.update', $enrollment), ['priority' => 2, 'daily_time_minutes' => 30, 'status' => 'paused']);
        $this->assertCount(1, app(Planner::class)->today($this->user), 'paused: open items removed, completed kept');
    }

    public function test_get_on_action_urls_redirects_instead_of_405(): void
    {
        Enrollment::factory()->for($this->user)->for($this->course)->scheduled(30)->create();
        $item = app(Planner::class)->today($this->user)->first();

        // No open attempt yet: home.
        $this->actingAs($this->user)->get("/plan-items/{$item->id}/start")->assertRedirect(route('home'));

        // With an open attempt: resume it.
        $this->actingAs($this->user)->post(route('session.start-planned', $item));
        $attempt = Attempt::latest('id')->first();
        $this->actingAs($this->user)->get("/plan-items/{$item->id}/start")->assertRedirect(route('session.show', $attempt));
        $this->actingAs($this->user)->get("/activities/{$item->activity_id}/start")->assertRedirect(route('session.show', $attempt));

        // Any other POST-only URL: back to the referrer with a note, never a 405 page.
        $this->actingAs($this->user)
            ->from(route('home'))
            ->get("/plan-items/{$item->id}/skip")
            ->assertRedirect(route('home'))
            ->assertSessionHas('status');
    }

    public function test_stranger_cannot_start_someone_elses_plan_item(): void
    {
        Enrollment::factory()->for($this->user)->for($this->course)->scheduled(30)->create();
        $item = app(Planner::class)->today($this->user)->first();

        $this->actingAs(User::factory()->create())->post(route('session.start-planned', $item))->assertForbidden();
        $this->assertSame('scheduled', $item->fresh()->status);
    }
}
