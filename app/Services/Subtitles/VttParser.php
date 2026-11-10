<?php

namespace App\Services\Subtitles;

/**
 * Reads a WebVTT file into cues. A cue is identified everywhere (glossary files, the player)
 * by its start time in milliseconds, which both sides can compute from the same file.
 */
class VttParser
{
    /**
     * @return list<array{start: int, end: int, text: string}>
     */
    public function parse(string $vtt): array
    {
        $vtt = preg_replace('/^\xEF\xBB\xBF/', '', $vtt);
        $blocks = preg_split('/\R{2,}/u', trim($vtt)) ?: [];
        $cues = [];

        foreach ($blocks as $block) {
            $lines = preg_split('/\R/u', trim($block)) ?: [];
            $timing = null;
            foreach ($lines as $i => $line) {
                if (str_contains($line, '-->')) {
                    $timing = $i;
                    break;
                }
            }
            if ($timing === null || ! preg_match('/^\s*([\d:.]+)\s*-->\s*([\d:.]+)/', $lines[$timing], $m)) {
                continue;
            }

            $text = collect(array_slice($lines, $timing + 1))
                ->map(fn (string $l) => trim(strip_tags($l)))
                ->filter()
                ->implode(' ');
            $text = html_entity_decode(trim(preg_replace('/\s+/u', ' ', $text)), ENT_QUOTES | ENT_HTML5);

            if ($text === '' || $this->isWatermark($text)) {
                continue;
            }

            $cues[] = ['start' => $this->milliseconds($m[1]), 'end' => $this->milliseconds($m[2]), 'text' => $text];
        }

        return $cues;
    }

    public function milliseconds(string $stamp): int
    {
        $parts = array_reverse(explode(':', $stamp));
        $seconds = (float) ($parts[0] ?? 0) + 60 * (int) ($parts[1] ?? 0) + 3600 * (int) ($parts[2] ?? 0);

        return (int) round($seconds * 1000);
    }

    /** The platform's "--- GIT.IR e-Learning Platform ---" banner cue is not speech. */
    protected function isWatermark(string $text): bool
    {
        return (bool) preg_match('/^-{2,}.*-{2,}$/', $text);
    }
}
