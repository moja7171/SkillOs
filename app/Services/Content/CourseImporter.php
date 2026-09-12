<?php

namespace App\Services\Content;

use App\Models\Activity;
use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Loads content/<slug>/ into the catalog (DESIGN.md §4.1). Everything is upserted by
 * stable identifiers (course slug, lesson slug, practice key) so attempts and mastery
 * that point at existing rows survive a re-import.
 */
class CourseImporter
{
    public const FORMS = ['mcq', 'short_answer', 'coding', 'explanation', 'scenario'];

    public const DIFFICULTIES = ['intro', 'core', 'stretch'];

    /**
     * @return array{course: Course, lessons: int, practices: int, pruned: int}
     */
    public function import(string $slug, bool $prune = false, ?string $root = null): array
    {
        $dir = rtrim($root ?? base_path('content'), '/').'/'.$slug;
        $courseFile = "$dir/course.json";

        if (! File::exists($courseFile)) {
            throw new ImportException("Course file not found: $courseFile");
        }

        $data = $this->readJson($courseFile);
        $this->requireKeys($data, ['title', 'lessons'], 'course.json');

        $lessonsData = $this->validateLessons($data['lessons'], $dir);

        return DB::transaction(function () use ($slug, $data, $lessonsData, $prune) {
            $course = Course::updateOrCreate(['slug' => $slug], [
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'outcome_statement' => $data['outcome_statement'] ?? null,
                'source_note' => $data['source_note'] ?? null,
            ]);

            $lessonsBySlug = [];
            $practiceCount = 0;

            foreach ($lessonsData as $order => $ld) {
                $lesson = Lesson::updateOrCreate(['course_id' => $course->id, 'slug' => $ld['slug']], [
                    'order' => $order,
                    'title' => $ld['title'],
                    'summary' => $ld['summary'] ?? null,
                    'content' => $ld['content'],
                    'key_points' => $ld['key_points'] ?? [],
                    'common_mistakes' => $ld['common_mistakes'] ?? [],
                    'estimated_minutes' => (int) ($ld['estimated_minutes'] ?? 10),
                ]);
                $lessonsBySlug[$ld['slug']] = $lesson;

                // Videos carry no learner state: replace wholesale.
                $lesson->videos()->delete();
                foreach ($ld['videos'] ?? [] as $i => $video) {
                    $lesson->videos()->create(['order' => $i, 'title' => $video['title'] ?? null, 'url' => $video['url']]);
                }

                Activity::updateOrCreate(['lesson_id' => $lesson->id, 'key' => 'learn'], [
                    'type' => 'learn',
                    'title' => $ld['title'],
                    'estimated_minutes' => (int) ($ld['estimated_minutes'] ?? 10),
                    'payload' => [],
                ]);

                $keys = ['learn'];
                foreach ($ld['practices'] as $p) {
                    $keys[] = $p['key'];
                    $payload = [
                        'form' => $p['form'],
                        'prompt' => $p['prompt'],
                        'expected_outcome' => $p['expected_outcome'],
                        'hints' => $p['hints'],
                        'rubric' => $p['rubric'],
                        'difficulty' => $p['difficulty'],
                    ];
                    if ($p['form'] === 'mcq') {
                        $payload['options'] = $p['options'];
                        $payload['correct_option'] = (int) $p['correct_option'];
                    }
                    Activity::updateOrCreate(['lesson_id' => $lesson->id, 'key' => $p['key']], [
                        'type' => 'practice',
                        'title' => $p['title'],
                        'estimated_minutes' => (int) ($p['estimated_minutes'] ?? 10),
                        'payload' => $payload,
                    ]);
                    $practiceCount++;
                }

                if ($prune) {
                    $lesson->activities()->whereNotIn('key', $keys)->delete();
                }
            }

            // Prerequisites: replace edges; default is the previous lesson in order.
            $previous = null;
            foreach ($lessonsData as $ld) {
                $lesson = $lessonsBySlug[$ld['slug']];
                $prereqSlugs = array_key_exists('prerequisites', $ld)
                    ? $ld['prerequisites']
                    : ($previous ? [$previous->slug] : []);
                $lesson->prerequisites()->sync(collect($prereqSlugs)->map(fn ($s) => $lessonsBySlug[$s]->id)->all());
                $previous = $lesson;
            }

            $pruned = 0;
            if ($prune) {
                $pruned = $course->lessons()->whereNotIn('slug', array_keys($lessonsBySlug))->delete();
            }

            return ['course' => $course, 'lessons' => count($lessonsData), 'practices' => $practiceCount, 'pruned' => $pruned];
        });
    }

    /**
     * Reads lesson text + practices from disk and validates everything before any write.
     *
     * @param  array<int, array<string, mixed>>  $lessons
     * @return array<int, array<string, mixed>>
     */
    protected function validateLessons(array $lessons, string $dir): array
    {
        if ($lessons === []) {
            throw new ImportException('course.json: "lessons" must not be empty.');
        }

        $slugs = array_column($lessons, 'slug');
        if (count($slugs) !== count(array_unique($slugs))) {
            throw new ImportException('course.json: duplicate lesson slugs.');
        }

        $out = [];
        foreach ($lessons as $i => $ld) {
            $where = "course.json lessons[$i]";
            $this->requireKeys($ld, ['slug', 'title'], $where);

            if (! preg_match('/^[a-z0-9][a-z0-9-]*$/', $ld['slug'])) {
                throw new ImportException("$where: slug \"{$ld['slug']}\" must be lowercase letters, digits and dashes.");
            }

            $mdFile = "$dir/lessons/{$ld['slug']}.md";
            if (! File::exists($mdFile)) {
                throw new ImportException("$where: missing lesson text $mdFile");
            }
            $ld['content'] = trim(File::get($mdFile));

            foreach ($ld['prerequisites'] ?? [] as $pre) {
                if (! in_array($pre, $slugs, true)) {
                    throw new ImportException("$where: unknown prerequisite \"$pre\".");
                }
                if ($pre === $ld['slug']) {
                    throw new ImportException("$where: a lesson cannot require itself.");
                }
            }

            foreach ($ld['videos'] ?? [] as $v => $video) {
                if (empty($video['url'])) {
                    throw new ImportException("$where videos[$v]: url is required.");
                }
            }

            $practicesFile = "$dir/lessons/{$ld['slug']}.practices.json";
            $ld['practices'] = File::exists($practicesFile) ? $this->validatePractices($this->readJson($practicesFile), $practicesFile) : [];

            $out[] = $ld;
        }

        return $out;
    }

    /**
     * @param  array<int, array<string, mixed>>  $practices
     * @return array<int, array<string, mixed>>
     */
    protected function validatePractices(array $practices, string $file): array
    {
        $keys = [];
        foreach ($practices as $i => $p) {
            $where = basename($file)." [$i]";
            $this->requireKeys($p, ['key', 'title', 'form', 'prompt', 'expected_outcome', 'hints', 'rubric', 'difficulty'], $where);

            if ($p['key'] === 'learn' || in_array($p['key'], $keys, true)) {
                throw new ImportException("$where: key \"{$p['key']}\" is reserved or duplicated.");
            }
            $keys[] = $p['key'];

            if (! in_array($p['form'], self::FORMS, true)) {
                throw new ImportException("$where: form must be one of ".implode(', ', self::FORMS).'.');
            }
            if (! in_array($p['difficulty'], self::DIFFICULTIES, true)) {
                throw new ImportException("$where: difficulty must be one of ".implode(', ', self::DIFFICULTIES).'.');
            }
            if (! is_array($p['hints']) || count($p['hints']) !== 2) {
                throw new ImportException("$where: exactly two hints are required.");
            }
            if ($p['form'] === 'mcq') {
                $options = $p['options'] ?? null;
                $correct = $p['correct_option'] ?? null;
                if (! is_array($options) || count($options) !== 4) {
                    throw new ImportException("$where: mcq needs exactly 4 options.");
                }
                if (! is_int($correct) || $correct < 0 || $correct > 3) {
                    throw new ImportException("$where: mcq correct_option must be an index 0-3.");
                }
            }
        }

        return $practices;
    }

    /**
     * @return array<string, mixed>
     */
    protected function readJson(string $file): array
    {
        $data = json_decode(File::get($file), true);

        if (! is_array($data)) {
            throw new ImportException("Invalid JSON in $file: ".json_last_error_msg());
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $keys
     */
    protected function requireKeys(array $data, array $keys, string $where): void
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $data) || $data[$key] === '' || $data[$key] === null) {
                throw new ImportException("$where: \"$key\" is required.");
            }
        }
    }
}
