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
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->string('key', 120)->unique();
            $table->foreignId('unit_id')->constrained()->restrictOnDelete();
            $table->enum('type', [
                'grammar', 'vocabulary', 'phrasal_verbs', 'reading', 'listening', 'writing', 'speaking',
            ]);
            $table->string('title');
            // Markdown per locale: {"en": "...", "es": "..."}.
            $table->jsonb('theory')->nullable();
            $table->unsignedSmallInteger('position');
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->unique(['unit_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
