<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['lesson_video_id', 'data'])]
class VideoGlossary extends Model
{
    public function video(): BelongsTo
    {
        return $this->belongsTo(LessonVideo::class, 'lesson_video_id');
    }
}
