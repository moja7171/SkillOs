<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One topic inside a course: the unit of mastery, practice and review.
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('slug'); // stable identity across re-imports
            $table->unsignedSmallInteger('order')->default(0);
            $table->string('title');
            $table->string('section')->nullable(); // display grouping only; no behaviour attached
            $table->text('summary')->nullable();
            $table->longText('content')->nullable(); // rewritten lesson text, markdown
            $table->json('key_points')->nullable();
            $table->json('common_mistakes')->nullable();
            $table->json('attachments')->nullable(); // [{title, url}] slides, notebooks, source files
            $table->unsignedSmallInteger('estimated_minutes')->default(10);
            $table->timestamps();

            $table->unique(['course_id', 'slug']);
        });

        Schema::create('lesson_videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('order')->default(0);
            $table->string('title')->nullable();
            $table->string('url', 2000); // served file or external link
            $table->json('subtitles')->nullable(); // [{url, lang, label}] WebVTT tracks; the player's CC menu toggles them
            $table->timestamps();
        });

        Schema::create('lesson_prerequisites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->foreignId('prerequisite_lesson_id')->constrained('lessons')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['lesson_id', 'prerequisite_lesson_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_prerequisites');
        Schema::dropIfExists('lesson_videos');
        Schema::dropIfExists('lessons');
    }
};
