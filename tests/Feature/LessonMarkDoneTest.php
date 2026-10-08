<?php

namespace Tests\Feature;

use App\Models\Attempt;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\MasteryRecord;
use App\Models\PlanItem;
use App\Models\User;
use App\Models\VideoView;
use App\Services\Mastery\MasteryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonMarkDoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_mark_done_does_not_wait_for_practices_on_a_lesson_without_videos(): void
    {
        $user = User::factory()->create();
        $lesson = Lesson::factory()->withActivities()->create();

        $this->actingAs($user)->post(route('lessons.mark-done', $lesson))
            ->assertRedirect($lesson->url());

        $done = Attempt::where('user_id', $user->id)
            ->where('activity_id', $lesson->learnActivity->id)
            ->where('evidence->source', 'lesson_done')
            ->sole();
        $this->assertSame('completed', $done->result_status);

        $this->actingAs($user)->get($lesson->url())->assertOk()
            ->assertSee('این درس رو انجام دادی')
            ->assertSee('تمرین‌هاش هنوز مونده');
        $this->assertFalse($lesson->allPracticesPassedBy($user));
    }

    public function test_mark_done_is_rejected_for_a_lesson_with_unwatched_videos(): void
    {
        $user = User::factory()->create();
        $lesson = Lesson::factory()->withActivities()->create();
        $lesson->videos()->create(['order' => 0, 'url' => 'https://example.com/a.mp4']);

        $this->actingAs($user)->post(route('lessons.mark-done', $lesson))->assertStatus(422);
    }

    public function test_the_lesson_completes_by_itself_when_the_last_video_ends(): void
    {
        $user = User::factory()->create();
        $lesson = Lesson::factory()->withActivities()->create();
        $video1 = $lesson->videos()->create(['order' => 0, 'title' => 'قسمت ۱', 'url' => 'https://example.com/a.mp4']);
        $video2 = $lesson->videos()->create(['order' => 1, 'title' => 'قسمت ۲', 'url' => 'https://example.com/b.mp4']);

        $this->actingAs($user)->post(route('lesson-videos.watched', $video1))
            ->assertOk()->assertJson(['watched' => true, 'lesson_done' => false]);
        $this->assertFalse($lesson->isMarkedDoneBy($user));

        $this->actingAs($user)->post(route('lesson-videos.watched', $video2))
            ->assertOk()->assertJson(['watched' => true, 'lesson_done' => true]);
        $this->assertTrue($lesson->fresh()->isMarkedDoneBy($user));

        // Re-watching never records a second completion.
        $this->actingAs($user)->post(route('lesson-videos.watched', $video2))->assertJson(['lesson_done' => true]);
        $this->assertSame(1, Attempt::where('user_id', $user->id)->where('evidence->source', 'lesson_done')->count());
    }

    public function test_practice_progress_is_independent_of_the_lesson_item(): void
    {
        $user = User::factory()->create();
        $lesson = Lesson::factory()->withActivities()->create();
        $practice = $lesson->practices()->first();

        $this->assertSame(['passed' => 0, 'total' => 1], $lesson->practiceProgressFor($user));

        Attempt::create(['activity_id' => $practice->id, 'user_id' => $user->id, 'result_status' => 'incorrect']);
        $this->assertFalse($lesson->allPracticesPassedBy($user));

        Attempt::create(['activity_id' => $practice->id, 'user_id' => $user->id, 'result_status' => 'correct_with_hint']);
        $this->assertSame(['passed' => 1, 'total' => 1], $lesson->practiceProgressFor($user));
        $this->assertTrue($lesson->allPracticesPassedBy($user));
        $this->assertFalse($lesson->isMarkedDoneBy($user));
    }

    public function test_a_lesson_without_practices_has_no_practice_item(): void
    {
        $user = User::factory()->create();
        $lesson = Lesson::factory()->create();

        $this->assertSame(['passed' => 0, 'total' => 0], $lesson->practiceProgressFor($user));
        $this->assertFalse($lesson->allPracticesPassedBy($user));
    }

    public function test_watching_the_same_video_twice_does_not_duplicate_the_view_row(): void
    {
        $user = User::factory()->create();
        $lesson = Lesson::factory()->create();
        $video = $lesson->videos()->create(['order' => 0, 'url' => 'https://example.com/a.mp4']);

        $this->actingAs($user)->post(route('lesson-videos.watched', $video))->assertOk();
        $this->actingAs($user)->post(route('lesson-videos.watched', $video))->assertOk();

        $this->assertSame(1, VideoView::where('user_id', $user->id)->where('lesson_video_id', $video->id)->count());
    }

    public function test_the_done_mark_survives_a_later_review_that_drops_mastery_below_familiar(): void
    {
        $user = User::factory()->create();
        $lesson = Lesson::factory()->withActivities()->create();
        $practice = $lesson->practices()->first();

        Attempt::create(['activity_id' => $practice->id, 'user_id' => $user->id, 'result_status' => 'correct']);
        $this->actingAs($user)->post(route('lessons.mark-done', $lesson));

        // A failed review knocks numeric mastery back down — the record starts at 150
        // ("learning", from the single correct practice above) and a review failure
        // (-150) clamps it to 0, well below the "familiar" threshold (250).
        $record = MasteryRecord::where('user_id', $user->id)->where('lesson_id', $lesson->id)->sole();
        $this->assertSame('learning', $record->level);

        $review = Attempt::create(['activity_id' => $practice->id, 'user_id' => $user->id, 'result_status' => 'incorrect', 'evidence' => ['source' => 'review']]);
        app(MasteryService::class)->applyAttempt($review);

        $record->refresh();
        $this->assertSame('learning', $record->level);
        $this->assertLessThan(250, $record->numeric_mastery);

        $this->actingAs($user)->get($lesson->url())->assertOk()->assertSee('این درس رو انجام دادی');
        $this->actingAs($user)->get(route('courses.show', $lesson->course))->assertOk()->assertSee('این درس رو انجام دادی');
    }

    public function test_finishing_a_lesson_from_its_page_ticks_todays_open_plan_item(): void
    {
        $user = User::factory()->create();
        $lesson = Lesson::factory()->withActivities()->create();
        $enrollment = Enrollment::factory()->for($user)->for($lesson->course)->scheduled(30)->create();
        $video = $lesson->videos()->create(['order' => 0, 'url' => 'https://example.com/a.mp4']);

        $item = PlanItem::create([
            'user_id' => $user->id, 'enrollment_id' => $enrollment->id, 'activity_id' => $lesson->learnActivity->id,
            'scheduled_for' => today(), 'duration_minutes' => 10, 'status' => 'scheduled', 'source' => 'plan', 'reason' => 'شروع درس',
        ]);
        $yesterday = PlanItem::create([
            'user_id' => $user->id, 'enrollment_id' => $enrollment->id, 'activity_id' => $lesson->learnActivity->id,
            'scheduled_for' => today()->subDay(), 'duration_minutes' => 10, 'status' => 'scheduled', 'source' => 'plan', 'reason' => 'شروع درس',
        ]);

        $this->actingAs($user)->post(route('lesson-videos.watched', $video))->assertOk();

        $this->assertSame('completed', $item->fresh()->status);
        $this->assertSame('scheduled', $yesterday->fresh()->status);
    }
}
