<?php

namespace Tests\Feature;

use App\Models\Skill;
use App\Models\User;
use App\Services\Ai\SkillContentGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SkillPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_skill_without_content_offers_generation_and_generates_on_request(): void
    {
        $skill = Skill::factory()->create(['name' => 'توابع']);
        $user = $skill->learningItem->user;

        $this->actingAs($user)->get(route('skills.show', $skill))
            ->assertOk()->assertSee('آماده‌سازی این مهارت')->assertSee('شروع‌نشده');

        $this->mock(SkillContentGenerator::class)
            ->shouldReceive('generate')
            ->once()
            ->withArgs(fn (Skill $s) => $s->is($skill));

        $this->actingAs($user)->post(route('skills.generate-content', $skill))
            ->assertRedirect(route('skills.show', $skill))
            ->assertSessionHas('status');
    }

    public function test_skill_with_content_renders_markdown_and_practices(): void
    {
        $skill = Skill::factory()->create(['content_generated_at' => now()]);
        $text = $skill->resources()->create(['learning_item_id' => $skill->learning_item_id, 'type' => 'text', 'title' => 'متن', 'content' => "## عنوان\n\n```python\nprint(1)\n```", 'is_recommended' => true]);
        $skill->activities()->create(['learning_item_id' => $skill->learning_item_id, 'type' => 'learn', 'title' => 'یادگیری', 'estimated_minutes' => 7, 'payload' => ['resource_id' => $text->id, 'key_points' => ['نکته‌ی یک'], 'common_mistakes' => []]]);
        $skill->activities()->create(['learning_item_id' => $skill->learning_item_id, 'type' => 'practice', 'title' => 'تمرین کد', 'estimated_minutes' => 8, 'payload' => ['form' => 'coding', 'difficulty' => 'core', 'prompt' => 'p', 'expected_outcome' => 'e', 'hints' => ['h1', 'h2'], 'rubric' => 'r']]);

        $this->actingAs($skill->learningItem->user)->get(route('skills.show', $skill))
            ->assertOk()
            ->assertSee('<h2>عنوان</h2>', false)
            ->assertSee('print(1)')
            ->assertSee('نکته‌ی یک')
            ->assertSee('تمرین کد')->assertSee('کدنویسی')->assertSee('اصلی')
            ->assertDontSee('آماده‌سازی این مهارت');
    }

    public function test_video_link_becomes_recommended_and_removal_restores_text(): void
    {
        $skill = Skill::factory()->create(['content_generated_at' => now()]);
        $skill->resources()->create(['learning_item_id' => $skill->learning_item_id, 'type' => 'text', 'title' => 'متن', 'content' => 'x', 'is_recommended' => true]);
        $user = $skill->learningItem->user;

        $this->actingAs($user)->post(route('skills.video.store', $skill), ['url' => 'https://www.youtube.com/watch?v=abc'])
            ->assertRedirect(route('skills.show', $skill));

        $this->assertTrue($skill->resources()->where('type', 'video')->sole()->is_recommended);
        $this->assertFalse($skill->resources()->where('type', 'text')->sole()->is_recommended);

        $this->actingAs($user)->delete(route('skills.video.destroy', $skill))->assertRedirect(route('skills.show', $skill));

        $this->assertSame(0, $skill->resources()->where('type', 'video')->count());
        $this->assertTrue($skill->resources()->where('type', 'text')->sole()->is_recommended);
    }

    public function test_invalid_video_url_is_rejected(): void
    {
        $skill = Skill::factory()->create();

        $this->actingAs($skill->learningItem->user)
            ->post(route('skills.video.store', $skill), ['url' => 'not a url'])
            ->assertSessionHasErrors('url');
    }

    public function test_other_users_cannot_view_or_change_a_skill(): void
    {
        $skill = Skill::factory()->create();
        $stranger = User::factory()->create();

        $this->actingAs($stranger)->get(route('skills.show', $skill))->assertForbidden();
        $this->actingAs($stranger)->post(route('skills.generate-content', $skill))->assertForbidden();
        $this->actingAs($stranger)->post(route('skills.video.store', $skill), ['url' => 'https://x.test'])->assertForbidden();
    }
}
