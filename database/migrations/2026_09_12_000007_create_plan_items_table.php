<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Today's materialized plan only; the future is always recomputed.
        Schema::create('plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->date('scheduled_for');
            $table->unsignedSmallInteger('duration_minutes');
            $table->enum('status', ['scheduled', 'completed', 'skipped'])->default('scheduled');
            $table->enum('source', ['plan', 'review', 'recovery'])->default('plan');
            $table->string('reason')->nullable(); // planner justification shown to the learner
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_items');
    }
};
