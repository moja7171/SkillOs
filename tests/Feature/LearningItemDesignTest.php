<?php

namespace Tests\Feature;

use App\Models\LearningItem;
use App\Models\User;
use App\Services\Ai\GeminiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearningItemDesignTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_stores_starting_point_and_shows_it_on_item_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('learning-items.store'), [
            'title' => 'Python',
            'starting_point' => 'I know loops, never used classes.',
        ]);

        $item = LearningItem::query()->firstOrFail();

        $response->assertRedirect(route('learning-items.show', $item));
        $this->assertSame('I know loops, never used classes.', $item->starting_point);

        $this->actingAs($user)
            ->get(route('learning-items.show', $item))
            ->assertSee('I know loops, never used classes.');
    }

    public function test_generate_design_passes_starting_point_to_ai_and_stages_draft(): void
    {
        $item = LearningItem::factory()->create([
            'title' => 'Python',
            'starting_point' => 'I know loops.',
        ]);

        $draft = [
            'outcome_statement' => 'Build scripts.',
            'skills' => [['key' => 's1', 'name' => 'Classes', 'description' => 'OOP basics.', 'prerequisite_keys' => []]],
        ];

        $this->mock(GeminiClient::class)
            ->shouldReceive('generateJson')
            ->once()
            ->withArgs(fn (string $prompt) => str_contains($prompt, 'Python') && str_contains($prompt, 'I know loops.'))
            ->andReturn($draft);

        $this->actingAs($item->user)
            ->post(route('learning-items.generate-design', $item))
            ->assertRedirect(route('learning-items.show', $item));

        $item->refresh();
        $this->assertSame('pending_review', $item->design_status);
        $this->assertSame($draft, $item->design_draft);
    }

    public function test_regenerate_replaces_pending_draft(): void
    {
        $item = LearningItem::factory()->pendingReview()->create();

        $newDraft = [
            'outcome_statement' => 'A different outcome.',
            'skills' => [['key' => 's1', 'name' => 'Something else', 'description' => 'New.', 'prerequisite_keys' => []]],
        ];

        $this->mock(GeminiClient::class)
            ->shouldReceive('generateJson')
            ->once()
            ->andReturn($newDraft);

        $this->actingAs($item->user)
            ->post(route('learning-items.generate-design', $item))
            ->assertRedirect(route('learning-items.show', $item));

        $item->refresh();
        $this->assertSame('pending_review', $item->design_status);
        $this->assertSame('A different outcome.', $item->design_draft['outcome_statement']);
    }

    public function test_generate_design_is_forbidden_once_approved(): void
    {
        $item = LearningItem::factory()->approved()->create();

        $this->mock(GeminiClient::class)->shouldNotReceive('generateJson');

        $this->actingAs($item->user)
            ->post(route('learning-items.generate-design', $item))
            ->assertForbidden();

        $this->assertSame('approved', $item->fresh()->design_status);
    }

    public function test_approve_creates_skills_with_prerequisites(): void
    {
        $item = LearningItem::factory()->pendingReview()->create();

        $this->actingAs($item->user)
            ->post(route('learning-items.approve-design', $item))
            ->assertRedirect(route('learning-items.show', $item));

        $item->refresh();
        $this->assertSame('approved', $item->design_status);
        $this->assertSame('Write small command-line tools.', $item->outcome_statement);

        $skills = $item->skills()->with('prerequisites')->get();
        $this->assertCount(2, $skills);
        $this->assertNull($skills[0]->content_generated_at);
        $this->assertSame(['Variables'], $skills[1]->prerequisites->pluck('name')->all());
    }

    public function test_another_user_cannot_generate_design_for_item(): void
    {
        $item = LearningItem::factory()->create();

        $this->mock(GeminiClient::class)->shouldNotReceive('generateJson');

        $this->actingAs(User::factory()->create())
            ->post(route('learning-items.generate-design', $item))
            ->assertForbidden();
    }
}
