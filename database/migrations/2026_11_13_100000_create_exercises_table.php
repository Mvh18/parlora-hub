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
        Schema::create('exercises', function (Blueprint $table) {
            $table->id();
            $table->string('key', 120)->unique();
            $table->foreignId('lesson_id')->constrained()->restrictOnDelete();
            $table->foreignId('passage_id')->nullable()->constrained()->restrictOnDelete();
            $table->enum('type', [
                'multiple_choice', 'fill_gap', 'reorder', 'matching',
                'transformation', 'dictation', 'writing', 'speaking',
            ]);
            $table->enum('skill', [
                'grammar', 'vocabulary', 'reading', 'listening', 'writing', 'speaking',
            ]);
            $table->text('prompt');
            // Typed by `type`; cast to data objects by ExercisePayloadCast.
            $table->jsonb('content');
            // Server only: never serialised, never sent to the browser.
            $table->jsonb('solution');
            // Explanation per locale: {"en": "...", "es": "..."}.
            $table->jsonb('explanation')->nullable();
            // Relative to the private `content-audio` disk, never a URL.
            $table->string('audio_path')->nullable();
            $table->unsignedSmallInteger('position');
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->unique(['lesson_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exercises');
    }
};
