<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mastery_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('numeric_mastery')->default(0); // 0-1000, never shown
            $table->enum('level', ['not_started', 'learning', 'familiar', 'proficient', 'mastered'])->default('not_started');
            $table->timestamp('last_evaluated_at')->nullable();
            $table->timestamp('next_review_due_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'lesson_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mastery_records');
    }
};
