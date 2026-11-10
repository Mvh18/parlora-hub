<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false);
            $table->uuid('learner_ref')->nullable()->unique();
        });

        // Existing accounts get their own random reference before the column
        // becomes required.
        DB::table('users')->whereNull('learner_ref')->orderBy('id')->each(
            fn (object $user) => DB::table('users')
                ->where('id', $user->id)
                ->update(['learner_ref' => (string) Str::uuid()]),
        );

        Schema::table('users', function (Blueprint $table) {
            $table->uuid('learner_ref')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['learner_ref']);
            $table->dropColumn(['is_admin', 'learner_ref']);
        });
    }
};
