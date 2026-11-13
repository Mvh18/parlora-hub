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
        Schema::create('passages', function (Blueprint $table) {
            $table->id();
            $table->string('key', 120)->unique();
            $table->string('title');
            // Markdown reading text; null for listening passages.
            $table->text('body')->nullable();
            // Listening script. Hidden like a solution: reading it answers the questions.
            $table->text('transcript')->nullable();
            // Relative to the private `content-audio` disk, never a URL.
            $table->string('audio_path')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('passages');
    }
};
