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
        Schema::table('courses', function (Blueprint $table) {
            // Display-only catalog grouping (DECISIONS.md §58) — no gating, no unlocking.
            $table->string('category')->nullable()->after('source_note');
            $table->unsignedSmallInteger('category_order')->nullable()->after('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['category', 'category_order']);
        });
    }
};
