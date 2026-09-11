<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');

            // Outcome — kept inline instead of a separate table for MVP speed.
            $table->text('outcome_statement')->nullable();

            // Design (Outcome + Skill structure) approval boundary. Nothing downstream
            // (content/practice/plan) is generated until this is 'approved'.
            $table->enum('design_status', ['draft', 'pending_review', 'approved'])->default('draft');
            // Raw AI output (outcome + proposed skills/dependencies) staged here for
            // human review before being turned into real Skill/SkillDependency rows.
            $table->json('design_draft')->nullable();
            $table->timestamp('design_approved_at')->nullable();

            $table->enum('status', ['active', 'paused', 'archived', 'maintenance'])->default('active');
            $table->unsignedTinyInteger('priority')->default(3);
            $table->unsignedSmallInteger('daily_time_minutes')->nullable();
            $table->time('preferred_time')->nullable();

            $table->timestamp('last_activity_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_items');
    }
};
