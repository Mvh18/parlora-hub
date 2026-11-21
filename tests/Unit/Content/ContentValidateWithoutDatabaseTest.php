<?php

use Illuminate\Support\Facades\DB;

test('content:validate needs no database', function () {
    config([
        'database.default' => 'pgsql',
        'database.connections.pgsql.host' => '127.0.0.1',
        'database.connections.pgsql.port' => 1,
        'database.connections.pgsql.database' => 'does_not_exist',
    ]);
    DB::purge();

    // The connection really is unreachable...
    expect(fn () => DB::connection()->getPdo())->toThrow(PDOException::class);

    // ...and the command does not care.
    $this->artisan('content:validate', ['--path' => 'tests/Fixtures/content'])
        ->expectsOutputToContain('Content is valid: 7 files')
        ->assertExitCode(0);
});
