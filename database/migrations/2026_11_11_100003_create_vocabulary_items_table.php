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
        Schema::create('vocabulary_items', function (Blueprint $table) {
            $table->id();
            $table->string('key', 120)->unique();
            $table->foreignId('lesson_id')->constrained()->restrictOnDelete();
            $table->string('term');
            $table->enum('kind', ['word', 'phrasal_verb', 'expression']);
            // Gloss per locale: {"en": "to look for", "es": "buscar"}.
            $table->jsonb('meaning');
            $table->string('example')->nullable();
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
        Schema::dropIfExists('vocabulary_items');
    }
};
