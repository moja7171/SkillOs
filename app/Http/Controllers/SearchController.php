<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Cross-course lesson search (DECISIONS.md §26) — a plain LIKE scan over title/summary/
 * content, no separate index. Lesson content isn't enrollment-gated anywhere else in the
 * app, so results aren't filtered to the learner's own enrollments either.
 */
class SearchController extends Controller
{
    public function __invoke(Request $request): View
    {
        $query = trim((string) $request->query('q', ''));

        $results = collect();

        if (mb_strlen($query) >= 2) {
            $results = Lesson::with('course')
                ->where(function ($q) use ($query) {
                    $q->where('title', 'like', "%{$query}%")
                        ->orWhere('summary', 'like', "%{$query}%")
                        ->orWhere('content', 'like', "%{$query}%");
                })
                ->orderBy('title')
                ->limit(40)
                ->get();
        }

        $results = $results->map(fn (Lesson $lesson) => [
            'lesson' => $lesson,
            'snippet' => $lesson->summary ?: $this->snippet($lesson->content, $query),
        ]);

        return view('search.index', ['query' => $query, 'results' => $results]);
    }

    /**
     * ~120 chars of plain text around the query's first match in the lesson body, for
     * when there's no summary to show instead.
     */
    protected function snippet(?string $content, string $query): string
    {
        $plain = trim(preg_replace('/[#*`>_\[\]()\-]/', ' ', (string) $content));
        $pos = mb_stripos($plain, $query);

        if ($pos === false) {
            return mb_substr($plain, 0, 120);
        }

        $start = max(0, $pos - 40);

        return ($start > 0 ? '…' : '').trim(mb_substr($plain, $start, 140)).'…';
    }
}
