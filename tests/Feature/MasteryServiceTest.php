<?php

namespace Tests\Feature;

use App\Models\Attempt;
use App\Models\Lesson;
use App\Models\MasteryRecord;
use App\Models\User;
use App\Services\Ai\GeminiClient;
use App\Services\Mastery\MasteryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MasteryServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{int, string, string, ?string, int, string, int}>
     */
    public static function evidenceTable(): array
    {
        // start, result_status, source, verdict, expected numeric, expected level, review days
        return [
            'first independent success' => [0, 'correct', 'free', 'correct', 150, 'learning', 1],
            'crosses into familiar' => [200, 'correct', 'plan', 'correct', 350, 'familiar', 3],
            'assisted success' => [400, 'correct_with_hint', 'free', 'correct', 470, 'familiar', 3],
            'partial after give-up' => [400, 'incorrect', 'free', 'partial', 430, 'familiar', 3],
            'practice failure' => [520, 'incorrect', 'free', 'incorrect', 420, 'familiar', 3],
            'review success' => [700, 'correct', 'review', 'correct', 820, 'mastered', 21],
            'review with hint' => [700, 'correct_with_hint', 'review', 'correct', 750, 'proficient', 7],
            'review failure drops a level' => [820, 'incorrect', 'review', 'incorrect', 670, 'proficient', 7],
            'learn completion is neutral' => [0, 'completed', 'free', null, 0, 'learning', 1],
            'abandoned is neutral' => [300, 'abandoned', 'free', null, 300, 'familiar', 3],
            'clamped at 1000' => [950, 'correct', 'free', 'correct', 1000, 'mastered', 21],
            'clamped at 0' => [50, 'incorrect', 'review', 'incorrect', 0, 'learning', 1],
        ];
    }

    #[DataProvider('evidenceTable')]
    public function test_applies_evidence_deltas_levels_and_review_intervals(int $start, string $status, string $source, ?string $verdict, int $expectedNumeric, string $expectedLevel, int $reviewDays): void
    {
        $this->freezeTime();
        $user = User::factory()->create();
        $lesson = Lesson::factory()->withActivities()->create();
        $record = MasteryRecord::create(['user_id' => $user->id, 'lesson_id' => $lesson->id, 'numeric_mastery' => $start, 'level' => MasteryService::levelFor($start)]);

        $attempt = Attempt::create([
            'activity_id' => $lesson->practices()->first()->id,
            'user_id' => $user->id,
            'result_status' => $status,
            'evidence' => ['source' => $source, 'verdict' => $verdict],
        ]);

        $change = app(MasteryService::class)->applyAttempt($attempt);

        $record->refresh();
        $this->assertSame($expectedNumeric, $record->numeric_mastery);
        $this->assertSame($expectedLevel, $record->level);
        $this->assertSame($expectedLevel, $change['new_level']);
        $this->assertTrue(now()->addDays($reviewDays)->startOfDay()->equalTo($record->next_review_due_at));
        $this->assertTrue(now()->startOfSecond()->equalTo($record->last_evaluated_at));
    }

    public function test_first_attempt_creates_the_record_and_open_attempts_are_rejected(): void
    {
        $user = User::factory()->create();
        $lesson = Lesson::factory()->withActivities()->create();
        $practice = $lesson->practices()->first();

        $open = Attempt::create(['activity_id' => $practice->id, 'user_id' => $user->id, 'result_status' => 'started']);
        $this->expectException(\LogicException::class);
        try {
            app(MasteryService::class)->applyAttempt($open);
        } finally {
            $this->assertSame(0, MasteryRecord::count());
        }
    }

    public function test_session_finalize_updates_mastery_and_reports_the_level_change(): void
    {
        $user = User::factory()->create();
        $lesson = Lesson::factory()->withActivities()->create();
        $practice = $lesson->practices()->first();
        $this->mock(GeminiClient::class)->shouldReceive('generateJson')->once()->andReturn(['verdict' => 'correct', 'feedback' => 'عالی']);

        $this->actingAs($user)->post(route('session.start', $practice));
        $attempt = Attempt::latest('id')->first();
        $this->actingAs($user)->post(route('session.submit', $attempt), ['response' => 'x']);

        $record = MasteryRecord::where('user_id', $user->id)->where('lesson_id', $lesson->id)->sole();
        $this->assertSame(150, $record->numeric_mastery);
        $this->assertSame('learning', $record->level);
        $this->assertSame(['from' => 'not_started', 'to' => 'learning'], $attempt->fresh()->evidence['level_change']);

        $page = $this->actingAs($user)->get(route('session.show', $attempt))->assertOk();
        $page->assertSee('سطح این درس')->assertSee('در حال یادگیری')->assertDontSee('150');

        $this->actingAs($user)->get(route('lessons.show', $lesson))->assertOk()->assertSee('مرور بعدی')->assertDontSee('150');
        $this->actingAs($user)->get(route('courses.show', $lesson->course))->assertOk()->assertSee('در حال یادگیری');
    }
}
