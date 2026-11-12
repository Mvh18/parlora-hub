<?php

use App\Rules\AudioPath;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

test('relative paths with an audio extension are valid', function (string $path) {
    expect(AudioPath::error($path))->toBeNull();
})->with([
    'a1/u03/listening-01.mp3',
    'clip.ogg',
    'a1/clip.opus',
    'a1/u03/dictation_02.m4a',
    'a1/u03/Dictation.WAV',
]);

test('null is valid because the column is nullable', function () {
    expect(AudioPath::error(null))->toBeNull();
});

test('path traversal is rejected', function (string $path) {
    expect(AudioPath::error($path))->not->toBeNull();
})->with([
    'parent segments' => '../../.env',
    'embedded parent' => 'a1/../../secret.mp3',
    'backslash traversal' => 'a1\\..\\secret.mp3',
    'encoded dots' => 'a1/%2e%2e/secret.mp3',
    'hidden file' => 'a1/.hidden.mp3',
    'current directory' => './clip.mp3',
    'null byte' => "clip.mp3\0.php",
]);

test('absolute paths are rejected', function (string $path) {
    expect(AudioPath::error($path))->not->toBeNull();
})->with([
    'unix' => '/etc/clip.mp3',
    'windows drive' => 'C:/clips/clip.mp3',
    'unc' => '//server/share/clip.mp3',
]);

test('other extensions and missing extensions are rejected', function (string $path) {
    expect(AudioPath::error($path))->not->toBeNull();
})->with([
    'script' => 'a1/clip.php',
    'executable' => 'a1/clip.exe',
    'no extension' => 'a1/clip',
    'double extension' => 'a1/clip.mp3.php',
    'extension only' => '.mp3',
]);

test('empty segments and unusual characters are rejected', function (string $path) {
    expect(AudioPath::error($path))->not->toBeNull();
})->with([
    'empty' => '',
    'double slash' => 'a1//clip.mp3',
    'trailing slash' => 'a1/',
    'space' => 'a1/my clip.mp3',
    'query string' => 'a1/clip.mp3?x=1',
]);

test('a value that is not a string is rejected', function () {
    expect(AudioPath::error(['a1/clip.mp3']))->not->toBeNull()
        ->and(AudioPath::error(5))->not->toBeNull();
});

test('assert names the attribute in the error', function () {
    try {
        AudioPath::assert('audio_path', '../../.env');
        $this->fail('Expected a ValidationException.');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('audio_path');
    }
});

test('assert passes for a valid or null path', function () {
    AudioPath::assert('audio_path', 'a1/u03/listening-01.mp3');
    AudioPath::assert('audio_path', null);

    expect(true)->toBeTrue();
});

test('it works as a validation rule', function () {
    $passes = Validator::make(['audio_path' => 'a1/clip.mp3'], ['audio_path' => [new AudioPath]]);
    $fails = Validator::make(['audio_path' => '../../.env'], ['audio_path' => [new AudioPath]]);

    expect($passes->passes())->toBeTrue()
        ->and($fails->fails())->toBeTrue();
});
