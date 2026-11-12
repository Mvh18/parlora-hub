<?php

test('content locales are english and spanish', function () {
    expect(config('hub.content_locales'))->toBe(['en', 'es']);
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
