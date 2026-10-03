<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * DECISIONS.md §65: the lesson item is now done only by `lesson_done` (every video watched, or
 * the manual mark) — mastery no longer fills it. Give every learner who already had a lesson
 * "finished" under the old rules (all videos watched, or mastery at «آشنا»+) the same
 * `lesson_done` attempt the new flow would have recorded, so nobody loses a filled dot.
 * Written as a migration so `/_ops/update` runs it on the shared host (no shell there).
 * Idempotent; touches no mastery numbers (the attempt is inserted directly, not finalized).
 */
return new class extends Migration
{
    public function up(): void
    {
        $learnActivityByLesson = DB::table('activities')->where('type', 'learn')->pluck('id', 'lesson_id');
        if ($learnActivityByLesson->isEmpty()) {
            return;
        }

        $alreadyDone = DB::table('attempts')
            ->whereIn('activity_id', $learnActivityByLesson->values())
            ->where('evidence->source', 'lesson_done')
            ->get(['user_id', 'activity_id'])
            ->mapWithKeys(fn ($row) => [$row->user_id.':'.$row->activity_id => true]);

        $videosPerLesson = DB::table('lesson_videos')->selectRaw('lesson_id, count(*) as total')->groupBy('lesson_id')->pluck('total', 'lesson_id');

        $watchedAll = DB::table('video_views')
            ->join('lesson_videos', 'lesson_videos.id', '=', 'video_views.lesson_video_id')
            ->selectRaw('video_views.user_id, lesson_videos.lesson_id, count(*) as watched')
            ->groupBy('video_views.user_id', 'lesson_videos.lesson_id')
            ->get()
            ->filter(fn ($row) => $row->watched >= ($videosPerLesson[$row->lesson_id] ?? PHP_INT_MAX))
            ->map(fn ($row) => [$row->user_id, $row->lesson_id]);

        $viaMastery = DB::table('mastery_records')
            ->whereIn('level', ['familiar', 'proficient', 'mastered'])
            ->get(['user_id', 'lesson_id'])
            ->map(fn ($row) => [$row->user_id, $row->lesson_id]);

        $now = now();
        $rows = $watchedAll->merge($viaMastery)
            ->unique(fn ($pair) => $pair[0].':'.$pair[1])
            ->filter(fn ($pair) => isset($learnActivityByLesson[$pair[1]]) && ! isset($alreadyDone[$pair[0].':'.$learnActivityByLesson[$pair[1]]]))
            ->map(fn ($pair) => [
                'activity_id' => $learnActivityByLesson[$pair[1]],
                'user_id' => $pair[0],
                'started_at' => $now,
                'completed_at' => $now,
                'result_status' => 'completed',
                'hint_level' => 0,
                'evidence' => json_encode(['source' => 'lesson_done', 'backfill' => true]),
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->values();

        foreach ($rows->chunk(500) as $chunk) {
            DB::table('attempts')->insert($chunk->all());
        }
    }

    public function down(): void
    {
        DB::table('attempts')->where('evidence->backfill', true)->delete();
    }
};
