<?php

namespace Tests\Feature;

use App\Models\Attempt;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_review_retention_row_only_shows_up_once_a_review_has_happened(): void
    {
        $user = User::factory()->create();
        $lesson = Lesson::factory()->withActivities()->create();
        $practice = $lesson->practices()->first();

        $this->actingAs($user)->get($lesson->url())->assertOk()->assertDontSee('نتیجه‌ی مرورها');

        Attempt::create(['activity_id' => $practice->id, 'user_id' => $user->id, 'result_status' => 'correct', 'evidence' => ['source' => 'review']]);
        Attempt::create(['activity_id' => $practice->id, 'user_id' => $user->id, 'result_status' => 'incorrect', 'evidence' => ['source' => 'review']]);
        Attempt::create(['activity_id' => $practice->id, 'user_id' => $user->id, 'result_status' => 'correct', 'evidence' => ['source' => 'free']]); // not a review, shouldn't count
        Attempt::create(['activity_id' => $practice->id, 'user_id' => $user->id, 'result_status' => 'started', 'evidence' => ['source' => 'review']]); // still open, shouldn't count

        $this->actingAs($user)->get($lesson->url())->assertOk()
            ->assertSee('نتیجه‌ی مرورها')
            ->assertSee('۱ از ۲ بار بلد بودی');
    }

    public function test_practice_list_shows_each_practices_own_latest_status(): void
    {
        $user = User::factory()->create();
        $lesson = Lesson::factory()->withActivities()->create();
        $practice = $lesson->practices()->first();

        $this->actingAs($user)->get($lesson->url())->assertOk()
            ->assertDontSee('حل‌شده')->assertDontSee('تلاش ناموفق')->assertDontSee('در حال انجام');

        $open = Attempt::create(['activity_id' => $practice->id, 'user_id' => $user->id, 'result_status' => 'started', 'evidence' => ['source' => 'free']]);
        $this->actingAs($user)->get($lesson->url())->assertOk()->assertSee('در حال انجام');

        $open->update(['result_status' => 'incorrect']);
        $this->actingAs($user)->get($lesson->url())->assertOk()->assertSee('تلاش ناموفق');

        Attempt::create(['activity_id' => $practice->id, 'user_id' => $user->id, 'result_status' => 'correct', 'evidence' => ['source' => 'free']]);
        $this->actingAs($user)->get($lesson->url())->assertOk()->assertSee('حل‌شده');
    }

    public function test_english_language_toggle_only_shows_up_when_english_text_exists(): void
    {
        $user = User::factory()->create();

        $withoutEnglish = Lesson::factory()->withActivities()->create();
        $this->actingAs($user)->get($withoutEnglish->url())->assertOk()->assertDontSee('English');

        $withEnglish = Lesson::factory()->withActivities()->create(['content_en' => 'English lesson text.']);
        $this->actingAs($user)->get($withEnglish->url())->assertOk()
            ->assertSee('English')
            ->assertSee('English lesson text.');
    }
}
