<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            // 'completed' is for learn activities (no right/wrong); the rest are practice outcomes.
            $table->enum('result_status', ['started', 'completed', 'correct', 'correct_with_hint', 'incorrect', 'abandoned'])->default('started');
            $table->unsignedTinyInteger('hint_level')->default(0);
            // {response, verdict, feedback, hints_shown, source: plan|review|free}
            $table->json('evidence')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attempts');
    }
};
