<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonVideo;
use App\Models\SubtitleLookup;
use App\Models\User;
use App\Services\Ai\GeminiClient;
use App\Services\Content\CourseImporter;
use App\Services\Subtitles\SubtitleTranslator;
use App\Services\Subtitles\VttParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SubtitleGlossaryTest extends TestCase
{
    use RefreshDatabase;

    private function video(bool $glossary = true): LessonVideo
    {
        $lesson = Lesson::factory()->for(Course::factory())->create();
        $video = $lesson->videos()->create(['order' => 0, 'url' => '/media/x/a.mp4', 'subtitles' => [], 'interactive_subtitles' => $glossary]);
        if ($glossary) {
            $video->glossary()->create(['data' => json_encode(['version' => 1, 'cues' => ['3311' => ['t' => 'سلام', 'w' => []]]])]);
        }

        return $video;
    }

    public function test_vtt_parser_keys_cues_by_start_ms_and_drops_the_platform_banner(): void
    {
        $vtt = "WEBVTT\n\n00:00:00.0 --> 00:00:10.0\n--- GIT.IR e-Learning Platform ---\n\n00:00:03.311 --> 00:00:05.000\nIn this <i>lecture</i>,\nwe'll start.\n\n01:02:03.5 --> 01:02:04.0\nLater.\n";

        $cues = (new VttParser)->parse($vtt);

        $this->assertSame([
            ['start' => 3311, 'end' => 5000, 'text' => "In this lecture, we'll start."],
            ['start' => 3723500, 'end' => 3724000, 'text' => 'Later.'],
        ], $cues);
    }

    public function test_the_importer_loads_a_video_glossary_and_flags_the_video(): void
    {
        $root = storage_path('framework/testing/gloss-'.uniqid());
        File::makeDirectory("$root/t/lessons", 0755, true);
        File::makeDirectory("$root/t/glossary", 0755, true);
        File::put("$root/t/lessons/a.md", "## درس\n\nمتن درس.");
        File::put("$root/t/lessons/a.practices.json", '[]');
        File::put("$root/t/glossary/001-intro.json", json_encode(['version' => 1, 'cues' => ['3311' => ['t' => 'سلام', 'w' => []]]]));
        File::put("$root/t/course.json", json_encode(['title' => 'دوره', 'lessons' => [[
            'slug' => 'a', 'title' => 'درس', 'estimated_minutes' => 5,
            'videos' => [['file' => '001-intro.mp4'], ['file' => '002-other.mp4']],
        ]]]));

        app(CourseImporter::class)->import('t', root: $root);
        File::deleteDirectory($root);

        $videos = Lesson::where('slug', 'a')->sole()->videos;
        $this->assertTrue($videos[0]->interactive_subtitles);
        $this->assertSame('سلام', json_decode($videos[0]->glossary->data, true)['cues']['3311']['t']);
        $this->assertFalse($videos[1]->interactive_subtitles);
        $this->assertNull($videos[1]->glossary);
    }

    public function test_a_course_can_opt_in_before_any_glossary_exists(): void
    {
        $root = storage_path('framework/testing/gloss-'.uniqid());
        File::makeDirectory("$root/t/lessons", 0755, true);
        File::put("$root/t/lessons/a.md", "## درس\n\nمتن درس.");
        File::put("$root/t/lessons/a.practices.json", '[]');
        File::put("$root/t/course.json", json_encode(['title' => 'دوره', 'interactive_subtitles' => true, 'lessons' => [[
            'slug' => 'a', 'title' => 'درس', 'estimated_minutes' => 5, 'videos' => [['file' => '001-intro.mp4']],
        ]]]));

        app(CourseImporter::class)->import('t', root: $root);
        File::deleteDirectory($root);

        $video = Lesson::where('slug', 'a')->sole()->videos->sole();
        $this->assertTrue($video->interactive_subtitles);
        $this->assertNull($video->glossary);

        // No glossary yet → an empty one (every word is looked up live), not a 404.
        $this->actingAs(User::factory()->create())->get(route('lesson-videos.glossary', $video))
            ->assertOk()->assertExactJson(['version' => 1, 'cues' => []]);
    }

    public function test_the_glossary_is_served_as_json_and_only_for_videos_that_have_one(): void
    {
        $user = User::factory()->create();
        $with = $this->video();
        $without = $this->video(glossary: false);

        $response = $this->actingAs($user)->get(route('lesson-videos.glossary', $with))->assertOk();
        $this->assertSame('سلام', $response->json('cues.3311.t'));

        $this->actingAs($user)->get(route('lesson-videos.glossary', $with), ['If-None-Match' => $response->headers->get('ETag')])->assertStatus(304);
        $this->actingAs($user)->get(route('lesson-videos.glossary', $without))->assertNotFound();
        auth()->logout();
        $this->get(route('lesson-videos.glossary', $with))->assertRedirect(route('login'));
    }

    public function test_the_lesson_page_offers_lookups_only_for_a_video_with_a_glossary(): void
    {
        $user = User::factory()->create();
        $video = $this->video();

        $this->actingAs($user)->get(route('lessons.show', [$video->lesson->course, $video->lesson]))->assertOk()
            ->assertSee('data-glossary-url="'.route('lesson-videos.glossary', $video).'"', false);

        $video->update(['interactive_subtitles' => false]);
        $this->actingAs($user)->get(route('lessons.show', [$video->lesson->course, $video->lesson]))->assertOk()
            ->assertDontSee('data-glossary-url', false);
    }

    public function test_a_live_lookup_is_translated_once_and_then_served_from_the_cache(): void
    {
        $user = User::factory()->create();
        $video = $this->video();
        $this->mock(GeminiClient::class)->shouldReceive('generateJson')->once()
            ->andReturn(['fa' => 'در دسترس‌پذیری بالا', 'n' => 'سیستم بیشتر وقت‌ها کار می‌کند.']);

        $payload = ['phrase' => 'High Availability', 'sentence' => 'We need high availability here.'];
        $this->actingAs($user)->postJson(route('lesson-videos.lookup', $video), $payload)
            ->assertOk()->assertJson(['fa' => 'در دسترس‌پذیری بالا', 'n' => 'سیستم بیشتر وقت‌ها کار می‌کند.']);
        // Same phrase and sentence (different case/spacing): no second Gemini call (->once()).
        $this->actingAs($user)->postJson(route('lesson-videos.lookup', $video), ['phrase' => 'high  availability'] + $payload)
            ->assertOk()->assertJson(['fa' => 'در دسترس‌پذیری بالا']);

        $this->assertSame(1, SubtitleLookup::count());
    }

    public function test_lookup_rejects_a_phrase_that_is_not_in_the_sentence_and_videos_without_a_glossary(): void
    {
        $user = User::factory()->create();
        $this->mock(GeminiClient::class)->shouldNotReceive('generateJson');

        $this->actingAs($user)->postJson(route('lesson-videos.lookup', $this->video()), ['phrase' => 'cache', 'sentence' => 'We need availability.'])
            ->assertStatus(422);
        $this->actingAs($user)->postJson(route('lesson-videos.lookup', $this->video(glossary: false)), ['phrase' => 'cache', 'sentence' => 'A cache.'])
            ->assertNotFound();
    }

    public function test_a_failed_lookup_answers_503_with_a_persian_message_and_caches_nothing(): void
    {
        $user = User::factory()->create();
        $video = $this->video();
        $this->mock(GeminiClient::class)->shouldReceive('generateJson')->andThrow(new \RuntimeException('down'));

        $this->actingAs($user)->postJson(route('lesson-videos.lookup', $video), ['phrase' => 'cache', 'sentence' => 'A cache helps.'])
            ->assertStatus(503)->assertJsonPath('message', fn ($m) => str_contains($m, 'دوباره'));
        $this->assertSame(0, SubtitleLookup::count());
    }

    public function test_translate_chunk_keeps_only_usable_entries(): void
    {
        $this->mock(GeminiClient::class)->shouldReceive('generateJson')->once()->andReturn(['cues' => [
            ['i' => 0, 't' => 'ما به یک load balancer نیاز داریم.', 'w' => [
                ['s' => 'Load Balancer', 'fa' => 'load balancer (توزیع‌کنندهٔ بار)', 'n' => 'ترافیک را بین سرورها پخش می‌کند.'],
                ['s' => 'cache', 'fa' => 'حافظهٔ نهان', 'n' => ''],   // not in the cue → dropped
                ['s' => 'need', 'fa' => '', 'n' => ''],              // no meaning → dropped
            ]],
            ['i' => 1, 't' => 'English only, no Persian', 'w' => []],   // no Persian → dropped
            ['i' => 9, 't' => 'ایندکس نامعتبر', 'w' => []],            // unknown index → dropped
        ]]);

        $cues = [['start' => 1000, 'text' => 'We need a load balancer.'], ['start' => 2000, 'text' => 'Next line.']];
        $out = app(SubtitleTranslator::class)->translateChunk($cues, 'دوره', 'درس');

        $this->assertSame([1000], array_keys($out));
        $this->assertSame([['s' => 'Load Balancer', 'fa' => 'load balancer (توزیع‌کنندهٔ بار)', 'n' => 'ترافیک را بین سرورها پخش می‌کند.']], $out[1000]['w']);
    }

    public function test_the_glossary_command_writes_resumable_files_and_checks_them(): void
    {
        $slug = '_gloss-test-'.uniqid();
        $content = base_path("content/$slug");
        $media = base_path("course/$slug");
        File::makeDirectory($media, 0755, true);
        File::makeDirectory($content, 0755, true);
        File::put("$media/001-a.en.vtt", "WEBVTT\n\n00:00:01.000 --> 00:00:02.000\nWe use a cache.\n\n00:00:03.000 --> 00:00:04.000\nIt is fast.\n");
        File::put("$content/course.json", json_encode(['title' => 'دوره', 'lessons' => [[
            'slug' => 'a', 'title' => 'درس', 'videos' => [['file' => '001-a.mp4', 'subtitles' => [['file' => '001-a.en.vtt', 'lang' => 'en']]]],
        ]]]));

        try {
            $this->mock(GeminiClient::class)->shouldReceive('generateJson')->once()->andReturn(['cues' => [
                ['i' => 0, 't' => 'ما از cache استفاده می‌کنیم.', 'w' => [['s' => 'cache', 'fa' => 'حافظهٔ نهان', 'n' => '']]],
                ['i' => 1, 't' => 'سریع است.', 'w' => []],
            ]]);

            $this->artisan('subtitles:glossary', ['slug' => $slug])->assertSuccessful();
            $file = json_decode(File::get("$content/glossary/001-a.json"), true);
            $this->assertSame(['1000', '3000'], array_map('strval', array_keys($file['cues'])));

            // Second run: everything is already there → no further Gemini call (->once() above).
            $this->artisan('subtitles:glossary', ['slug' => $slug])->assertSuccessful();
            $this->artisan('subtitles:glossary', ['slug' => $slug, '--check' => true])->assertSuccessful();
        } finally {
            File::deleteDirectory($content);
            File::deleteDirectory($media);
        }
    }
}
