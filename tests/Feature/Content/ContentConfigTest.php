<?php

test('content locales are english and spanish', function () {
    expect(config('hub.content_locales'))->toBe(['en', 'es']);
});

/**
 * Evaluate config/hub.php with HUB_CONTENT_PATH set to the given value, where
 * null means the variable is not set at all.
 */
function contentPathWith(?string $value): string
{
    $saved = [$_SERVER['HUB_CONTENT_PATH'] ?? null, $_ENV['HUB_CONTENT_PATH'] ?? null, getenv('HUB_CONTENT_PATH')];

    if ($value === null) {
        unset($_SERVER['HUB_CONTENT_PATH'], $_ENV['HUB_CONTENT_PATH']);
        putenv('HUB_CONTENT_PATH');
    } else {
        $_SERVER['HUB_CONTENT_PATH'] = $_ENV['HUB_CONTENT_PATH'] = $value;
        putenv("HUB_CONTENT_PATH={$value}");
    }

    try {
        return (require config_path('hub.php'))['content_path'];
    } finally {
        [$server, $env, $process] = $saved;
        $server === null ? putenv('HUB_CONTENT_PATH') : putenv("HUB_CONTENT_PATH={$process}");

        if ($server === null) {
            unset($_SERVER['HUB_CONTENT_PATH'], $_ENV['HUB_CONTENT_PATH']);
        } else {
            $_SERVER['HUB_CONTENT_PATH'] = $server;
            $_ENV['HUB_CONTENT_PATH'] = $env;
        }
    }
}

test('the content path defaults to content/ in the project', function () {
    expect(contentPathWith(null))->toBe(base_path('content'))
        ->and(contentPathWith(''))->toBe(base_path('content'));
});

test('a relative content path is resolved against the project root', function () {
    expect(contentPathWith('course'))->toBe(base_path('course'));
});

test('absolute content paths are kept as they are', function (string $path) {
    expect(contentPathWith($path))->toBe($path);
})->with(['/srv/course', 'C:\\course', 'C:/course', '\\\\server\\share\\course']);

test('the content path is documented in .env.example', function () {
    expect(file_get_contents(base_path('.env.example')))->toContain('HUB_CONTENT_PATH=');
});

test('the content audio disk is private', function () {
    $disk = config('filesystems.disks.content-audio');

    expect($disk['root'])->toBe(storage_path('app/private/content-audio'))
        ->and($disk)->not->toHaveKey('url')
        ->and($disk)->not->toHaveKey('serve')
        ->and($disk['visibility'])->toBe('private');
});

test('the content audio disk is outside the public folder', function () {
    $root = str_replace('\\', '/', config('filesystems.disks.content-audio.root'));

    expect($root)->not->toStartWith(str_replace('\\', '/', public_path()))
        ->and($root)->not->toContain('app/public');
});

test('the content audio disk is not linked into public', function () {
    expect(array_values(config('filesystems.links')))->not->toContain(storage_path('app/private/content-audio'));
});
