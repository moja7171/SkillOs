<?php

namespace App\Services\Subtitles;

use App\Services\Ai\GeminiClient;
use RuntimeException;

/**
 * Persian meanings for English subtitle text, always judged in context: the sentence being
 * spoken, its neighbours and the course. Used offline by `subtitles:glossary` (whole chunks
 * of cues) and live by the lookup endpoint (one phrase the glossary didn't cover).
 *
 * House rules (docs/AUTHORING.md §1): learner-visible text is Persian with English technical
 * terms kept as they are — never an invented Persian jargon word.
 */
class SubtitleTranslator
{
    public function __construct(protected GeminiClient $gemini) {}

    /**
     * Translate a chunk of consecutive cues.
     *
     * @param  list<array{start: int, text: string}>  $cues
     * @param  list<string>  $before  the lines just before the chunk (context only)
     * @param  list<string>  $after  the lines just after the chunk (context only)
     * @return array<int, array{t: string, w: list<array{s: string, fa: string, n?: string}>}> keyed by cue start (ms)
     */
    public function translateChunk(array $cues, string $courseTitle, string $lessonTitle, array $before = [], array $after = []): array
    {
        $result = $this->gemini->generateJson(
            $this->chunkPrompt($cues, $courseTitle, $lessonTitle, $before, $after),
            $this->chunkSchema(),
            GeminiClient::PROFILE_GENERATE,
        );

        $out = [];
        foreach ((array) ($result['cues'] ?? []) as $row) {
            $index = $row['i'] ?? null;
            if (! is_int($index) || ! isset($cues[$index])) {
                continue;
            }
            $entry = $this->cleanEntry($cues[$index]['text'], (array) $row);
            if ($entry !== null) {
                $out[$cues[$index]['start']] = $entry;
            }
        }

        return $out;
    }

    /**
     * One phrase the learner clicked that the glossary doesn't cover.
     *
     * @return array{fa: string, n: ?string}
     */
    public function lookup(string $phrase, string $sentence, string $courseTitle, ?string $before = null, ?string $after = null): array
    {
        $prompt = <<<PROMPT
You are a precise English→Persian (Farsi) dictionary for a technical learner watching a software-architecture lecture.
Course: {$courseTitle}

The learner clicked this word/phrase in the subtitles: "{$phrase}"
Sentence it appears in: "{$sentence}"
Previous line: "{$before}"
Next line: "{$after}"

Give its meaning AS USED IN THIS SENTENCE (not the most common dictionary sense).
- "fa": the Persian meaning, at most 6 words, plain modern Persian. If it is a technical term with no established Persian equivalent (load balancer, cache, throughput, microservice…), keep the English term and add a short Persian gloss, e.g. "load balancer (توزیع‌کنندهٔ بار بین سرورها)". Never invent Persian jargon.
- "n": optional, ONE short Persian sentence explaining the idea when the term is technical, an idiom or a phrasal verb. Empty string otherwise.
Use correct Persian orthography with half-spaces (می‌شود, نمی‌کند).
PROMPT;

        $result = $this->gemini->generateJson($prompt, [
            'type' => 'OBJECT',
            'properties' => ['fa' => ['type' => 'STRING'], 'n' => ['type' => 'STRING']],
            'required' => ['fa', 'n'],
        ], GeminiClient::PROFILE_GENERATE);

        $fa = $this->cleanText($result['fa'] ?? null, 120);
        if ($fa === null) {
            throw new RuntimeException('Gemini returned no meaning.');
        }

        return ['fa' => $fa, 'n' => $this->cleanText($result['n'] ?? null, 240)];
    }

    /**
     * Keep an entry only if it is usable: a Persian sentence translation, and glossary items
     * whose surface text really occurs in the cue (the player matches them against the words
     * on screen) with a non-empty meaning.
     *
     * @param  array<string, mixed>  $row
     * @return array{t: string, w: list<array{s: string, fa: string, n?: string}>}|null
     */
    public function cleanEntry(string $cueText, array $row): ?array
    {
        $t = $this->cleanText($row['t'] ?? null, 400);
        if ($t === null || ! preg_match('/\p{Arabic}/u', $t)) {
            return null;
        }

        $words = [];
        $seen = [];
        foreach ((array) ($row['w'] ?? []) as $item) {
            $s = is_array($item) ? trim((string) ($item['s'] ?? '')) : '';
            $fa = is_array($item) ? $this->cleanText($item['fa'] ?? null, 120) : null;
            if ($s === '' || $fa === null || ! $this->occursInCue($s, $cueText)) {
                continue;
            }
            $key = mb_strtolower($s);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $word = ['s' => $s, 'fa' => $fa];
            $n = $this->cleanText($item['n'] ?? null, 240);
            if ($n !== null) {
                $word['n'] = $n;
            }
            $words[] = $word;
        }

        return ['t' => $t, 'w' => $words];
    }

    /** Whole-word, case-insensitive occurrence (so "set" doesn't match inside "settings"). */
    public function occursInCue(string $surface, string $cueText): bool
    {
        $quoted = preg_replace('/\s+/u', '\s+', preg_quote(trim($surface), '/'));
        $pattern = '/(?<![\p{L}\p{N}])'.$quoted.'(?![\p{L}\p{N}])/iu';

        return (bool) preg_match($pattern, $cueText);
    }

    protected function cleanText(mixed $value, int $max): ?string
    {
        if (! is_string($value)) {
            return null;
        }
        $value = trim(preg_replace('/\s+/u', ' ', $value));

        return $value === '' || mb_strlen($value) > $max ? null : $value;
    }

    /**
     * @param  list<array{start: int, text: string}>  $cues
     * @param  list<string>  $before
     * @param  list<string>  $after
     */
    protected function chunkPrompt(array $cues, string $courseTitle, string $lessonTitle, array $before, array $after): string
    {
        $lines = collect($cues)->map(fn ($c, $i) => "[{$i}] {$c['text']}")->implode("\n");
        $before = $before === [] ? '(start of the video)' : implode(' ', $before);
        $after = $after === [] ? '(end of the video)' : implode(' ', $after);

        return <<<PROMPT
You prepare an interactive-subtitle glossary for Persian-speaking software professionals learning from an English lecture.
Course: {$courseTitle}
Lesson: {$lessonTitle}

Lines are consecutive pieces of ONE spoken lecture, indexed [0], [1], … They may start or end mid-sentence.
Context before the first line: {$before}
Context after the last line: {$after}

For EVERY indexed line return:
- "i": the index.
- "t": a natural, accurate Persian translation of that line. Keep English technical terms (scalability, load balancer, cache, microservices, API, latency, throughput, database names…) in English inside the Persian sentence; do not invent Persian jargon. A line that is a fragment should be translated as a fragment, not completed.
- "w": the words and phrases a learner might tap to understand — technical terms, multi-word terms (treat "load balancer", "single point of failure", "eventual consistency" as ONE item), idioms and phrasal verbs, and less-common vocabulary. Skip trivial words (the, is, and, to, we, that…) and anything an intermediate learner knows: everyday vocabulary (request, response, table, row, column, advantages, duration, optionally, actively, summarized, categorize, interact…) and bare generic words such as software, course, lecture, structure, system, database are NOT worth an item unless they are part of a longer term or used in an unusual sense here. 0–4 items per line, usually 0–2; a line with nothing worth tapping gets an empty list.
  Each item: "s" = the exact text as it appears in that line (same spelling; case may differ), "fa" = its meaning AS USED IN THIS LINE, at most 6 words (for a technical term with no established Persian equivalent keep the English term and add a short Persian gloss), "n" = optional ONE short Persian sentence that explains the concept when it is technical, an idiom or a phrasal verb (empty string otherwise).

Accuracy matters more than coverage: when unsure of a word's sense in this lecture, use the surrounding lines — a general-English word usually has an everyday sense here, not a technical one (e.g. "the most critical step" means essential/حیاتی, not a crisis). In "t", leave the technical terms of the field in English exactly as spoken (relational database, primary key, response time, latency, message queue…) instead of replacing them with a Persian equivalent; ordinary words that Persian speakers use daily are translated normally, never left in English (software → نرم‌افزار, question → سؤال, lecture → جلسه/درس, architect → معمار, request, response, table, row…); translate what the speaker MEANS, not word by word — resolve pronouns and filler from context ("to become one" = to become that role; "Well, okay" = a hedge, not praise) and pick the sense that fits ("rewarding" = ارزشمند/رضایت‌بخش); only the "fa" field gives the Persian meaning. Spell each Persian word the same way every time, with half-spaces. Use correct Persian orthography with half-spaces (می‌شود, نمی‌کند, ‌ها after a Latin word).

Lines:
{$lines}
PROMPT;
    }

    /**
     * @return array<string, mixed>
     */
    protected function chunkSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'cues' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'i' => ['type' => 'INTEGER'],
                            't' => ['type' => 'STRING'],
                            'w' => [
                                'type' => 'ARRAY',
                                'items' => [
                                    'type' => 'OBJECT',
                                    'properties' => [
                                        's' => ['type' => 'STRING'],
                                        'fa' => ['type' => 'STRING'],
                                        'n' => ['type' => 'STRING'],
                                    ],
                                    'required' => ['s', 'fa', 'n'],
                                ],
                            ],
                        ],
                        'required' => ['i', 't', 'w'],
                    ],
                ],
            ],
            'required' => ['cues'],
        ];
    }
}
