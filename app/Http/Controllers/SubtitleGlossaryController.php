<?php

namespace App\Http\Controllers;

use App\Models\LessonVideo;
use App\Models\SubtitleLookup;
use App\Services\Subtitles\SubtitleTranslator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use RuntimeException;

/**
 * Interactive subtitles (DECISIONS.md §73): the authored per-cue glossary of a video, and a
 * live, cached lookup for a word or phrase the glossary doesn't cover. Both exist only for
 * videos of courses that opted in (`interactive_subtitles` in course.json, or a glossary file
 * for the video), so the feature and its AI quota stay limited to the courses prepared for it.
 */
class SubtitleGlossaryController extends Controller
{
    public function show(Request $request, LessonVideo $lessonVideo): Response
    {
        abort_unless($lessonVideo->interactive_subtitles, 404);

        // Enabled for the course but no glossary authored for this video yet: an empty one, so
        // every word is looked up live.
        $data = $lessonVideo->glossary?->data ?? '{"version":1,"cues":{}}';

        $response = response($data, 200, [
            'Content-Type' => 'application/json; charset=utf-8',
            'Cache-Control' => 'private, max-age=86400',
        ]);
        $response->setEtag(md5($data));
        $response->isNotModified($request);

        return $response;
    }

    public function lookup(Request $request, LessonVideo $lessonVideo, SubtitleTranslator $translator): JsonResponse
    {
        abort_unless($lessonVideo->interactive_subtitles, 404);

        $data = $request->validate([
            'phrase' => ['required', 'string', 'max:100'],
            'sentence' => ['required', 'string', 'max:400'],
            'before' => ['nullable', 'string', 'max:400'],
            'after' => ['nullable', 'string', 'max:400'],
        ]);

        if (! $translator->occursInCue($data['phrase'], $data['sentence'])) {
            return response()->json(['message' => 'عبارت در این جمله نیست.'], 422);
        }

        $hash = SubtitleLookup::hashFor($data['phrase'], $data['sentence']);
        $lookup = SubtitleLookup::firstWhere('hash', $hash);

        if ($lookup === null) {
            try {
                $result = $translator->lookup(
                    $data['phrase'],
                    $data['sentence'],
                    $lessonVideo->lesson->course->title,
                    $data['before'] ?? null,
                    $data['after'] ?? null,
                );
            } catch (RuntimeException) {
                return response()->json(['message' => 'الان نمی‌شه ترجمه رو گرفت. کمی بعد دوباره امتحان کن.'], 503);
            }

            $lookup = SubtitleLookup::firstOrCreate(['hash' => $hash], [
                'phrase' => $data['phrase'],
                'context' => $data['sentence'],
                'meaning' => $result['fa'],
                'note' => $result['n'],
            ]);
        }

        return response()->json(['fa' => $lookup->meaning, 'n' => $lookup->note]);
    }
}
