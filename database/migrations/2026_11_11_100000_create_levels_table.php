<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        Schema::create('levels', function (Blueprint $table) {
            $table->id();
            $table->string('key', 120)->unique();
            $table->enum('code', ['A1', 'A2', 'B1', 'B2', 'C1'])->unique();
            $table->string('title');
            $table->unsignedTinyInteger('position')->unique();
            $table->unsignedTinyInteger('pass_threshold');
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('levels');
    }
};
