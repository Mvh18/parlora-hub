<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Values are spelled out rather than read from the enums so this
     * migration keeps meaning the same thing if an enum grows later.
     */
    public function up(): void
    {
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->string('key', 120)->unique();
            $table->enum('kind', ['level', 'placement']);
            // Null for the placement exam; at most one exam per level.
            $table->foreignId('level_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->string('title');
            $table->unsignedSmallInteger('time_limit_minutes');
            $table->unsignedTinyInteger('pass_score');
            // Null means unlimited attempts.
            $table->unsignedSmallInteger('max_attempts')->nullable();
            $table->unsignedSmallInteger('cooldown_hours')->default(0);
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });

        DB::statement("ALTER TABLE exams ADD CONSTRAINT exams_kind_level_check CHECK ((kind = 'level') = (level_id IS NOT NULL))");
        DB::statement('ALTER TABLE exams ADD CONSTRAINT exams_pass_score_check CHECK (pass_score BETWEEN 0 AND 100)');
        // One active placement exam; archiving it frees the slot.
        DB::statement("CREATE UNIQUE INDEX exams_one_active_placement ON exams (kind) WHERE kind = 'placement' AND archived_at IS NULL");

        Schema::create('exam_sections', function (Blueprint $table) {
            $table->id();
            $table->string('key', 120)->unique();
            $table->foreignId('exam_id')->constrained()->restrictOnDelete();
            $table->enum('skill', ['use_of_english', 'reading', 'listening', 'writing', 'speaking']);
            $table->unsignedSmallInteger('points');
            $table->enum('grading_mode', ['auto', 'ai', 'self_assessment']);
            // Size of the random subset drawn from the bank per attempt; null means all.
            $table->unsignedSmallInteger('questions_per_attempt')->nullable();
            $table->unsignedSmallInteger('position');
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->unique(['exam_id', 'position']);
        });

        Schema::create('exam_questions', function (Blueprint $table) {
            $table->id();
            $table->string('key', 120)->unique();
            $table->foreignId('exam_section_id')->constrained()->restrictOnDelete();
            $table->foreignId('passage_id')->nullable()->constrained()->restrictOnDelete();
            $table->enum('type', [
                'multiple_choice', 'fill_gap', 'reorder', 'matching',
                'transformation', 'dictation', 'writing', 'speaking',
            ]);
            // Typed by `type`; cast to data objects by ExercisePayloadCast.
            $table->jsonb('content');
            // Server only: never serialised, never sent to the browser.
            $table->jsonb('solution');
            // Explanation per locale: {"en": "...", "es": "..."}.
            $table->jsonb('explanation')->nullable();
            $table->unsignedSmallInteger('points');
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_questions');
        Schema::dropIfExists('exam_sections');
        Schema::dropIfExists('exams');
    }
};
