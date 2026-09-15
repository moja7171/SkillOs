<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_matches_lessons_by_title_summary_or_content_across_courses(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['title' => 'دوره‌ی آزمایشی']);
        $byTitle = Lesson::factory()->for($course)->create(['title' => 'دامنه‌ها و Closureها', 'content' => 'متن نامرتبط']);
        $byContent = Lesson::factory()->for($course)->create(['title' => 'درس دیگر', 'content' => 'اینجا درباره‌ی closure توضیح می‌دیم']);
        $unrelated = Lesson::factory()->for($course)->create(['title' => 'یه درس دیگه', 'content' => 'ربطی نداره']);

        $page = $this->actingAs($user)->get(route('search', ['q' => 'closure']))->assertOk();

        $page->assertSee($byTitle->title)->assertSee($byContent->title)->assertDontSee($unrelated->title);
    }

    public function test_a_query_under_two_characters_asks_for_more(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('search', ['q' => 'ا']))->assertOk()->assertSee('حداقل ۲ حرف');
    }

    public function test_no_query_shows_no_results_section(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('search'))->assertOk()->assertDontSee('نتیجه');
    }
}
