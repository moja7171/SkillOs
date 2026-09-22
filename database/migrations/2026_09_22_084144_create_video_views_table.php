<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // One row = this learner watched this video to the end. Keyed on lesson_video_id,
        // same volatile-across-reimport identity `player.js`'s position memory already
        // relies on (CourseImporter deletes+recreates a lesson's videos on every import) —
        // an accepted tradeoff here too, not a new one.
        Schema::create('video_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_video_id')->constrained()->cascadeOnDelete();
            $table->timestamp('watched_at');
            $table->timestamps();

            $table->unique(['user_id', 'lesson_video_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_views');
    }
};
