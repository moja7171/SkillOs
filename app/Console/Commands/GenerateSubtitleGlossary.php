<?php

namespace App\Console\Commands;

use App\Services\Subtitles\SubtitleTranslator;
use App\Services\Subtitles\VttParser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Throwable;

/**
 * Builds content/<slug>/glossary/<video>.json from a course's English .vtt files: for every
 * cue a Persian translation plus contextual meanings of its non-trivial words and phrases
 * (docs/DECISIONS.md §73). Resumable — a chunk already in the file is skipped, and the file
 * is rewritten after every chunk, so a quota stop loses nothing.
 */
class GenerateSubtitleGlossary extends Command
{
    protected $signature = 'subtitles:glossary
        {slug : Folder name under content/ (and course/)}
        {--video=* : Only videos whose file name contains this text}
        {--chunk=14 : Cues per Gemini request (a relay call is cut off after ~30 s)}
        {--max-requests=0 : Stop after this many requests (0 = no limit) — spreads a free quota over days}
        {--check : Only validate existing glossary files, call nothing}';

    protected $description = 'Generate (or validate with --check) the interactive-subtitle glossary of a course';

    public function handle(SubtitleTranslator $translator, VttParser $parser): int
    {
        $slug = $this->argument('slug');
        $courseFile = base_path("content/$slug/course.json");
        if (! File::exists($courseFile)) {
            $this->error("Course file not found: $courseFile");

            return self::FAILURE;
        }

        $course = json_decode(File::get($courseFile), true);
        $videos = $this->videosOf($course);
        $only = (array) $this->option('video');
        if ($only !== []) {
            $videos = array_filter($videos, fn ($v) => collect($only)->contains(fn ($needle) => str_contains($v['file'], $needle)));
        }

        if ($this->option('check')) {
            return $this->check($slug, $videos, $translator, $parser);
        }

        // A relay call is cut at ~30 s, so give each attempt room for a chunk and let a failing
        // model fall through to the next one.
        config(['services.gemini.attempt_timeout' => 40, 'services.gemini.total_budget' => 120, 'services.gemini.max_attempts' => 4]);

        $chunkSize = max(4, (int) $this->option('chunk'));
        $budget = (int) $this->option('max-requests');
        $requests = 0;

        foreach ($videos as $video) {
            $cues = $this->cuesOf($slug, $video, $parser);
            if ($cues === null) {
                $this->warn("{$video['file']}: no English subtitle file — skipped");

                continue;
            }

            $path = $this->glossaryPath($slug, $video['file']);
            $glossary = File::exists($path) ? (json_decode(File::get($path), true) ?: []) : [];
            $glossary['version'] = 1;
            $glossary['video'] = $video['file'];
            $glossary['cues'] = (array) ($glossary['cues'] ?? []);

            $chunks = array_chunk($cues, $chunkSize);
            $this->line(sprintf('%s — %d cues, %d chunks', $video['file'], count($cues), count($chunks)));

            foreach ($chunks as $n => $chunk) {
                if (collect($chunk)->every(fn ($c) => isset($glossary['cues'][(string) $c['start']]))) {
                    continue;
                }
                if ($budget > 0 && $requests >= $budget) {
                    $this->warn("Stopped after $requests requests (--max-requests). Run again to continue.");

                    return self::SUCCESS;
                }

                $before = array_slice(array_column($chunks[$n - 1] ?? [], 'text'), -3);
                $after = array_slice(array_column($chunks[$n + 1] ?? [], 'text'), 0, 3);
                try {
                    $requests++;
                    $done = $translator->translateChunk($chunk, $course['title'], $video['lesson'], $before, $after);
                } catch (Throwable $e) {
                    $this->error('Request failed: '.$e->getMessage().' — progress is saved; run again later.');

                    return self::FAILURE;
                }

                foreach ($done as $start => $entry) {
                    $glossary['cues'][(string) $start] = $entry;
                }
                $this->save($path, $glossary);
                $this->line(sprintf('  chunk %d/%d: %d/%d cues', $n + 1, count($chunks), count($done), count($chunk)));
            }
        }

        $this->info("Done ($requests requests). Run with --check, then `php artisan content:import $slug`.");

        return self::SUCCESS;
    }

    /**
     * Report videos whose glossary is missing, incomplete or malformed.
     *
     * @param  list<array{file: string, lesson: string}>  $videos
     */
    protected function check(string $slug, array $videos, SubtitleTranslator $translator, VttParser $parser): int
    {
        $problems = 0;
        foreach ($videos as $video) {
            $cues = $this->cuesOf($slug, $video, $parser);
            if ($cues === null) {
                continue;
            }
            $path = $this->glossaryPath($slug, $video['file']);
            if (! File::exists($path)) {
                $this->line("✗ {$video['file']}: no glossary");
                $problems++;

                continue;
            }
            $glossary = json_decode(File::get($path), true);
            $entries = (array) ($glossary['cues'] ?? []);
            $missing = 0;
            $bad = 0;
            foreach ($cues as $cue) {
                $entry = $entries[(string) $cue['start']] ?? null;
                if ($entry === null) {
                    $missing++;
                } elseif ($translator->cleanEntry($cue['text'], $entry) === null) {
                    $bad++;
                }
            }
            $mark = ($missing || $bad) ? '✗' : '✓';
            $problems += ($missing || $bad) ? 1 : 0;
            $this->line(sprintf('%s %s: %d cues, %d missing, %d invalid', $mark, $video['file'], count($cues), $missing, $bad));
        }
        $this->line($problems === 0 ? 'All glossaries complete.' : "$problems video(s) need work.");

        return $problems === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @param  array<string, mixed>  $course
     * @return list<array{file: string, lesson: string}>
     */
    protected function videosOf(array $course): array
    {
        $videos = [];
        foreach ($course['lessons'] as $lesson) {
            foreach ($lesson['videos'] ?? [] as $video) {
                if (! empty($video['file'])) {
                    $videos[] = ['file' => $video['file'], 'lesson' => $lesson['title'], 'subtitles' => $video['subtitles'] ?? []];
                }
            }
        }

        return $videos;
    }

    /**
     * @param  array{file: string, subtitles?: array<int, array<string, string>>}  $video
     * @return list<array{start: int, end: int, text: string}>|null
     */
    protected function cuesOf(string $slug, array $video, VttParser $parser): ?array
    {
        $english = collect($video['subtitles'] ?? [])->firstWhere('lang', 'en');
        $file = base_path("course/$slug/".($english['file'] ?? ''));
        if (! $english || ! File::exists($file)) {
            return null;
        }
        $cues = $parser->parse(File::get($file));

        return $cues === [] ? null : $cues;
    }

    protected function glossaryPath(string $slug, string $videoFile): string
    {
        return base_path("content/$slug/glossary/".pathinfo($videoFile, PATHINFO_FILENAME).'.json');
    }

    /**
     * @param  array<string, mixed>  $glossary
     */
    protected function save(string $path, array $glossary): void
    {
        File::ensureDirectoryExists(dirname($path));
        ksort($glossary['cues'], SORT_NUMERIC);
        $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

        // One cue per line: small files, readable diffs.
        $lines = [];
        foreach ($glossary['cues'] as $start => $entry) {
            $lines[] = '    '.json_encode((string) $start, $flags).': '.json_encode($entry, $flags);
        }
        $body = "{\n  \"version\": 1,\n  \"video\": ".json_encode($glossary['video'], $flags).",\n  \"cues\": {\n"
            .implode(",\n", $lines)."\n  }\n}\n";

        File::put($path, $body);
    }
}
