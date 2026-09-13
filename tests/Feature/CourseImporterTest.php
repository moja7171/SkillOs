<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Attempt;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use App\Services\Content\CourseImporter;
use App\Services\Content\ImportException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class CourseImporterTest extends TestCase
{
    use RefreshDatabase;

    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = storage_path('framework/testing/content-'.uniqid());
        File::makeDirectory($this->root, 0755, true);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->root);
        parent::tearDown();
    }

    public function test_imports_the_shipped_sample_course(): void
    {
        $result = app(CourseImporter::class)->import('sample-course');

        $this->assertSame(3, $result['lessons']);
        $this->assertSame(7, $result['practices']);

        $course = Course::where('slug', 'sample-course')->sole();
        $lessons = $course->lessons()->with(['prerequisites', 'videos', 'activities'])->get();

        $this->assertSame(['variables', 'functions', 'loops'], $lessons->pluck('slug')->all());
        $this->assertSame(['variables'], $lessons[1]->prerequisites->pluck('slug')->all(), 'default prerequisite is the previous lesson');
        $this->assertSame(['variables'], $lessons[2]->prerequisites->pluck('slug')->all(), 'explicit prerequisites override the default');
        $this->assertCount(1, $lessons[0]->videos);
        $this->assertStringContainsString('type()', $lessons[0]->content);
        $this->assertSame(1, $lessons[0]->activities->where('type', 'learn')->count());

        $mcq = $lessons[0]->activities->firstWhere('key', 'type-check');
        $this->assertSame('mcq', $mcq->payload['form']);
        $this->assertSame(1, $mcq->payload['correct_option']);
        $this->assertCount(4, $mcq->payload['options']);
    }

    public function test_video_file_shorthand_resolves_against_video_base_url_with_subtitle_tracks_and_section(): void
    {
        $lesson = $this->lesson('a');
        $lesson['section'] = 'بخش یک';
        $lesson['attachments'] = [['title' => 'اسلایدها', 'file' => 'files/01 slides.pdf'], ['url' => 'https://example.com/nb.ipynb']];
        $lesson['videos'] = [
            ['file' => '001-intro part.mp4', 'subtitle' => 'subs/001 fa.vtt', 'subtitles' => [['file' => 'subs/001 en.vtt', 'lang' => 'en']], 'title' => 'مقدمه'],
            ['url' => 'https://www.youtube.com/watch?v=abc123xyz'],
        ];
        $this->writeCourse(['video_base_url' => '/media/demo/', 'lessons' => [$lesson]]);

        app(CourseImporter::class)->import('t', root: $this->root);

        $stored = Lesson::where('slug', 'a')->sole();
        $this->assertSame('بخش یک', $stored->section);
        $this->assertSame([
            ['title' => 'اسلایدها', 'url' => '/media/demo/files/01%20slides.pdf'],
            ['title' => 'nb.ipynb', 'url' => 'https://example.com/nb.ipynb'],
        ], $stored->attachments);
        $videos = $stored->videos;
        $this->assertSame('/media/demo/001-intro%20part.mp4', $videos[0]->url);
        $this->assertSame([
            ['url' => '/media/demo/subs/001%20en.vtt', 'lang' => 'en', 'label' => 'English subtitles'],
            ['url' => '/media/demo/subs/001%20fa.vtt', 'lang' => 'fa', 'label' => 'زیرنویس فارسی'],
        ], $videos[0]->subtitles, 'explicit tracks first (default), shorthand Persian last');
        $this->assertSame('file', $videos[0]->embed()['kind']);
        $this->assertSame('https://www.youtube.com/watch?v=abc123xyz', $videos[1]->url);
        $this->assertSame([], $videos[1]->subtitles);
    }

    public function test_reimport_updates_content_and_keeps_learner_data(): void
    {
        $this->writeCourse(['lessons' => [$this->lesson('intro', title: 'قدیمی')]]);
        app(CourseImporter::class)->import('t', root: $this->root);

        $lesson = Lesson::where('slug', 'intro')->sole();
        $practice = Activity::where('lesson_id', $lesson->id)->where('key', 'p1')->sole();
        $attempt = Attempt::create(['activity_id' => $practice->id, 'user_id' => User::factory()->create()->id, 'result_status' => 'correct']);

        $this->writeCourse(['lessons' => [$this->lesson('intro', title: 'جدید', content: 'متن تازه')]]);
        app(CourseImporter::class)->import('t', root: $this->root);

        $this->assertSame('جدید', $lesson->fresh()->title);
        $this->assertSame('متن تازه', $lesson->fresh()->content);
        $this->assertSame($practice->id, Activity::where('lesson_id', $lesson->id)->where('key', 'p1')->sole()->id);
        $this->assertSame($practice->id, $attempt->fresh()->activity_id);
        $this->assertSame(1, Lesson::count());
        $this->assertSame(2, Activity::count());
    }

    public function test_prune_removes_lessons_missing_from_files_only_when_asked(): void
    {
        $this->writeCourse(['lessons' => [$this->lesson('a'), $this->lesson('b')]]);
        app(CourseImporter::class)->import('t', root: $this->root);

        $this->writeCourse(['lessons' => [$this->lesson('a')]]);
        app(CourseImporter::class)->import('t', root: $this->root);
        $this->assertSame(2, Lesson::count());

        $result = app(CourseImporter::class)->import('t', prune: true, root: $this->root);
        $this->assertSame(1, $result['pruned']);
        $this->assertSame(['a'], Lesson::pluck('slug')->all());
    }

    public function test_rejects_inconsistent_mcq_before_writing_anything(): void
    {
        $bad = $this->lesson('a');
        $bad['practices'][0] = ['key' => 'q', 'title' => 'س', 'form' => 'mcq', 'prompt' => '?', 'options' => ['a', 'b', 'c'], 'correct_option' => 5,
            'expected_outcome' => 'x', 'hints' => ['h1', 'h2'], 'rubric' => 'r', 'difficulty' => 'intro'];
        $this->writeCourse(['lessons' => [$bad]]);

        $this->expectException(ImportException::class);
        $this->expectExceptionMessage('mcq needs exactly 4 options');

        try {
            app(CourseImporter::class)->import('t', root: $this->root);
        } finally {
            $this->assertSame(0, Course::count());
        }
    }

    public function test_rejects_unknown_prerequisite_and_missing_text(): void
    {
        $this->writeCourse(['lessons' => [$this->lesson('a', prerequisites: ['ghost'])]]);
        try {
            app(CourseImporter::class)->import('t', root: $this->root);
            $this->fail('expected ImportException');
        } catch (ImportException $e) {
            $this->assertStringContainsString('unknown prerequisite "ghost"', $e->getMessage());
        }

        $this->writeCourse(['lessons' => [$this->lesson('b')]]);
        File::delete("{$this->root}/t/lessons/b.md");
        try {
            app(CourseImporter::class)->import('t', root: $this->root);
            $this->fail('expected ImportException');
        } catch (ImportException $e) {
            $this->assertStringContainsString('missing lesson text', $e->getMessage());
        }
    }

    public function test_artisan_command_reports_errors(): void
    {
        $this->artisan('content:import', ['slug' => 'does-not-exist'])
            ->expectsOutputToContain('Course file not found')
            ->assertFailed();

        $this->artisan('content:import', ['slug' => 'sample-course'])
            ->expectsOutputToContain('3 lessons, 7 practices')
            ->assertSuccessful();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function writeCourse(array $overrides): void
    {
        $dir = "{$this->root}/t";
        File::ensureDirectoryExists("$dir/lessons");

        $lessons = $overrides['lessons'];
        foreach ($lessons as &$l) {
            File::put("$dir/lessons/{$l['slug']}.md", $l['content'] ?? "متن {$l['slug']}");
            File::put("$dir/lessons/{$l['slug']}.practices.json", json_encode($l['practices'], JSON_UNESCAPED_UNICODE));
            unset($l['content'], $l['practices']);
        }
        unset($l);

        File::put("$dir/course.json", json_encode(array_merge(['title' => 'تست'], $overrides, ['lessons' => $lessons]), JSON_UNESCAPED_UNICODE));
    }

    /**
     * @param  array<int, string>|null  $prerequisites
     * @return array<string, mixed>
     */
    private function lesson(string $slug, string $title = 'درس', ?string $content = null, ?array $prerequisites = null): array
    {
        $l = [
            'slug' => $slug,
            'title' => $title,
            'content' => $content,
            'practices' => [[
                'key' => 'p1', 'title' => 'تمرین', 'form' => 'coding', 'prompt' => 'بنویس', 'expected_outcome' => 'x',
                'hints' => ['h1', 'h2'], 'rubric' => 'r', 'difficulty' => 'core',
            ]],
        ];
        if ($prerequisites !== null) {
            $l['prerequisites'] = $prerequisites;
        }

        return $l;
    }
}
