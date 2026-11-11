<?php

use Dotenv\Dotenv;

/*
 * Placeholders for the Parlora Tutor integration (Phase 6): the names and
 * defaults are fixed now, and the integration stays off until it is
 * configured.
 */

const TUTOR_VARIABLES = [
    'PARLORA_TUTOR_ENABLED',
    'PARLORA_TUTOR_URL',
    'PARLORA_TUTOR_API_KEY',
    'PARLORA_TUTOR_WEBHOOK_SECRET',
];

/**
 * Evaluate config/parlora_tutor.php with the given environment, where null
 * means the variable is not set at all.
 *
 * @param  array<string, string|null>  $environment
 * @return array<string, mixed>
 */
function tutorConfigWith(array $environment): array
{
    $saved = [];

    foreach ($environment as $name => $value) {
        $saved[$name] = [$_SERVER[$name] ?? null, $_ENV[$name] ?? null, getenv($name)];

        if ($value === null) {
            unset($_SERVER[$name], $_ENV[$name]);
            putenv($name);
        } else {
            $_SERVER[$name] = $_ENV[$name] = $value;
            putenv("{$name}={$value}");
        }
    }

    try {
        return require config_path('parlora_tutor.php');
    } finally {
        foreach ($saved as $name => [$server, $env, $process]) {
            if ($server === null) {
                unset($_SERVER[$name]);
            } else {
                $_SERVER[$name] = $server;
            }

            if ($env === null) {
                unset($_ENV[$name]);
            } else {
                $_ENV[$name] = $env;
            }

            putenv($process === false ? $name : "{$name}={$process}");
        }
    }
}

test('.env.example lists the tutor variables with the integration off', function () {
    $example = Dotenv::parse((string) file_get_contents(base_path('.env.example')));

    expect($example)->toHaveKeys(TUTOR_VARIABLES)
        ->and($example['PARLORA_TUTOR_ENABLED'])->toBe('false')
        ->and($example['PARLORA_TUTOR_URL'])->toBe('')
        ->and($example['PARLORA_TUTOR_API_KEY'])->toBe('')
        ->and($example['PARLORA_TUTOR_WEBHOOK_SECRET'])->toBe('');
});

test('the integration is disabled with the .env.example values', function () {
    $example = Dotenv::parse((string) file_get_contents(base_path('.env.example')));

    $config = tutorConfigWith(array_intersect_key($example, array_flip(TUTOR_VARIABLES)));

    expect($config)->toBe([
        'enabled' => false,
        'url' => null,
        'api_key' => null,
        'webhook_secret' => null,
    ]);
});

test('the integration is disabled when the variables are not set', function () {
    $config = tutorConfigWith(array_fill_keys(TUTOR_VARIABLES, null));

    expect($config['enabled'])->toBeFalse()
        ->and($config['url'])->toBeNull();
});

test('the tutor variables are read when set', function () {
    $config = tutorConfigWith([
        'PARLORA_TUTOR_ENABLED' => 'true',
        'PARLORA_TUTOR_URL' => 'http://127.0.0.1:8700',
        'PARLORA_TUTOR_API_KEY' => 'test-api-key',
        'PARLORA_TUTOR_WEBHOOK_SECRET' => 'test-webhook-secret',
    ]);

    expect($config)->toBe([
        'enabled' => true,
        'url' => 'http://127.0.0.1:8700',
        'api_key' => 'test-api-key',
        'webhook_secret' => 'test-webhook-secret',
    ]);
});

test('the application boots with the integration disabled', function () {
    expect(config('parlora_tutor.enabled'))->toBeFalse();
});
