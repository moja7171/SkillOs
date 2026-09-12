<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['lesson_id', 'order', 'title', 'url'])]
class LessonVideo extends Model
{
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * How the lesson page should render this URL: an <iframe> for YouTube/Aparat pages,
     * a native <video> for direct media files, otherwise a plain link.
     *
     * @return array{kind: 'youtube'|'aparat'|'file'|'link', src: string}
     */
    public function embed(): array
    {
        $url = $this->url;

        if (preg_match('~(?:youtube\.com/watch\?v=|youtu\.be/|youtube\.com/embed/)([\w-]{6,})~', $url, $m)) {
            return ['kind' => 'youtube', 'src' => 'https://www.youtube.com/embed/'.$m[1]];
        }

        if (preg_match('~aparat\.com/v/([\w-]+)~', $url, $m)) {
            return ['kind' => 'aparat', 'src' => 'https://www.aparat.com/video/video/embed/videohash/'.$m[1].'/vt/frame'];
        }

        if (preg_match('~\.(mp4|webm|m4v|ogv)(\?.*)?$~i', $url)) {
            return ['kind' => 'file', 'src' => $url];
        }

        return ['kind' => 'link', 'src' => $url];
    }
}
