<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Reusable activity templates: exactly one 'learn' per lesson plus its authored practices.
        // Execution state lives on attempts and plan_items, never here.
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->string('key'); // 'learn' or the practice key from the content file
            $table->enum('type', ['learn', 'practice']);
            $table->string('title');
            $table->unsignedSmallInteger('estimated_minutes')->default(10);
            // Practice: form, prompt, options/correct_option (mcq), expected_outcome,
            // hints[2], rubric, difficulty. Learn: empty.
            $table->json('payload')->nullable();
            // AI-generated review practice (never mcq), added to the pool once the authored
            // ones are exhausted (DECISIONS.md §18) — never touched by content:import --prune.
            $table->boolean('generated')->default(false);
            $table->timestamps();

            $table->unique(['lesson_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
