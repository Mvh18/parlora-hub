<?php

use Dotenv\Dotenv;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Symfony\Component\Yaml\Yaml;
use Tests\Fixtures\Jobs\NoopJob;

/*
 * The local runtime (compose.yaml, .env.example, the dev command) is code:
 * these checks keep ports on loopback and every variable documented.
 */

/**
 * @return array<string, mixed>
 */
function composeFile(): array
{
    return Yaml::parseFile(base_path('compose.yaml'));
}

/**
 * @return array<string, string|null>
 */
function envExample(): array
{
    return Dotenv::parse((string) file_get_contents(base_path('.env.example')));
}

/**
 * Config files shipped by Laravel or published by packages. Every other file
 * in config/ belongs to the project and must document its env() keys.
 */
const VENDOR_CONFIG_FILES = [
    'app.php', 'auth.php', 'cache.php', 'database.php', 'filesystems.php',
    'fortify.php', 'horizon.php', 'inertia.php', 'logging.php', 'mail.php',
    'queue.php', 'services.php', 'session.php',
];

test('compose defines the parlora-hub project with every local service', function () {
    $compose = composeFile();

    expect($compose['name'])->toBe('parlora-hub')
        ->and($compose['services'])->toHaveKeys(['app', 'postgres', 'valkey', 'mailpit']);
});

test('every compose service has a healthcheck', function () {
    foreach (composeFile()['services'] as $name => $service) {
        expect($service)->toHaveKey('healthcheck', message: "service {$name} has no healthcheck");
    }
});

test('every published port is bound to 127.0.0.1', function () {
    $ports = collect(composeFile()['services'])->flatMap(fn (array $service) => $service['ports'] ?? []);

    expect($ports)->not->toBeEmpty();

    $ports->each(fn (string $port) => expect($port)->toStartWith('127.0.0.1:'));
});

test('every host port is overridable from .env with the documented default', function () {
    $env = envExample();
    $ports = collect(composeFile()['services'])->flatMap(fn (array $service) => $service['ports'] ?? []);

    $ports->each(function (string $port) use ($env) {
        expect($port)->toMatch('/^127\.0\.0\.1:\$\{(\w+):-(\d+)\}:/');

        preg_match('/^127\.0\.0\.1:\$\{(\w+):-(\d+)\}:/', $port, $matches);

        expect($env)->toHaveKey($matches[1])
            ->and($env[$matches[1]])->toBe($matches[2]);
    });
});

test('every variable compose.yaml interpolates is documented in .env.example', function () {
    preg_match_all('/(?<!\$)\$\{(\w+)/', (string) file_get_contents(base_path('compose.yaml')), $matches);

    expect(array_keys(envExample()))->toContain(...array_unique($matches[1]));
});

test('every env() key in the project config files is documented in .env.example', function () {
    $keys = collect(glob(config_path('*.php')) ?: [])
        ->reject(fn (string $path) => in_array(basename($path), VENDOR_CONFIG_FILES, true))
        ->flatMap(function (string $path) {
            preg_match_all("/\benv\(\s*'([A-Z0-9_]+)'/", (string) file_get_contents($path), $matches);

            return $matches[1];
        })
        ->unique()
        ->values()
        ->all();

    expect(array_keys(envExample()))->toContain(...($keys ?: ['APP_KEY']));
});

test('.env.example documents the Parlora Tutor variables with empty values', function () {
    $env = envExample();

    expect($env['PARLORA_TUTOR_ENABLED'])->toBe('false')
        ->and($env['PARLORA_TUTOR_URL'])->toBe('')
        ->and($env['PARLORA_TUTOR_API_KEY'])->toBe('')
        ->and($env['PARLORA_TUTOR_WEBHOOK_SECRET'])->toBe('')
        ->and($env)->toHaveKey('HUB_REGISTRATION_ENABLED');
});

test('the default database is the Compose PostgreSQL service', function () {
    $env = envExample();

    expect($env['DB_CONNECTION'])->toBe('pgsql')
        ->and($env['DB_HOST'])->toBe('postgres')
        ->and($env['DB_PORT'])->toBe('5432');
});

test('mail in development goes to Mailpit', function () {
    $env = envExample();

    expect($env['MAIL_MAILER'])->toBe('smtp')
        ->and($env['MAIL_HOST'])->toBe('mailpit')
        ->and($env['MAIL_PORT'])->toBe('1025')
        ->and(composeFile()['services']['mailpit']['ports'])
        ->toContain('127.0.0.1:${FORWARD_MAILPIT_PORT:-1025}:1025');
});

test('queues use the redis connection against Valkey', function () {
    $env = envExample();

    expect($env['QUEUE_CONNECTION'])->toBe('redis')
        ->and($env['REDIS_HOST'])->toBe('valkey')
        ->and(config('queue.connections.redis.driver'))->toBe('redis');
});

test('a dispatched job is pushed to Valkey through the redis connection', function () {
    config(['queue.default' => 'redis']);
    $queue = 'test-'.Str::uuid();
    $connection = Queue::connection('redis');

    try {
        dispatch(new NoopJob)->onQueue($queue);

        expect($connection->size($queue))->toBe(1);
    } finally {
        $connection->clear($queue);
    }
});

test('the dev command starts the web server, Horizon and Vite', function () {
    $dev = implode("\n", json_decode((string) file_get_contents(base_path('composer.json')), true)['scripts']['dev']);

    expect($dev)
        ->toContain('php artisan serve')
        ->toContain('php artisan horizon')
        ->toContain('pnpm run dev')
        ->and(composeFile()['services']['app']['command'])->toBe(['composer', 'dev']);
});
