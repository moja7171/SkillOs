<?php

namespace Tests\Feature;

use App\Models\Attempt;
use App\Models\Lesson;
use App\Models\MasteryRecord;
use App\Models\User;
use App\Models\VideoView;
use App\Services\Mastery\MasteryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonMarkDoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_mark_done_is_rejected_until_every_practice_is_passed(): void
    {
        $user = User::factory()->create();
        $lesson = Lesson::factory()->withActivities()->create();
        $practice = $lesson->practices()->first();

        $this->actingAs($user)->get($lesson->url())->assertOk()
            ->assertSee('اول همه‌ی تمرین‌های بالا رو درست جواب بده');

        $this->actingAs($user)->post(route('lessons.mark-done', $lesson))->assertStatus(422);

        Attempt::create(['activity_id' => $practice->id, 'user_id' => $user->id, 'result_status' => 'incorrect']);
        $this->actingAs($user)->post(route('lessons.mark-done', $lesson))->assertStatus(422);
    }

    public function test_mark_done_succeeds_once_practices_are_passed_and_fills_the_circle(): void
    {
        $user = User::factory()->create();
        $lesson = Lesson::factory()->withActivities()->create();
        $practice = $lesson->practices()->first();

        Attempt::create(['activity_id' => $practice->id, 'user_id' => $user->id, 'result_status' => 'correct']);

        $this->actingAs($user)->get($lesson->url())->assertOk()
            ->assertDontSee('اول همه‌ی تمرین‌های بالا رو درست جواب بده');

        $this->actingAs($user)->post(route('lessons.mark-done', $lesson))
            ->assertRedirect($lesson->url());

        $done = Attempt::where('user_id', $user->id)
            ->where('activity_id', $lesson->learnActivity->id)
            ->where('evidence->source', 'lesson_done')
            ->sole();
        $this->assertSame('completed', $done->result_status);

        $this->actingAs($user)->get($lesson->url())->assertOk()->assertSee('این درس رو انجام دادی');
    }

    public function test_mark_done_is_rejected_until_every_video_is_watched(): void
    {
        $user = User::factory()->create();
        $lesson = Lesson::factory()->withActivities()->create();
        $practice = $lesson->practices()->first();
        Attempt::create(['activity_id' => $practice->id, 'user_id' => $user->id, 'result_status' => 'correct']);

        $video1 = $lesson->videos()->create(['order' => 0, 'title' => 'قسمت ۱', 'url' => 'https://example.com/a.mp4']);
        $video2 = $lesson->videos()->create(['order' => 1, 'title' => 'قسمت ۲', 'url' => 'https://example.com/b.mp4']);

        // Practices alone aren't enough once the lesson has videos.
        $this->actingAs($user)->post(route('lessons.mark-done', $lesson))->assertStatus(422);

        $this->actingAs($user)->post(route('lesson-videos.watched', $video1))->assertOk();
        $this->actingAs($user)->post(route('lessons.mark-done', $lesson))->assertStatus(422);

        $this->actingAs($user)->post(route('lesson-videos.watched', $video2))->assertOk();
        $this->actingAs($user)->post(route('lessons.mark-done', $lesson))->assertRedirect($lesson->url());
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

        // The sidebar circle (and course-page checkmark) should still show this lesson
        // as done, even though its live mastery level is now below "familiar".
        $this->actingAs($user)->get($lesson->url())->assertOk()->assertSee('این درس رو انجام دادی');
        $this->actingAs($user)->get(route('courses.show', $lesson->course))->assertOk()->assertSee('این درس رو انجام دادی');
    }
}
