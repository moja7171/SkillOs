<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DeployTest extends TestCase
{
    use RefreshDatabase;

    public function test_ops_endpoint_is_hidden_without_a_configured_token_or_with_a_wrong_one(): void
    {
        config(['app.ops_token' => null]);
        $this->get('/_ops/status?token=anything')->assertNotFound();

        config(['app.ops_token' => 'secret']);
        $this->get('/_ops/status')->assertNotFound();
        $this->get('/_ops/status?token=wrong')->assertNotFound();
        $this->get('/_ops/nope?token=secret')->assertNotFound();
    }

    public function test_ops_status_migrate_import_and_clear_run_with_the_token(): void
    {
        config(['app.ops_token' => 'secret']);

        $this->get('/_ops/status?token=secret')->assertOk()->assertSee('courses on disk: complete-python-mastery, python-deep-dive-1');
        $this->get('/_ops/migrate?token=secret')->assertOk()->assertSee('artisan migrate → exit 0');
        $this->get('/_ops/import?token=secret&slug=sample-course')->assertOk()->assertSee('artisan content:import → exit 0');
        $this->assertDatabaseHas('courses', ['slug' => 'sample-course']);
        $this->get('/_ops/clear?token=secret')->assertOk()->assertSee('artisan optimize:clear → exit 0');
    }

    public function test_ops_delete_course_removes_a_course_and_refuses_without_a_slug(): void
    {
        config(['app.ops_token' => 'secret']);
        $this->artisan('content:import', ['slug' => 'sample-course']);
        $this->assertDatabaseHas('courses', ['slug' => 'sample-course']);

        $this->get('/_ops/delete-course?token=secret')->assertOk()->assertSee('ERROR: pass ?slug=');
        $this->assertDatabaseHas('courses', ['slug' => 'sample-course']);

        $this->get('/_ops/delete-course?token=secret&slug=does-not-exist')->assertOk()->assertSee('nothing to delete');

        $this->get('/_ops/delete-course?token=secret&slug=sample-course')->assertOk()->assertSee('deleted course');
        $this->assertDatabaseMissing('courses', ['slug' => 'sample-course']);
    }

    public function test_ops_deploy_log_reports_missing_or_tails_the_file(): void
    {
        config(['app.ops_token' => 'secret']);
        $path = storage_path('logs/deploy-hook.log');
        File::delete($path);

        $this->get('/_ops/deploy-log?token=secret')->assertOk()->assertSee('never run');

        File::put($path, "line one\nline two\n");
        $this->get('/_ops/deploy-log?token=secret')->assertOk()->assertSee('line one')->assertSee('line two');

        File::delete($path);
    }

    public function test_registration_requires_the_invite_code_when_configured(): void
    {
        config(['app.registration_code' => 'friends']);

        $this->get('/register')->assertOk()->assertSee('کد دعوت');

        $data = ['name' => 'A', 'email' => 'a@x.io', 'password' => 'password123', 'password_confirmation' => 'password123'];
        $this->post('/register', $data + ['code' => 'nope'])->assertSessionHasErrors('code');
        $this->assertGuest();

        $this->post('/register', $data + ['code' => 'friends'])->assertRedirect(route('home', absolute: false));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'a@x.io']);
    }

    public function test_media_urls_are_rebased_only_when_a_base_is_configured(): void
    {
        config(['media.base_url' => null]);
        $this->assertSame('/media/c/v.mp4', media_url('/media/c/v.mp4'));

        config(['media.base_url' => 'http://localhost:8765/']);
        $this->assertSame('http://localhost:8765/c/v.mp4', media_url('/media/c/v.mp4'));
        $this->assertSame('https://youtu.be/abc', media_url('https://youtu.be/abc'));
        $this->assertNull(media_url(null));

        $user = User::factory()->create();
        $course = Course::factory()->create();
        $lesson = Lesson::factory()->for($course)->withActivities()->create();
        $lesson->videos()->create(['order' => 0, 'url' => '/media/c/v.mp4', 'subtitles' => [['url' => '/media/c/v.en.vtt', 'lang' => 'en', 'label' => 'English']]]);

        $this->actingAs($user)->get($lesson->url())->assertOk()
            ->assertSee('src="http://localhost:8765/c/v.mp4"', false)
            ->assertSee('src="http://localhost:8765/c/v.en.vtt"', false);
    }
}
