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
use App\Services\Planning\Planner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LifecycleTest extends TestCase
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
        foreach ([0, 1] as $i) {
            $this->lessons[$i] = Lesson::factory()->for($this->course)->withActivities()->create(['order' => $i, 'title' => "درس $i"]);
        }
        $this->lessons[1]->prerequisites()->attach($this->lessons[0]);
    }

    private function mastery(Lesson $lesson, int $numeric, string $reviewDue): MasteryRecord
    {
        return MasteryRecord::create([
            'user_id' => $this->user->id, 'lesson_id' => $lesson->id,
            'numeric_mastery' => $numeric, 'level' => MasteryService::levelFor($numeric),
            'next_review_due_at' => now()->parse($reviewDue)->startOfDay(),
        ]);
    }

    public function test_skip_marks_the_item_and_continue_learning_moves_on(): void
    {
        Enrollment::factory()->for($this->user)->for($this->course)->scheduled(30)->create();
        $items = app(Planner::class)->today($this->user);
        $first = $items[0];

        $this->actingAs($this->user)->post(route('plan-items.skip', $first))->assertRedirect(route('home'));

        $this->assertSame('skipped', $first->fresh()->status);
        $this->assertSame($items[1]->id, app(Planner::class)->continueLearning($this->user)['primary']->id);
        $this->actingAs($this->user)->post(route('session.start-planned', $first))->assertStatus(422);
        $this->actingAs(User::factory()->create())->post(route('plan-items.skip', $items[1]))->assertForbidden();
    }

    public function test_maintenance_plans_only_due_reviews(): void
    {
        Enrollment::factory()->for($this->user)->for($this->course)->scheduled(30)->create(['status' => 'maintenance']);
        $this->mastery($this->lessons[0], 600, 'yesterday');
        Attempt::create(['activity_id' => $this->lessons[0]->learnActivity->id, 'user_id' => $this->user->id, 'result_status' => 'completed']);

        $items = app(Planner::class)->today($this->user);

        $this->assertCount(1, $items);
        $this->assertSame('review', $items[0]->source);

        // Same state as active would also plan lesson 1 (learn + practice).
        $this->user->enrollments()->update(['status' => 'active']);
        MasteryRecord::query()->delete();
        PlanItem::query()->delete();
        $this->mastery($this->lessons[0], 600, 'yesterday');
        $this->assertCount(3, app(Planner::class)->today($this->user));
    }

    public function test_pausing_removes_open_items_and_archiving_keeps_mastery(): void
    {
        $enrollment = Enrollment::factory()->for($this->user)->for($this->course)->scheduled(30)->create();
        $this->mastery($this->lessons[0], 300, '+3 days');
        app(Planner::class)->today($this->user);

        $this->actingAs($this->user)->put(route('enrollments.update', $enrollment), ['priority' => 3, 'daily_time_minutes' => 30, 'status' => 'archived']);

        $this->assertCount(0, app(Planner::class)->today($this->user));
        $this->assertSame(300, MasteryRecord::where('lesson_id', $this->lessons[0]->id)->sole()->numeric_mastery);
        $this->actingAs($this->user)->get(route('home'))->assertOk()->assertSee('بایگانی');
    }

    public function test_reactivation_after_a_long_gap_queues_reviews_for_learned_lessons(): void
    {
        $enrollment = Enrollment::factory()->for($this->user)->for($this->course)->scheduled(30)
            ->create(['status' => 'paused', 'last_activity_at' => now()->subDays(20)]);
        $familiar = $this->mastery($this->lessons[0], 300, '+10 days');
        $learning = $this->mastery($this->lessons[1], 100, '+10 days');

        $this->actingAs($this->user)
            ->put(route('enrollments.update', $enrollment), ['priority' => 3, 'daily_time_minutes' => 30, 'status' => 'active'])
            ->assertSessionHas('status', fn ($s) => str_contains($s, 'خوش برگشتی') && str_contains($s, '۱ درس'));

        $this->assertTrue($familiar->fresh()->next_review_due_at->isToday());
        $this->assertFalse($learning->fresh()->next_review_due_at->isToday(), 'lessons below familiar are not queued');
        $this->assertSame('review', app(Planner::class)->today($this->user)->first()->source);
    }

    public function test_reactivation_after_a_short_gap_changes_nothing(): void
    {
        $enrollment = Enrollment::factory()->for($this->user)->for($this->course)->scheduled(30)
            ->create(['status' => 'paused', 'last_activity_at' => now()->subDays(3)]);
        $record = $this->mastery($this->lessons[0], 300, '+10 days');

        $this->actingAs($this->user)->put(route('enrollments.update', $enrollment), ['priority' => 3, 'daily_time_minutes' => 30, 'status' => 'active'])
            ->assertSessionHas('status', 'زمان‌بندی ذخیره شد.');

        $this->assertFalse($record->fresh()->next_review_due_at->isToday());
    }

    public function test_week_view_shows_today_and_upcoming_reviews_on_their_days(): void
    {
        Enrollment::factory()->for($this->user)->for($this->course)->scheduled(30)->create();
        $this->mastery($this->lessons[0], 300, '+3 days');
        Attempt::create(['activity_id' => $this->lessons[0]->learnActivity->id, 'user_id' => $this->user->id, 'result_status' => 'completed']);

        $week = app(Planner::class)->week($this->user);

        $this->assertCount(7, $week);
        $this->assertTrue($week[0]['items']->isNotEmpty());
        $this->assertCount(1, $week[3]['reviews']);
        $this->assertSame($this->lessons[0]->id, $week[3]['reviews'][0]->lesson_id);
        $this->assertTrue($week[1]['reviews']->isEmpty());

        $this->actingAs($this->user)->get(route('week'))->assertOk()->assertSee('امروز ·')->assertSee('درس 0')->assertSee('مرور');
    }
}
