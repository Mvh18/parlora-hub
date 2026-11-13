<?php

use App\Models\Passage;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

describe('passages', function () {
    test('a reading passage stores its body', function () {
        $passage = Passage::factory()->reading()->create();

        expect($passage->fresh())
            ->body->not->toBeNull()
            ->audio_path->toBeNull()
            ->transcript->toBeNull();
    });

    test('a listening passage stores audio and a transcript', function () {
        $passage = Passage::factory()->listening()->create();

        expect($passage->fresh())
            ->audio_path->not->toBeNull()
            ->transcript->not->toBeNull()
            ->body->toBeNull();
    });

    test('duplicate keys are rejected', function () {
        Passage::factory()->create(['key' => 'a1.u01.passage01']);

        expect(fn () => Passage::factory()->create(['key' => 'a1.u01.passage01']))
            ->toThrow(QueryException::class);
    });

    test('malformed keys are rejected', function () {
        expect(fn () => Passage::factory()->create(['key' => 'A1 Passage']))
            ->toThrow(ValidationException::class, 'key');
    });

    test('the key is immutable', function () {
        $passage = Passage::factory()->create();
        $passage->key = 'changed.key';

        expect(fn () => $passage->save())->toThrow(LogicException::class);
    });

    test('passages are archived instead of deleted', function () {
        $passage = Passage::factory()->create();
        $passage->archive();

        expect(Passage::query()->active()->count())->toBe(0)
            ->and(Passage::query()->count())->toBe(1);

        $passage->restore();

        expect(Passage::query()->active()->count())->toBe(1);
    });
});

describe('audio paths', function () {
    test('a valid relative path is stored unchanged', function () {
        $passage = Passage::factory()->create(['audio_path' => 'a1/u03/listening-01.mp3']);

        expect($passage->fresh()->audio_path)->toBe('a1/u03/listening-01.mp3');
    });

    test('audio is optional', function () {
        expect(Passage::factory()->create(['audio_path' => null])->fresh()->audio_path)->toBeNull();
    });

    test('traversal and absolute paths are rejected', function (string $path) {
        expect(fn () => Passage::factory()->create(['audio_path' => $path]))
            ->toThrow(ValidationException::class, 'audio_path');

        expect(Passage::query()->count())->toBe(0);
    })->with(['../../.env', '/etc/passwd', 'a1/../../secret.mp3', 'a1\\secret.mp3', 'C:/audio.mp3']);

    test('unsupported extensions are rejected', function () {
        expect(fn () => Passage::factory()->create(['audio_path' => 'a1/clip.exe']))
            ->toThrow(ValidationException::class, 'audio_path');
    });

    test('an update cannot introduce a bad path', function () {
        $passage = Passage::factory()->create();

        expect(fn () => $passage->update(['audio_path' => '../escape.mp3']))
            ->toThrow(ValidationException::class);
    });

    test('the content-audio disk is private', function () {
        $config = config('filesystems.disks.content-audio');

        expect($config['root'])->toContain('storage'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'private')
            ->and($config)->not->toHaveKey('url');
    });

    test('audio files resolve on the private disk', function () {
        Storage::fake('content-audio');
        Storage::disk('content-audio')->put('a1/clip.mp3', 'x');

        $passage = Passage::factory()->create(['audio_path' => 'a1/clip.mp3']);

        expect(Storage::disk('content-audio')->exists($passage->audio_path))->toBeTrue();
    });
});

describe('transcript', function () {
    test('is hidden from arrays and json', function () {
        $passage = Passage::factory()->listening()->create(['transcript' => 'The secret answer is blue.']);

        expect($passage->toArray())->not->toHaveKey('transcript')
            ->and($passage->toJson())->not->toContain('secret answer')
            ->and($passage->transcript)->toBe('The secret answer is blue.');
    });
});
