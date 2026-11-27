<?php

use App\Content\ContentTables;
use Illuminate\Support\Facades\DB;
use Tests\Support\Content\FixtureTree;

/*
 * "A1 imports from scratch with one command": the shipped content/ tree goes
 * into an empty database and every row the files describe is there.
 */

test('the shipped content imports into an empty database', function () {
    foreach (ContentTables::order() as $table) {
        expect(DB::table($table)->count())->toBe(0, $table);
    }

    $this->artisan('content:import', ['--path' => base_path('content')])->assertExitCode(0);

    $tree = FixtureTree::tree(base_path('content'));

    foreach (ContentTables::order() as $table) {
        expect(DB::table($table)->pluck('key')->sort()->values()->all())
            ->toBe(collect($tree->keys($table))->sort()->values()->all(), $table);
    }

    expect(DB::table('levels')->where('key', 'a1')->exists())->toBeTrue();
});
