<?php

namespace App\Services\Content;

use App\Models\Activity;
use App\Models\Lesson;
use App\Services\Ai\GeminiClient;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Adds one AI-generated practice to a lesson's review pool once the learner has seen
 * every authored one, so a review never just repeats the same 2-3 questions
 * (DECISIONS.md §18). Content stays authored everywhere else; this is the one exception,
 * scoped to reviews only, and never mcq (avoids the option-consistency problem in §5).
 * The generated activity is added to the pool for good — future reviews rotate through
 * it via Planner::pickPractice() like any authored practice.
 */
class ReviewPracticeGenerator
{
    public const FORMS = ['short_answer', 'coding', 'explanation', 'scenario'];

    public function __construct(protected GeminiClient $gemini) {}

    public function generate(Lesson $lesson): Activity
    {
        $existing = $lesson->activities->where('type', 'practice')->values();

        $exemplars = $existing->map(fn (Activity $a) => sprintf(
            '- (%s, %s) %s',
            $a->payload['form'] ?? '?',
            $a->payload['difficulty'] ?? '?',
            $a->payload['prompt'] ?? ''
        ))->implode("\n");

        $prompt = <<<PROMPT
            You are writing one new practice exercise for a spaced-repetition review, for a self-taught
            learner who already passed this lesson before. It must test the same core skill as the lesson
            but must NOT be a copy or trivial rewording of any existing practice below — use a different
            angle, example, or wording.

            Lesson: {$lesson->title}

            Lesson content:
            {$lesson->content}

            Existing practices for this lesson (do not repeat these):
            {$exemplars}

            Write one new practice. form must be one of: short_answer, coding, explanation, scenario
            (never mcq). difficulty must be one of: intro, core, stretch, matched to the existing
            practices' level. prompt and hints are shown to the learner in Persian (Farsi), keeping
            English technical terms and code as-is. expected_outcome and rubric are internal grading
            notes and must be in English. hints must be exactly two hints, a small nudge then a bigger
            one, neither revealing the answer.
            PROMPT;

        $schema = [
            'type' => 'OBJECT',
            'properties' => [
                'title' => ['type' => 'STRING'],
                'form' => ['type' => 'STRING', 'enum' => self::FORMS],
                'difficulty' => ['type' => 'STRING', 'enum' => CourseImporter::DIFFICULTIES],
                'prompt' => ['type' => 'STRING'],
                'expected_outcome' => ['type' => 'STRING'],
                'rubric' => ['type' => 'STRING'],
                'hints' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
            ],
            'required' => ['title', 'form', 'difficulty', 'prompt', 'expected_outcome', 'rubric', 'hints'],
        ];

        $result = $this->gemini->generateJson($prompt, $schema);
        $this->validate($result);

        return $lesson->activities()->create([
            'key' => 'review-ai-'.Str::random(10),
            'type' => 'practice',
            'title' => $result['title'],
            'estimated_minutes' => (int) round($existing->avg('estimated_minutes') ?: 10),
            'generated' => true,
            'payload' => [
                'form' => $result['form'],
                'prompt' => $result['prompt'],
                'expected_outcome' => $result['expected_outcome'],
                'hints' => array_values($result['hints']),
                'rubric' => $result['rubric'],
                'difficulty' => $result['difficulty'],
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $result
     */
    protected function validate(array $result): void
    {
        if (empty($result['title']) || empty($result['prompt']) || empty($result['expected_outcome']) || empty($result['rubric'])) {
            throw new RuntimeException('Gemini returned an incomplete review practice: '.json_encode($result));
        }
        if (! in_array($result['form'] ?? null, self::FORMS, true)) {
            throw new RuntimeException('Gemini returned an invalid practice form: '.json_encode($result));
        }
        if (! in_array($result['difficulty'] ?? null, CourseImporter::DIFFICULTIES, true)) {
            throw new RuntimeException('Gemini returned an invalid practice difficulty: '.json_encode($result));
        }
        if (! is_array($result['hints'] ?? null) || count($result['hints']) !== 2) {
            throw new RuntimeException('Gemini returned the wrong number of hints: '.json_encode($result));
        }
    }
}
