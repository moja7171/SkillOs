<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Authored per-cue translations for one video (content/<slug>/glossary/<video>.json),
        // loaded by the importer. Kept out of lesson_videos so a lesson page never drags the
        // (large) JSON along; the flag below says whether the player offers lookups on this video
        // (the course opted in, or the video has a glossary).
        Schema::create('video_glossaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_video_id')->unique()->constrained()->cascadeOnDelete();
            $table->longText('data');
            $table->timestamps();
        });

        Schema::table('lesson_videos', function (Blueprint $table) {
            $table->boolean('interactive_subtitles')->default(false);
        });

        // Live lookups for words the glossary doesn't cover (Gemini), cached so each
        // (phrase, sentence) pair is translated once for every learner. Not tied to a video
        // row: videos are recreated on every import.
        Schema::create('subtitle_lookups', function (Blueprint $table) {
            $table->id();
            $table->char('hash', 40)->unique();
            $table->string('phrase', 120);
            $table->text('context');
            $table->string('meaning');
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subtitle_lookups');
        Schema::table('lesson_videos', function (Blueprint $table) {
            $table->dropColumn('interactive_subtitles');
        });
        Schema::dropIfExists('video_glossaries');
    }
};
