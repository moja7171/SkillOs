<?php

namespace Tests\Feature;

use App\Models\Skill;
use App\Services\Ai\GeminiClient;
use App\Services\Ai\SkillContentGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SkillContentGeneratorTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function aiResult(array $mcqOverrides = []): array
    {
        return [
            'learn' => [
                'explanation' => "تابع با `def` تعریف می‌شود.\n\n```python\ndef f(x):\n    return x * 2\n```",
                'key_points' => ['def', 'return'],
                'common_mistakes' => ['فراموش کردن return'],
                'estimated_minutes' => 7,
            ],
            'practices' => [
                array_merge([
                    'title' => 'خروجی کد',
                    'form' => 'mcq',
                    'prompt' => 'خروجی چیست؟',
                    'options' => ['۱۰', 'None', 'خطا', '۲۰'],
                    'correct_option' => 1,
                    'correct_option_text' => 'None',
                    'expected_outcome' => 'None',
                    'hints' => ['به return دقت کن', 'return وجود ندارد', 'راهنمایی سوم اضافی'],
                    'rubric' => 'Correct if option 1.',
                    'difficulty' => 'intro',
                    'estimated_minutes' => 3,
                ], $mcqOverrides),
                [
                    'title' => 'تابع تمیزسازی',
                    'form' => 'coding',
                    'prompt' => 'تابعی بنویس که ...',
                    'expected_outcome' => 'استفاده از strip و lower',
                    'hints' => ['strip', 'lower'],
                    'rubric' => 'Correct if strip and lower used.',
                    'difficulty' => 'core',
                    'estimated_minutes' => 8,
                ],
            ],
        ];
    }

    public function test_generates_text_resource_learn_and_practice_activities_once(): void
    {
        $skill = Skill::factory()->create();

        $this->mock(GeminiClient::class)
            ->shouldReceive('generateJson')
            ->once()
            ->withArgs(fn (string $prompt) => str_contains($prompt, $skill->name) && str_contains($prompt, 'Persian'))
            ->andReturn($this->aiResult());

        $generator = app(SkillContentGenerator::class);
        $generator->generate($skill);
        $generator->generate($skill->fresh()); // idempotent: no second AI call, no duplicates

        $skill->refresh();
        $this->assertNotNull($skill->content_generated_at);

        $resource = $skill->resources()->sole();
        $this->assertSame('text', $resource->type);
        $this->assertTrue($resource->is_recommended);
        $this->assertStringContainsString('def f(x)', $resource->content);

        $learn = $skill->activities()->where('type', 'learn')->sole();
        $this->assertSame($resource->id, $learn->payload['resource_id']);
        $this->assertSame(['def', 'return'], $learn->payload['key_points']);
        $this->assertSame(7, $learn->estimated_minutes);

        $practices = $skill->activities()->where('type', 'practice')->orderBy('id')->get();
        $this->assertCount(2, $practices);
        $this->assertSame('mcq', $practices[0]->payload['form']);
        $this->assertSame(1, $practices[0]->payload['correct_option']);
        $this->assertCount(2, $practices[0]->payload['hints'], 'hints are capped at two');
        $this->assertSame('coding', $practices[1]->payload['form']);
        $this->assertArrayNotHasKey('options', $practices[1]->payload);
    }

    public function test_inconsistent_mcq_is_demoted_to_short_answer(): void
    {
        $skill = Skill::factory()->create();

        // Index says option 0 but the model's own answer text is option 1.
        $this->mock(GeminiClient::class)
            ->shouldReceive('generateJson')
            ->once()
            ->andReturn($this->aiResult(['correct_option' => 0]));

        app(SkillContentGenerator::class)->generate($skill);

        $first = $skill->activities()->where('type', 'practice')->orderBy('id')->first();
        $this->assertSame('short_answer', $first->payload['form']);
        $this->assertArrayNotHasKey('correct_option', $first->payload);
    }

    public function test_video_already_attached_stays_recommended(): void
    {
        $skill = Skill::factory()->create();
        $skill->resources()->create(['learning_item_id' => $skill->learning_item_id, 'type' => 'video', 'title' => 'ویدیو', 'url' => 'https://example.com/v', 'is_recommended' => true]);

        $this->mock(GeminiClient::class)->shouldReceive('generateJson')->once()->andReturn($this->aiResult());

        app(SkillContentGenerator::class)->generate($skill);

        $this->assertFalse($skill->resources()->where('type', 'text')->sole()->is_recommended);
    }
}
