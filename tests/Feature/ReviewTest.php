<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Attempt;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\MasteryRecord;
use App\Models\User;
use App\Services\Ai\GeminiClient;
use App\Services\Review\ReviewQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Lesson $lesson;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->lesson = Lesson::factory()->withActivities()->create(['title' => 'درس مرور', 'key_points' => ['نکته‌ی کلیدی اول', 'نکته‌ی کلیدی دوم'], 'common_mistakes' => ['اشتباه رایج']]);
        Enrollment::factory()->for($this->user)->for($this->lesson->course)->create();
        $this->due($this->lesson);
    }

    private function due(Lesson $lesson, int $mastery = 300): void
    {
        MasteryRecord::create(['user_id' => $this->user->id, 'lesson_id' => $lesson->id, 'numeric_mastery' => $mastery, 'level' => 'familiar', 'next_review_due_at' => now()->subDay()]);
    }

    private function authoredMcq(string $key = 'q1'): Activity
    {
        return $this->lesson->activities()->create(['key' => $key, 'type' => 'practice', 'title' => 'چندگزینه‌ای', 'estimated_minutes' => 2, 'payload' => [
            'form' => 'mcq', 'prompt' => 'کدام درست است؟', 'options' => ['الف', 'ب', 'ج', 'د'], 'correct_option' => 2,
            'expected_outcome' => 'گزینه‌ی ج درست است.', 'hints' => [], 'rubric' => 'r', 'difficulty' => 'core',
        ]]);
    }

    /**
     * @return array<string, mixed>
     */
    private function generated(string $answer = 'پاسخ درست'): array
    {
        return [
            'title' => 'سوال تازه', 'question' => 'سوال ساخته‌شده؟',
            'options' => [$answer, 'یک گزینه‌ی غلط و کمی بلندتر', 'گزینه‌ی غلط دیگر که طولانی‌ترین است', 'غلط'],
            'correct_option_text' => $answer, 'explanation' => 'چون این درست است.',
        ];
    }

    public function test_a_review_uses_an_authored_unanswered_question_without_calling_ai(): void
    {
        $this->mock(GeminiClient::class)->shouldNotReceive('generateJson');
        $mcq = $this->authoredMcq();

        $this->actingAs($this->user)->post(route('reviews.start'))->assertRedirect();

        $attempt = Attempt::sole();
        $this->assertSame($mcq->id, $attempt->activity_id);
        $this->assertSame('review', $attempt->evidence['source']);
    }

    public function test_an_open_review_is_resumed_instead_of_starting_another(): void
    {
        $this->mock(GeminiClient::class)->shouldNotReceive('generateJson');
        $this->authoredMcq();

        $this->actingAs($this->user)->post(route('reviews.start'));
        $this->actingAs($this->user)->post(route('reviews.start'));

        $this->assertSame(1, Attempt::count());
    }

    public function test_when_every_question_was_answered_a_new_four_option_question_is_generated(): void
    {
        $answered = $this->authoredMcq();
        Attempt::create(['activity_id' => $answered->id, 'user_id' => $this->user->id, 'result_status' => 'correct', 'completed_at' => now()->subDay()]);
        $this->mock(GeminiClient::class)->shouldReceive('generateJson')->once()->andReturn($this->generated());

        $this->actingAs($this->user)->post(route('reviews.start'))->assertRedirect();

        $question = Attempt::where('evidence->source', 'review')->sole()->activity;
        $this->assertTrue($question->generated);
        $this->assertSame('mcq', $question->payload['form']);
        $this->assertCount(4, $question->payload['options']);
        $this->assertSame('پاسخ درست', $question->payload['options'][$question->payload['correct_option']]);
        $this->assertFalse($this->lesson->practices()->whereKey($question->id)->exists(), 'generated questions stay out of the lesson practice list');
        $this->assertFalse($this->lesson->fresh()->practices->contains($question));
    }

    public function test_an_answer_that_is_the_longest_option_is_rejected_and_asked_again(): void
    {
        $this->mock(GeminiClient::class)->shouldReceive('generateJson')->twice()->andReturn(
            $this->generated('پاسخ درست و بسیار بسیار طولانی‌تر از همه‌ی گزینه‌های دیگر'),
            $this->generated(),
        );

        $this->actingAs($this->user)->post(route('reviews.start'))->assertRedirect();

        $this->assertSame(1, Activity::where('generated', true)->count());
    }

    public function test_a_failed_generation_leaves_the_lesson_due_and_says_so(): void
    {
        $this->mock(GeminiClient::class)->shouldReceive('generateJson')->andThrow(new \RuntimeException('no key'));

        $this->actingAs($this->user)->post(route('reviews.start'))
            ->assertRedirect(route('home'))
            ->assertSessionHas('status');

        $this->assertSame(0, Attempt::count());
        $this->assertTrue(MasteryRecord::sole()->next_review_due_at->isPast());
    }

    public function test_the_review_page_shows_the_recap_first_and_no_lesson_text(): void
    {
        $this->authoredMcq();
        $this->actingAs($this->user)->post(route('reviews.start'));

        $this->actingAs($this->user)->get(route('session.show', Attempt::sole()))->assertOk()
            ->assertSee('نکته‌ی کلیدی اول')
            ->assertSee('اشتباه رایج')
            ->assertSee('بزن بریم سوال')
            ->assertSee('کدام درست است؟')
            ->assertDontSee('<h2>مقدمه</h2>', false);
    }

    public function test_a_wrong_review_answer_is_final_and_shows_the_answer_and_explanation(): void
    {
        $this->mock(GeminiClient::class)->shouldNotReceive('generateJson');
        $this->authoredMcq();
        $this->actingAs($this->user)->post(route('reviews.start'));
        $attempt = Attempt::sole();

        $this->actingAs($this->user)->post(route('session.submit', $attempt), ['response' => '0'])->assertRedirect();

        $attempt->refresh();
        $this->assertSame('incorrect', $attempt->result_status);
        $this->assertTrue($attempt->evidence['revealed']);
        $this->assertSame(150, MasteryRecord::sole()->numeric_mastery); // 300 - 150 for a failed review
        $this->actingAs($this->user)->get(route('session.show', $attempt))
            ->assertSee('این بار نشد')
            ->assertSee('گزینه‌ی ج درست است.')
            ->assertSee('برگرد به خانه');
        $this->actingAs($this->user)->post(route('session.submit', $attempt), ['response' => '2'])->assertStatus(422);
    }

    public function test_after_a_review_only_the_next_review_is_offered(): void
    {
        $this->mock(GeminiClient::class)->shouldNotReceive('generateJson');
        $this->authoredMcq();
        $second = Lesson::factory()->for($this->lesson->course)->withActivities()->create(['order' => 1]);
        $this->due($second);
        $second->activities()->create(['key' => 'q1', 'type' => 'practice', 'title' => 'سوال دوم', 'estimated_minutes' => 2, 'payload' => [
            'form' => 'mcq', 'prompt' => 'سوال دوم؟', 'options' => ['۱', '۲', '۳', '۴'], 'correct_option' => 0, 'expected_outcome' => 'x', 'hints' => [], 'rubric' => '', 'difficulty' => 'core',
        ]]);

        $this->actingAs($this->user)->post(route('reviews.start'));
        $attempt = Attempt::sole();
        $this->actingAs($this->user)->post(route('session.submit', $attempt), ['response' => '2']);

        $this->actingAs($this->user)->get(route('session.show', $attempt->fresh()))->assertOk()
            ->assertSee('مرور بعدی (۱ مونده)')
            ->assertSee(route('reviews.start'), false);
    }

    public function test_the_daily_limit_caps_the_queue(): void
    {
        $activity = $this->authoredMcq();
        foreach (range(1, ReviewQueue::DAILY_LIMIT) as $i) {
            Attempt::create(['activity_id' => $activity->id, 'user_id' => $this->user->id, 'result_status' => 'correct', 'completed_at' => now(), 'evidence' => ['source' => 'review']]);
        }

        $this->assertCount(0, app(ReviewQueue::class)->due($this->user));
    }

    public function test_only_lessons_of_active_enrollments_are_due(): void
    {
        $this->assertCount(1, app(ReviewQueue::class)->due($this->user));

        Enrollment::where('user_id', $this->user->id)->update(['status' => 'paused']);

        $this->assertCount(0, app(ReviewQueue::class)->due($this->user));
    }

    public function test_reporting_a_generated_question_retires_it_and_takes_the_attempt_back(): void
    {
        $this->mock(GeminiClient::class)->shouldReceive('generateJson')->twice()->andReturn($this->generated(), $this->generated('پاسخ دیگر'));
        $this->actingAs($this->user)->post(route('reviews.start'));
        $attempt = Attempt::sole();
        $question = $attempt->activity;
        $wrong = $question->payload['correct_option'] === 0 ? '1' : '0';
        $this->actingAs($this->user)->post(route('session.submit', $attempt), ['response' => $wrong]);
        $this->assertSame(150, MasteryRecord::sole()->numeric_mastery);

        $this->actingAs($this->user)->get(route('session.show', $attempt))->assertSee('این سوال مشکل داره');
        $this->actingAs($this->user)->post(route('session.report', $attempt))->assertRedirect(route('home'))->assertSessionHas('status');

        $this->assertDatabaseMissing('attempts', ['id' => $attempt->id]);
        $this->assertTrue($question->fresh()->payload['retired']);
        $record = MasteryRecord::sole();
        $this->assertSame(300, $record->numeric_mastery);
        $this->assertTrue($record->next_review_due_at->lte(now()));
        // The retired question is never picked again: the lesson's next review writes a new one.
        $this->assertNotSame($question->id, app(ReviewQueue::class)->questionFor($this->user, $this->lesson->fresh())->id);
    }

    public function test_an_authored_question_cannot_be_reported(): void
    {
        $this->mock(GeminiClient::class)->shouldNotReceive('generateJson');
        $this->authoredMcq();
        $this->actingAs($this->user)->post(route('reviews.start'));
        $attempt = Attempt::sole();
        $this->actingAs($this->user)->post(route('session.submit', $attempt), ['response' => '2']);

        $this->actingAs($this->user)->post(route('session.report', $attempt))->assertStatus(422);
        $this->assertDatabaseHas('attempts', ['id' => $attempt->id]);
    }
}
