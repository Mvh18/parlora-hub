<?php

namespace Tests\Support;

use RuntimeException;

/**
 * Refuses to run the suite against anything but a PostgreSQL database whose
 * name ends in `_test`, so RefreshDatabase can never wipe the development
 * database.
 */
final class TestDatabaseGuard
{
    public static function assertSafe(?string $driver, ?string $database): void
    {
        if ($driver !== 'pgsql') {
            throw new RuntimeException(sprintf(
                'The test suite needs PostgreSQL (DB_CONNECTION=pgsql), got [%s]. See CONTRIBUTING.md, "Test database".',
                $driver ?? 'none',
            ));
        }

        if (! str_ends_with((string) $database, '_test')) {
            throw new RuntimeException(sprintf(
                'Refusing to run the tests on database [%s]: its name must end in "_test". See CONTRIBUTING.md, "Test database".',
                $database ?? 'none',
            ));
        }
    }
}
