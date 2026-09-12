<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            // Review is not a type: it is a practice executed with plan_items.source = 'review'.
            $table->enum('type', ['learn', 'practice']);
            $table->string('title');
            $table->unsignedSmallInteger('estimated_minutes')->default(10);

            // Everything AI-generated and type-specific lives here instead of
            // separate tables: prompt/content, hints[], evaluation method + rubric,
            // expected_outcome, difficulty. Keeps the schema flexible per activity type.
            $table->json('payload')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
