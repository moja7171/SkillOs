<?php

namespace Tests\Feature;

use App\Models\LearningItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PagesRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_pages_render_in_persian_rtl(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('dir="rtl"', false)->assertSee('ورود');
        $this->get(route('register'))->assertOk()->assertSee('ثبت‌نام');
    }

    public function test_learning_item_pages_render_for_every_design_state(): void
    {
        $user = User::factory()->create();
        $draft = LearningItem::factory()->for($user)->create(['title' => 'موضوع خام']);
        $pending = LearningItem::factory()->for($user)->pendingReview()->create();
        $approved = LearningItem::factory()->for($user)->approved()->create(['daily_time_minutes' => 45]);
        $approved->skills()->create(['name' => 'مهارت اول', 'order' => 0]);

        $this->actingAs($user);

        $this->get(route('learning-items.index'))->assertOk()->assertSee('موضوع خام')->assertSee('۴۵ دقیقه در روز');
        $this->get(route('learning-items.create'))->assertOk();
        $this->get(route('learning-items.show', $draft))->assertOk()->assertSee('ساختن طرح با AI');
        $this->get(route('learning-items.show', $pending))->assertOk()->assertSee('منتظر تأیید');
        $this->get(route('learning-items.show', $approved))->assertOk()->assertSee('مهارت اول')->assertSee('شروع‌نشده');
        $this->get(route('learning-items.edit', $approved))->assertOk();
        $this->get(route('profile.edit'))->assertOk();
    }

    public function test_validation_errors_are_in_persian(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('learning-items.create'))
            ->post(route('learning-items.store'), ['title' => ''])
            ->assertSessionHasErrors(['title' => 'وارد کردن عنوان الزامی است.']);
    }
}
