<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Attempt;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\User;
use App\Services\Ai\GeminiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Lesson $lesson;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->lesson = Lesson::factory()->withActivities()->create();
        $this->lesson->activities()->create(['key' => 'q1', 'type' => 'practice', 'title' => 'چندگزینه‌ای', 'estimated_minutes' => 2, 'payload' => [
            'form' => 'mcq', 'prompt' => 'کدام؟', 'options' => ['الف', 'ب', 'ج', 'د'], 'correct_option' => 2,
            'expected_outcome' => 'گزینه‌ی ج چون …', 'hints' => ['هینت اول', 'هینت دوم'], 'rubric' => 'r', 'difficulty' => 'intro',
        ]]);
    }

    private function mcq(): Activity
    {
        return $this->lesson->activities()->where('key', 'q1')->sole();
    }

    private function coding(): Activity
    {
        return $this->lesson->activities()->where('key', 'p1')->sole();
    }

    private function startAttempt(Activity $activity): Attempt
    {
        $this->actingAs($this->user)->post(route('session.start', $activity))->assertRedirect();

        return Attempt::latest('id')->first();
    }

    public function test_learn_session_completes_and_offers_first_practice(): void
    {
        Enrollment::factory()->for($this->user)->for($this->lesson->course)->create();
        $learn = $this->lesson->learnActivity;

        $attempt = $this->startAttempt($learn);
        $this->assertSame('started', $attempt->result_status);

        $this->actingAs($this->user)->get(route('session.show', $attempt))->assertOk()->assertSee('آماده‌ام')->assertSee('<h2>مقدمه</h2>', false);

        $this->actingAs($this->user)->post(route('session.complete', $attempt))->assertRedirect(route('session.show', $attempt));

        $attempt->refresh();
        $this->assertSame('completed', $attempt->result_status);
        $this->assertNotNull($attempt->completed_at);
        $this->assertNotNull(Enrollment::first()->last_activity_at);
        $this->assertSame(1, $this->user->fresh()->streak_count);

        $this->actingAs($this->user)->get(route('session.show', $attempt))->assertOk()->assertSee('برو سراغ تمرین')->assertDontSee('آماده‌ام');
        $this->actingAs($this->user)->post(route('session.complete', $attempt))->assertStatus(422);
    }

    public function test_mcq_correct_answer_without_hint_is_independent_success(): void
    {
        $this->mock(GeminiClient::class)->shouldNotReceive('generateJson');
        $attempt = $this->startAttempt($this->mcq());

        $this->actingAs($this->user)->post(route('session.submit', $attempt), ['response' => '2'])->assertRedirect();

        $attempt->refresh();
        $this->assertSame('correct', $attempt->result_status);
        $this->assertSame(0, $attempt->hint_level);
        $this->assertSame('correct', $attempt->evidence['verdict']);
        $this->assertCount(1, $attempt->evidence['history']);

        $page = $this->actingAs($this->user)->get(route('session.show', $attempt))->assertOk();
        $page->assertSee('درست — بدون راهنمایی')->assertDontSee('ارسال پاسخ');
    }

    public function test_wrong_answers_reveal_hints_but_not_the_answer(): void
    {
        $attempt = $this->startAttempt($this->mcq());

        $this->actingAs($this->user)->post(route('session.submit', $attempt), ['response' => '0']);
        $attempt->refresh();
        $this->assertSame('started', $attempt->result_status);
        $this->assertSame(1, $attempt->hint_level);
        $this->actingAs($this->user)->get(route('session.show', $attempt))->assertSee('هینت اول')->assertDontSee('هینت دوم')->assertDontSee('گزینه‌ی ج چون');

        $this->actingAs($this->user)->post(route('session.submit', $attempt), ['response' => '1']);
        $attempt->refresh();
        $this->assertSame(2, $attempt->hint_level);
        $this->actingAs($this->user)->get(route('session.show', $attempt))->assertSee('هینت دوم')->assertDontSee('گزینه‌ی ج چون');

        // Both hints exhausted: further wrong answers keep the attempt open and never
        // auto-reveal the answer — only the explicit give-up action does that.
        $this->actingAs($this->user)->post(route('session.submit', $attempt), ['response' => '3']);
        $attempt->refresh();
        $this->assertSame('started', $attempt->result_status);
        $this->assertSame(2, $attempt->hint_level);
        $this->assertArrayNotHasKey('revealed', $attempt->evidence);
        $this->assertCount(3, $attempt->evidence['history']);
        $this->actingAs($this->user)->get(route('session.show', $attempt))->assertDontSee('گزینه‌ی ج چون')->assertSee('ارسال پاسخ');

        $this->actingAs($this->user)->post(route('session.give-up', $attempt))->assertRedirect();
        $attempt->refresh();
        $this->assertSame('incorrect', $attempt->result_status);
        $this->assertTrue($attempt->evidence['revealed']);
        $this->actingAs($this->user)->get(route('session.show', $attempt))->assertSee('گزینه‌ی ج چون')->assertSee('این بار نشد');
    }

    public function test_correct_after_a_hint_is_assisted_success(): void
    {
        $attempt = $this->startAttempt($this->mcq());
        $this->actingAs($this->user)->post(route('session.submit', $attempt), ['response' => '0']);
        $this->actingAs($this->user)->post(route('session.submit', $attempt), ['response' => '2']);

        $this->assertSame('correct_with_hint', $attempt->fresh()->result_status);
    }

    public function test_give_up_reveals_answer_and_records_incorrect(): void
    {
        $attempt = $this->startAttempt($this->mcq());

        $this->actingAs($this->user)->post(route('session.give-up', $attempt))->assertRedirect();

        $attempt->refresh();
        $this->assertSame('incorrect', $attempt->result_status);
        $this->assertTrue($attempt->evidence['revealed']);
        $this->actingAs($this->user)->post(route('session.submit', $attempt), ['response' => '2'])->assertStatus(422);
    }

    public function test_open_answer_is_graded_by_gemini_with_the_rubric(): void
    {
        $attempt = $this->startAttempt($this->coding());

        $this->mock(GeminiClient::class)
            ->shouldReceive('generateJson')
            ->once()
            ->withArgs(fn (string $prompt, array $schema) => str_contains($prompt, 'Correct if strip is used.')
                && str_contains($prompt, 'def clean(s): return s.strip()')
                && str_contains($prompt, 'Hints already shown to the learner: 0 of 2')
                && str_contains($prompt, 'Persian'))
            ->andReturn(['verdict' => 'correct', 'feedback' => 'درسته؛ strip رو درست به کار بردی.']);

        $this->actingAs($this->user)->post(route('session.submit', $attempt), ['response' => 'def clean(s): return s.strip()']);

        $attempt->refresh();
        $this->assertSame('correct', $attempt->result_status);
        $this->assertSame('درسته؛ strip رو درست به کار بردی.', $attempt->evidence['feedback']);
    }

    public function test_partial_answer_grants_one_retry_and_later_success_counts_as_assisted(): void
    {
        $attempt = $this->startAttempt($this->coding());

        $this->mock(GeminiClient::class)->shouldReceive('generateJson')->twice()->andReturn(
            ['verdict' => 'partial', 'feedback' => 'نزدیکه ولی lower نداره.'],
            ['verdict' => 'correct', 'feedback' => 'حالا کامله.'],
        );

        $this->actingAs($this->user)->post(route('session.submit', $attempt), ['response' => 'v1']);
        $attempt->refresh();
        $this->assertSame('started', $attempt->result_status);
        $this->assertSame(0, $attempt->hint_level, 'partial does not spend a hint');
        $this->assertTrue($attempt->evidence['partial_retry']);
        $this->actingAs($this->user)->get(route('session.show', $attempt))->assertSee('نزدیکه، ولی کامل نیست')->assertSee('نزدیکه ولی lower نداره.');

        $this->actingAs($this->user)->post(route('session.submit', $attempt), ['response' => 'v2']);
        $this->assertSame('correct_with_hint', $attempt->fresh()->result_status);
    }

    public function test_second_partial_falls_into_the_hint_path(): void
    {
        $attempt = $this->startAttempt($this->coding());
        $this->mock(GeminiClient::class)->shouldReceive('generateJson')->twice()->andReturn(['verdict' => 'partial', 'feedback' => 'x']);

        $this->actingAs($this->user)->post(route('session.submit', $attempt), ['response' => 'v1']);
        $this->actingAs($this->user)->post(route('session.submit', $attempt), ['response' => 'v2']);

        $attempt->refresh();
        $this->assertSame('started', $attempt->result_status);
        $this->assertSame(1, $attempt->hint_level);
    }

    public function test_failed_ai_call_keeps_the_attempt_open_and_the_answer(): void
    {
        $attempt = $this->startAttempt($this->coding());
        $this->mock(GeminiClient::class)->shouldReceive('generateJson')->once()->andThrow(new \RuntimeException('Gemini request failed'));

        $this->actingAs($this->user)
            ->from(route('session.show', $attempt))
            ->post(route('session.submit', $attempt), ['response' => 'my code'])
            ->assertRedirect(route('session.show', $attempt))
            ->assertSessionHas('error')
            ->assertSessionHasInput('response', 'my code');

        $attempt->refresh();
        $this->assertSame('started', $attempt->result_status);
        $this->assertSame(0, $attempt->hint_level);
        $this->assertEmpty($attempt->evidence['history']);
    }

    public function test_empty_response_is_rejected_in_persian(): void
    {
        $attempt = $this->startAttempt($this->coding());

        $this->actingAs($this->user)->post(route('session.submit', $attempt), ['response' => ''])
            ->assertSessionHasErrors(['response' => 'وارد کردن پاسخ الزامی است.']);
    }

    public function test_another_user_cannot_see_or_drive_an_attempt(): void
    {
        $attempt = $this->startAttempt($this->mcq());
        $stranger = User::factory()->create();

        $this->actingAs($stranger)->get(route('session.show', $attempt))->assertForbidden();
        $this->actingAs($stranger)->post(route('session.submit', $attempt), ['response' => '2'])->assertForbidden();
        $this->actingAs($stranger)->post(route('session.give-up', $attempt))->assertForbidden();
        $this->assertSame('started', $attempt->fresh()->result_status);
    }

    public function test_open_starts_fresh_the_first_time_then_resumes_or_shows_the_last_attempt(): void
    {
        $activity = $this->mcq();

        // First visit: nothing exists yet, behaves like start().
        $this->actingAs($this->user)->get(route('session.open', $activity))->assertRedirect();
        $this->assertSame(1, Attempt::count());
        $first = Attempt::latest('id')->first();
        $this->assertSame('started', $first->result_status);

        // Still open: re-opening resumes the same attempt instead of creating another.
        $this->actingAs($this->user)->get(route('session.open', $activity))->assertRedirect(route('session.show', $first));
        $this->assertSame(1, Attempt::count());

        // Finish it, then opening again shows that finished attempt (with its result and
        // a retry option) rather than silently starting a blank new one.
        $this->actingAs($this->user)->post(route('session.submit', $first), ['response' => '2']);
        $this->actingAs($this->user)->get(route('session.open', $activity))->assertRedirect(route('session.show', $first));
        $this->assertSame(1, Attempt::count());
    }

    public function test_cancel_deletes_an_open_attempt_without_penalty(): void
    {
        $attempt = $this->startAttempt($this->mcq());

        $this->actingAs($this->user)->delete(route('session.cancel', $attempt))->assertRedirect($this->lesson->url());

        $this->assertDatabaseMissing('attempts', ['id' => $attempt->id]);
    }

    public function test_cancel_refuses_a_finished_attempt(): void
    {
        $attempt = $this->startAttempt($this->mcq());
        $this->actingAs($this->user)->post(route('session.give-up', $attempt));

        $this->actingAs($this->user)->delete(route('session.cancel', $attempt))->assertStatus(422);
        $this->assertDatabaseHas('attempts', ['id' => $attempt->id]);
    }

    public function test_another_user_cannot_cancel_someones_attempt(): void
    {
        $attempt = $this->startAttempt($this->mcq());
        $stranger = User::factory()->create();

        $this->actingAs($stranger)->delete(route('session.cancel', $attempt))->assertForbidden();
        $this->assertDatabaseHas('attempts', ['id' => $attempt->id]);
    }

    public function test_session_show_lists_earlier_attempts_at_the_same_practice(): void
    {
        $first = $this->startAttempt($this->mcq());
        $this->actingAs($this->user)->post(route('session.submit', $first), ['response' => '0']); // wrong, uses a hint
        $this->actingAs($this->user)->post(route('session.give-up', $first));

        $second = $this->startAttempt($this->mcq());
        $this->actingAs($this->user)->post(route('session.submit', $second), ['response' => '2']);

        $this->actingAs($this->user)->get(route('session.show', $second))
            ->assertOk()
            ->assertSee('تلاش‌های قبلی من')
            ->assertSee('غلط');
    }
}
