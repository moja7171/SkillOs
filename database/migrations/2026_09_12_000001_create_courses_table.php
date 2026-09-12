<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Catalog entry. Written only by `content:import`; shared by all users.
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique(); // matches content/<slug>/
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('outcome_statement')->nullable();
            $table->text('source_note')->nullable(); // where the materials came from
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
