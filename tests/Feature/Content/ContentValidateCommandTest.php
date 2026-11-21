<?php

use Illuminate\Support\Facades\Storage;
use Tests\Support\Content\FixtureTree;

afterEach(fn () => FixtureTree::cleanUp());

const GREETINGS = 'levels/a1/units/01-greetings.json';
const FAMILY = 'levels/a1/units/02-family.json';

test('the fixture tree passes and missing audio is only a warning', function () {
    Storage::fake('content-audio');

    $this->artisan('content:validate', ['--path' => 'tests/Fixtures/content'])
        ->expectsOutputToContain('warning passages/a1/listening-01.json /audio_path')
        ->expectsOutputToContain('Content is valid: 7 files, 2 warnings.')
        ->assertExitCode(0);
});

test('existing audio raises no warning', function () {
    Storage::fake('content-audio');
    Storage::disk('content-audio')->put('a1/listening-01.mp3', 'x');
    Storage::disk('content-audio')->put('a1/phone-call.mp3', 'x');

    $this->artisan('content:validate', ['--path' => 'tests/Fixtures/content'])
        ->expectsOutputToContain('Content is valid: 7 files, 0 warnings.')
        ->assertExitCode(0);
});

test('all errors are reported at once, each with its file and pointer', function () {
    $directory = FixtureTree::copy();

    FixtureTree::edit($directory, GREETINGS, function (array $unit) {
        unset($unit['lessons'][0]['exercises'][1]['solution']['answers']['g2']);

        return $unit;
    });
    FixtureTree::edit($directory, FAMILY, function (array $unit) {
        $unit['lessons'][0]['exercises'][0]['passage'] = 'a1.does-not-exist';

        return $unit;
    });

    $this->artisan('content:validate', ['--path' => $directory])
        ->expectsOutputToContain(GREETINGS.' /lessons/0/exercises/1/solution/answers')
        ->expectsOutputToContain(FAMILY.' /lessons/0/exercises/0/passage: There is no passage with the key `a1.does-not-exist`.')
        ->expectsOutputToContain('2 errors')
        ->assertExitCode(1);
});

test('a payload error is located down to the failing member', function () {
    $directory = FixtureTree::copy();

    FixtureTree::edit($directory, GREETINGS, function (array $unit) {
        $unit['lessons'][0]['exercises'][0]['solution']['correct'] = ['d'];

        return $unit;
    });

    $this->artisan('content:validate', ['--path' => $directory])
        ->expectsOutputToContain(GREETINGS.' /lessons/0/exercises/0/solution/correct/0:')
        ->assertExitCode(1);
});

test('an exam question payload error is located too', function () {
    $directory = FixtureTree::copy();

    FixtureTree::edit($directory, 'levels/a1/exam.json', function (array $exam) {
        $exam['sections'][0]['questions'][0]['solution']['correct'] = ['zzz'];

        return $exam;
    });

    $this->artisan('content:validate', ['--path' => $directory])
        ->expectsOutputToContain('levels/a1/exam.json /sections/0/questions/0/solution/correct/0:')
        ->assertExitCode(1);
});

test('a key used in two files names both locations', function () {
    $directory = FixtureTree::copy();

    FixtureTree::edit($directory, FAMILY, function (array $unit) {
        $unit['lessons'][] = FixtureTree::read(dirname(__DIR__, 2).'/Fixtures/content', GREETINGS)['lessons'][1];

        return $unit;
    });

    $this->artisan('content:validate', ['--path' => $directory])
        ->expectsOutputToContain(GREETINGS.' /lessons/1: The key `a1.u01.l02` is also used at '.FAMILY.' /lessons/4.')
        ->expectsOutputToContain(FAMILY.' /lessons/4: The key `a1.u01.l02` is already used at '.GREETINGS.' /lessons/1.')
        ->assertExitCode(1);
});

test('keys only have to be unique within their table', function () {
    $directory = FixtureTree::copy();

    FixtureTree::edit($directory, 'passages/a1/reading-01.json', function (array $passage) {
        $passage['key'] = 'a1.u01';

        return $passage;
    });
    FixtureTree::edit($directory, GREETINGS, function (array $unit) {
        $unit['lessons'][0]['exercises'][0]['passage'] = null;

        return $unit;
    });

    $this->artisan('content:validate', ['--path' => $directory])
        ->doesntExpectOutputToContain('is also used at')
        ->assertExitCode(1);
});

test('a locale that is not configured is reported at its own pointer', function () {
    $directory = FixtureTree::copy();

    FixtureTree::edit($directory, GREETINGS, function (array $unit) {
        $unit['lessons'][0]['theory']['fr'] = 'Utilisez am, is et are.';

        return $unit;
    });

    $this->artisan('content:validate', ['--path' => $directory])
        ->expectsOutputToContain(GREETINGS.' /lessons/0/theory/fr:')
        ->assertExitCode(1);
});

test('a bare string translation is a schema error', function () {
    $directory = FixtureTree::copy();

    FixtureTree::edit($directory, GREETINGS, function (array $unit) {
        $unit['lessons'][1]['vocabulary'][0]['meaning'] = 'a greeting';

        return $unit;
    });

    $this->artisan('content:validate', ['--path' => $directory])
        ->expectsOutputToContain(GREETINGS.' /lessons/1/vocabulary/0/meaning:')
        ->assertExitCode(1);
});

test('a position member is rejected at that member', function () {
    $directory = FixtureTree::copy();

    FixtureTree::edit($directory, GREETINGS, function (array $unit) {
        $unit['lessons'][0]['exercises'][0]['position'] = 4;

        return $unit;
    });

    $this->artisan('content:validate', ['--path' => $directory])
        ->expectsOutputToContain(GREETINGS.' /lessons/0/exercises/0/position:')
        ->assertExitCode(1);
});

test('a file that fails its schema does not hide the problems of the others', function () {
    $directory = FixtureTree::copy();

    FixtureTree::edit($directory, GREETINGS, function (array $unit) {
        $unit['title'] = '';

        return $unit;
    });
    FixtureTree::edit($directory, FAMILY, function (array $unit) {
        $unit['lessons'][0]['exercises'][0]['passage'] = 'a1.nowhere';

        return $unit;
    });

    $this->artisan('content:validate', ['--path' => $directory])
        ->expectsOutputToContain(GREETINGS.' /title:')
        ->expectsOutputToContain(FAMILY.' /lessons/0/exercises/0/passage:')
        ->assertExitCode(1);
});

test('the level folder must be the level code in lowercase', function () {
    $directory = FixtureTree::copy();
    rename("{$directory}/levels/a1", "{$directory}/levels/a2");

    $this->artisan('content:validate', ['--path' => $directory])
        ->expectsOutputToContain('levels/a2/level.json /code: The level folder is `a2` but the code is `A1`')
        ->assertExitCode(1);
});

test('a unit or exam in a folder without a level file is reported', function () {
    $directory = FixtureTree::copy();
    unlink("{$directory}/levels/a1/level.json");

    $this->artisan('content:validate', ['--path' => $directory])
        ->expectsOutputToContain(GREETINGS.' /: The folder levels/a1 has no level.json.')
        ->expectsOutputToContain('levels/a1/exam.json /: The folder levels/a1 has no level.json.')
        ->assertExitCode(1);
});

test('--path overrides the configured content folder', function () {
    config(['hub.content_path' => sys_get_temp_dir().'/hub-content-nowhere']);

    $this->artisan('content:validate', ['--path' => 'tests/Fixtures/content'])
        ->expectsOutputToContain('Content is valid: 7 files')
        ->assertExitCode(0);
});

test('without --path the configured content folder is validated', function () {
    $directory = FixtureTree::copy();
    config(['hub.content_path' => $directory]);

    $this->artisan('content:validate')->expectsOutputToContain('Content is valid: 7 files')->assertExitCode(0);

    unlink("{$directory}/levels/a1/level.json");

    $this->artisan('content:validate')->assertExitCode(1);
});

test('a missing content folder fails with one clear error', function () {
    $this->artisan('content:validate', ['--path' => sys_get_temp_dir().'/hub-content-nowhere'])
        ->expectsOutputToContain('(content) /: The content directory does not exist.')
        ->assertExitCode(1);
});

test('an empty content folder is valid', function () {
    $this->artisan('content:validate', ['--path' => FixtureTree::empty()])
        ->expectsOutputToContain('Content is valid: 0 files, 0 warnings.')
        ->assertExitCode(0);
});

test('a symlink to a file outside the content root fails validation and names the symlink', function () {
    $outside = FixtureTree::empty();
    FixtureTree::write($outside, 'secret.json', ['key' => 'a1.secret', 'title' => 'Secret']);

    $directory = FixtureTree::copy();

    if (! @symlink("{$outside}/secret.json", "{$directory}/passages/a1/leak.json")) {
        $this->markTestSkipped('Symlinks cannot be created here.');
    }

    $this->artisan('content:validate', ['--path' => $directory])
        ->expectsOutputToContain('passages/a1/leak.json /: This symlink points outside the content root')
        ->assertExitCode(1);
});

test('a unit file without a numeric prefix is a warning, not an error', function () {
    $directory = FixtureTree::copy();
    rename("{$directory}/".FAMILY, "{$directory}/levels/a1/units/family.json");

    $this->artisan('content:validate', ['--path' => $directory])
        ->expectsOutputToContain('warning levels/a1/units/family.json /: The file name has no numeric prefix')
        ->assertExitCode(0);
});

test('an audio path with the wrong format is an error', function () {
    $directory = FixtureTree::copy();

    FixtureTree::edit($directory, 'passages/a1/listening-01.json', function (array $passage) {
        $passage['audio_path'] = 'a1/listening-01.exe';

        return $passage;
    });

    $this->artisan('content:validate', ['--path' => $directory])
        ->expectsOutputToContain('passages/a1/listening-01.json /audio_path:')
        ->assertExitCode(1);
});
