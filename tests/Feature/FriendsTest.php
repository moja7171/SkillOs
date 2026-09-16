<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FriendsTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_everyone_ordered_by_streak_and_marks_the_current_user(): void
    {
        $me = User::factory()->create(['name' => 'من', 'streak_count' => 2, 'is_admin' => true]);
        $friend = User::factory()->create(['name' => 'دوست', 'streak_count' => 9]);

        $page = $this->actingAs($me)->get(route('friends'))->assertOk();

        $page->assertSeeInOrder([$friend->name, $me->name])
            ->assertSee('(خودت)')
            ->assertSee('۹')
            ->assertSee('۲');
    }

    public function test_non_admin_cannot_see_the_friends_page(): void
    {
        $me = User::factory()->create(['is_admin' => false]);

        $this->actingAs($me)->get(route('friends'))->assertForbidden();
    }

    public function test_non_admin_does_not_see_the_nav_link(): void
    {
        $me = User::factory()->create(['is_admin' => false]);

        $this->actingAs($me)->get(route('home'))->assertOk()->assertDontSee('دوستان');
    }
}
