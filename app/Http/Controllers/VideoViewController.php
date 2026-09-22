<?php

namespace App\Http\Controllers;

use App\Models\LessonVideo;
use App\Models\VideoView;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VideoViewController extends Controller
{
    /**
     * Fired by player.js when a video reaches its end — records that this learner
     * watched it to completion, gating the "انجام دادم" button (Lesson::allVideosWatchedBy).
     */
    public function store(Request $request, LessonVideo $lessonVideo): JsonResponse
    {
        VideoView::updateOrCreate(
            ['user_id' => $request->user()->id, 'lesson_video_id' => $lessonVideo->id],
            ['watched_at' => now()]
        );

        return response()->json(['watched' => true]);
    }
}
