<?php

namespace Tests\Feature;

use App\Models\Attempt;
use App\Models\Lesson;
use App\Models\MasteryRecord;
use App\Models\User;
use App\Models\VideoView;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackfillLessonDoneTest extends TestCase
{
    use RefreshDatabase;

    private function runBackfill(): void
    {
        (require database_path('migrations/2026_10_03_120320_backfill_lesson_done_for_independent_items.php'))->up();
    }

    public function test_it_marks_lessons_done_for_learners_who_watched_everything_or_reached_familiar(): void
    {
        $watcher = User::factory()->create();
        $partial = User::factory()->create();
        $practiced = User::factory()->create();
        $untouched = User::factory()->create();

        $watched = Lesson::factory()->withActivities()->create();
        $video1 = $watched->videos()->create(['order' => 0, 'url' => 'https://example.com/a.mp4']);
        $video2 = $watched->videos()->create(['order' => 1, 'url' => 'https://example.com/b.mp4']);
        VideoView::create(['user_id' => $watcher->id, 'lesson_video_id' => $video1->id, 'watched_at' => now()]);
        VideoView::create(['user_id' => $watcher->id, 'lesson_video_id' => $video2->id, 'watched_at' => now()]);
        VideoView::create(['user_id' => $partial->id, 'lesson_video_id' => $video1->id, 'watched_at' => now()]);

        $mastered = Lesson::factory()->withActivities()->create();
        MasteryRecord::create(['user_id' => $practiced->id, 'lesson_id' => $mastered->id, 'numeric_mastery' => 300, 'level' => 'familiar']);

        $this->runBackfill();

        $this->assertTrue($watched->isMarkedDoneBy($watcher));
        $this->assertFalse($watched->isMarkedDoneBy($partial));
        $this->assertTrue($mastered->isMarkedDoneBy($practiced));
        $this->assertFalse($mastered->isMarkedDoneBy($untouched));
        $this->assertSame(2, Attempt::where('evidence->source', 'lesson_done')->count());
    }

    public function test_it_is_idempotent_and_leaves_mastery_alone(): void
    {
        $user = User::factory()->create();
        $lesson = Lesson::factory()->withActivities()->create();
        $record = MasteryRecord::create(['user_id' => $user->id, 'lesson_id' => $lesson->id, 'numeric_mastery' => 300, 'level' => 'familiar']);

        $this->runBackfill();
        $this->runBackfill();

        $this->assertSame(1, Attempt::where('user_id', $user->id)->where('evidence->source', 'lesson_done')->count());
        $this->assertSame(300, $record->fresh()->numeric_mastery);
    }
}
