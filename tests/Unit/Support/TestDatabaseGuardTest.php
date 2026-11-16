<?php

use Tests\Support\TestDatabaseGuard;

it('accepts a PostgreSQL database whose name ends in _test', function () {
    TestDatabaseGuard::assertSafe('pgsql', 'hub_test');

    expect(true)->toBeTrue();
});

it('rejects any driver other than PostgreSQL', function (?string $driver) {
    TestDatabaseGuard::assertSafe($driver, 'hub_test');
})->with(['sqlite', 'mysql', null])->throws(RuntimeException::class, 'needs PostgreSQL');

it('rejects a database whose name does not end in _test', function (?string $database) {
    TestDatabaseGuard::assertSafe('pgsql', $database);
})->with(['hub', 'hub_test_backup', '', null])->throws(RuntimeException::class, 'must end in "_test"');
