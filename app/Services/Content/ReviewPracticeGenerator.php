<?php

namespace App\Services\Content;

use App\Models\Activity;
use App\Models\Lesson;
use App\Services\Ai\GeminiClient;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Writes one new 4-option question for a lesson's review, so a review never repeats a
 * question the learner has already answered (DECISIONS.md §70, supersedes §18).
 * The row is kept (attempts point at it) but flagged `generated`, which keeps it out of
 * the lesson's practice list and practice progress.
 *
 * The model's answer is only trusted when it passes the same guard as authored MCQs
 * (§5): exactly four distinct options and a `correct_option_text` that is one of them.
 * Option order is shuffled here, and an answer that is the strictly longest option is
 * rejected (§69), so neither slot nor length gives it away.
 */
class ReviewPracticeGenerator
{
    public const MAX_TRIES = 3;

    public function __construct(protected GeminiClient $gemini) {}

    public function generate(Lesson $lesson): Activity
    {
        $existing = $lesson->activities()->where('type', 'practice')->latest('id')->limit(20)->get();
        $result = $this->askUntilValid($lesson, $existing->map(fn (Activity $a) => '- '.($a->payload['prompt'] ?? ''))->implode("\n"));

        $options = $result['options'];
        $correctText = $result['correct_option_text'];
        shuffle($options);

        return $lesson->activities()->create([
            'key' => 'review-ai-'.Str::random(10),
            'type' => 'practice',
            'title' => $result['title'],
            'estimated_minutes' => 2,
            'generated' => true,
            'payload' => [
                'form' => 'mcq',
                'prompt' => $result['question'],
                'options' => array_values($options),
                'correct_option' => (int) array_search($correctText, $options, true),
                'expected_outcome' => $result['explanation'],
                'hints' => [],
                'rubric' => '',
                'difficulty' => 'core',
            ],
        ]);
    }

    /**
     * @return array{title: string, question: string, options: array<int, string>, correct_option_text: string, explanation: string}
     */
    protected function askUntilValid(Lesson $lesson, string $exemplars): array
    {
        $last = null;
        for ($try = 0; $try < self::MAX_TRIES; $try++) {
            $result = $this->gemini->generateJson($this->prompt($lesson, $exemplars), $this->schema(), GeminiClient::PROFILE_GENERATE);

            try {
                $this->validate($result);

                return $result;
            } catch (RuntimeException $e) {
                $last = $e;
            }
        }

        throw $last;
    }

    protected function prompt(Lesson $lesson, string $exemplars): string
    {
        return <<<PROMPT
            You are writing ONE new multiple-choice question for a spaced-repetition review, for a self-taught
            learner who already studied this lesson. It must test understanding of the lesson, not memory of
            its wording, and must NOT repeat or lightly reword any existing question below.

            Lesson: {$lesson->title}

            Lesson content:
            {$lesson->content}

            Existing questions (do not repeat):
            {$exemplars}

            Rules:
            - Exactly 4 options; exactly one is correct.
            - Prefer a short scenario or a decision over plain recall.
            - Every wrong option must be a believable misconception or a half-true statement, written in the
              same tone and about the same length as the correct one. The correct option must NOT be the
              longest option, and must not contain words that give it away.
            - `question`, `title`, `options` and `explanation` are shown to the learner: write them in Persian
              (Farsi), keeping English technical terms and code as they are.
            - `correct_option_text` must be copied exactly from one of the options.
            - `explanation`: two or three sentences saying why the correct option is right and the main
              wrong ones are not.
            PROMPT;
    }

    /**
     * @return array<string, mixed>
     */
    protected function schema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'title' => ['type' => 'STRING'],
                'question' => ['type' => 'STRING'],
                'options' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                'correct_option_text' => ['type' => 'STRING'],
                'explanation' => ['type' => 'STRING'],
            ],
            'required' => ['title', 'question', 'options', 'correct_option_text', 'explanation'],
        ];
    }

    /**
     * @param  array<string, mixed>  $result
     */
    protected function validate(array $result): void
    {
        foreach (['title', 'question', 'correct_option_text', 'explanation'] as $field) {
            if (! is_string($result[$field] ?? null) || trim($result[$field]) === '') {
                throw new RuntimeException("Gemini returned a review question without {$field}.");
            }
        }

        $options = $result['options'] ?? null;
        if (! is_array($options) || count($options) !== 4 || count(array_unique($options)) !== 4 || in_array('', array_map('trim', $options), true)) {
            throw new RuntimeException('Gemini returned a review question without four distinct options.');
        }

        if (! in_array($result['correct_option_text'], $options, true)) {
            throw new RuntimeException('Gemini returned a review question whose answer is not one of the options.');
        }

        $correctLength = mb_strlen($result['correct_option_text']);
        $longerOthers = collect($options)->reject(fn (string $o) => $o === $result['correct_option_text'])->filter(fn (string $o) => mb_strlen($o) >= $correctLength);
        if ($longerOthers->isEmpty()) {
            throw new RuntimeException('Gemini returned a review question whose answer is the longest option.');
        }
    }
}
