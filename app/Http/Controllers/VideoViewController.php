<?php

namespace App\Http\Controllers;

use App\Models\LessonVideo;
use App\Models\VideoView;
use App\Services\Evaluation\AttemptSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VideoViewController extends Controller
{
    /**
     * Fired by player.js when a video reaches its end — records that this learner
     * watched it to completion. Once every video of the lesson is watched, the lesson
     * item is marked done automatically (DECISIONS.md §65).
     */
    public function store(Request $request, LessonVideo $lessonVideo, AttemptSession $sessions): JsonResponse
    {
        $user = $request->user();

        VideoView::updateOrCreate(
            ['user_id' => $user->id, 'lesson_video_id' => $lessonVideo->id],
            ['watched_at' => now()]
        );

        $lesson = $lessonVideo->lesson()->with('videos', 'learnActivity')->first();
        $lessonDone = $lesson->isMarkedDoneBy($user);

        if (! $lessonDone && $lesson->learnActivity && $lesson->allVideosWatchedBy($user)) {
            $sessions->markLessonDone($user, $lesson->learnActivity);
            $lessonDone = true;
        }

        return response()->json(['watched' => true, 'lesson_done' => $lessonDone]);
    }
}
