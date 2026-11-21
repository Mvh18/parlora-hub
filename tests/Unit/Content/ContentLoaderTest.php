<?php

use App\Content\ContentFile;
use App\Content\ContentLoader;
use App\Content\ValidationReport;
use Tests\Support\Content\FixtureTree;

afterEach(fn () => FixtureTree::cleanUp());

/**
 * @return array{0: list<ContentFile>, 1: ValidationReport}
 */
function loadTree(string $directory): array
{
    $report = new ValidationReport;
    $files = (new ContentLoader)->load($directory, $report);

    return [$files, $report];
}

/** @param  list<ContentFile>  $files */
function loadedPaths(array $files): array
{
    return array_map(fn (ContentFile $file): string => $file->path, $files);
}

test('the fixture tree loads in import order without problems', function () {
    [$files, $report] = loadTree(dirname(__DIR__, 2).'/Fixtures/content');

    expect($report->errors())->toBe([])
        ->and(loadedPaths($files))->toBe([
            'levels/a1/level.json',
            'levels/a1/units/01-greetings.json',
            'levels/a1/units/02-family.json',
            'levels/a1/exam.json',
            'exams/placement.json',
            'passages/a1/listening-01.json',
            'passages/a1/reading-01.json',
        ]);
});

test('files are classified by their path', function () {
    [$files] = loadTree(dirname(__DIR__, 2).'/Fixtures/content');
    $byPath = [];

    foreach ($files as $file) {
        $byPath[$file->path] = $file;
    }

    expect($byPath['levels/a1/level.json']->kind)->toBe('level')
        ->and($byPath['levels/a1/level.json']->folder)->toBe('a1')
        ->and($byPath['levels/a1/units/01-greetings.json']->kind)->toBe('unit')
        ->and($byPath['levels/a1/exam.json']->kind)->toBe('exam')
        ->and($byPath['levels/a1/exam.json']->placement)->toBeFalse()
        ->and($byPath['exams/placement.json']->placement)->toBeTrue()
        ->and($byPath['exams/placement.json']->folder)->toBeNull()
        ->and($byPath['passages/a1/reading-01.json']->kind)->toBe('passage');
});

test('units are ordered by file name, not by the order they were written', function () {
    $directory = FixtureTree::copy();
    rename("{$directory}/levels/a1/units/01-greetings.json", "{$directory}/levels/a1/units/03-greetings.json");

    [$files] = loadTree($directory);

    expect(array_values(array_filter(loadedPaths($files), fn (string $path) => str_contains($path, '/units/'))))
        ->toBe(['levels/a1/units/02-family.json', 'levels/a1/units/03-greetings.json']);
});

test('levels are ordered by CEFR level, not by folder name', function () {
    $directory = FixtureTree::empty();

    foreach (['b1' => 'B1', 'a2' => 'A2', 'a1' => 'A1'] as $folder => $code) {
        FixtureTree::write($directory, "levels/{$folder}/level.json", ['key' => $folder, 'code' => $code, 'title' => $code, 'pass_threshold' => 70]);
    }

    [$files] = loadTree($directory);

    expect(loadedPaths($files))->toBe(['levels/a1/level.json', 'levels/a2/level.json', 'levels/b1/level.json']);
});

test('only json files are read', function () {
    $directory = FixtureTree::copy();
    file_put_contents("{$directory}/levels/a1/notes.txt", 'not content');
    file_put_contents("{$directory}/README.md", '# readme');

    [$files, $report] = loadTree($directory);

    expect($report->errors())->toBe([])
        ->and($files)->toHaveCount(7);
});

test('a json file outside the layout is reported', function () {
    $directory = FixtureTree::copy();
    FixtureTree::write($directory, 'levels/a1/unit/01-typo.json', ['key' => 'a1.typo', 'title' => 'Typo']);

    [, $report] = loadTree($directory);

    expect($report->errors())->toHaveCount(1)
        ->and($report->errors()[0]->file)->toBe('levels/a1/unit/01-typo.json')
        ->and($report->errors()[0]->message)->toContain('content layout');
});

test('a missing directory is an error, not an exception', function () {
    [$files, $report] = loadTree(sys_get_temp_dir().'/hub-content-does-not-exist');

    expect($files)->toBe([])
        ->and($report->errors())->toHaveCount(1)
        ->and($report->errors()[0]->message)->toContain('does not exist');
});

test('invalid json is reported with its file and nothing else is lost', function () {
    $directory = FixtureTree::copy();
    file_put_contents("{$directory}/levels/a1/units/02-family.json", '{"key": ');

    [$files, $report] = loadTree($directory);

    expect($report->errors())->toHaveCount(1)
        ->and($report->errors()[0]->file)->toBe('levels/a1/units/02-family.json')
        ->and($report->errors()[0]->message)->toStartWith('Invalid JSON')
        ->and($files)->toHaveCount(6);
});

test('a file over the size cap is rejected without being decoded', function () {
    $directory = FixtureTree::copy();
    file_put_contents("{$directory}/passages/a1/reading-01.json", json_encode(['key' => 'a1.reading-01', 'title' => 'Big', 'body' => str_repeat('a', ContentLoader::MAX_FILE_BYTES)]));

    [$files, $report] = loadTree($directory);

    expect($report->errors())->toHaveCount(1)
        ->and($report->errors()[0]->file)->toBe('passages/a1/reading-01.json')
        ->and($report->errors()[0]->message)->toContain('larger than')
        ->and(loadedPaths($files))->not->toContain('passages/a1/reading-01.json');
});

test('a document nested deeper than the limit is rejected', function () {
    $directory = FixtureTree::copy();
    file_put_contents("{$directory}/passages/a1/reading-01.json", str_repeat('[', ContentLoader::MAX_DEPTH + 5).str_repeat(']', ContentLoader::MAX_DEPTH + 5));

    [, $report] = loadTree($directory);

    expect($report->errors())->toHaveCount(1)
        ->and($report->errors()[0]->message)->toContain('Invalid JSON');
});

test('a symlink to a file outside the root is reported and its target is not read', function () {
    $outside = FixtureTree::empty();
    FixtureTree::write($outside, 'secret.json', ['key' => 'a1.secret', 'title' => 'Secret']);

    $directory = FixtureTree::copy();

    if (! @symlink("{$outside}/secret.json", "{$directory}/passages/a1/leak.json")) {
        $this->markTestSkipped('Symlinks cannot be created here.');
    }

    [$files, $report] = loadTree($directory);

    expect($report->errors())->toHaveCount(1)
        ->and($report->errors()[0]->file)->toBe('passages/a1/leak.json')
        ->and($report->errors()[0]->message)->toContain('outside the content root')
        ->and(loadedPaths($files))->not->toContain('passages/a1/leak.json');
});

test('a symlinked folder is reported and not followed', function () {
    $outside = FixtureTree::empty();
    FixtureTree::write($outside, 'leak.json', ['key' => 'a1.leak', 'title' => 'Leak']);

    $directory = FixtureTree::copy();

    if (! @symlink($outside, "{$directory}/passages/linked")) {
        $this->markTestSkipped('Symlinks cannot be created here.');
    }

    [$files, $report] = loadTree($directory);

    expect($report->errors())->toHaveCount(1)
        ->and($report->errors()[0]->file)->toBe('passages/linked')
        ->and(loadedPaths($files))->not->toContain('passages/linked/leak.json');
});

test('a symlink that stays inside the root is read', function () {
    $directory = FixtureTree::copy();

    if (! @symlink("{$directory}/passages/a1/reading-01.json", "{$directory}/passages/a1/alias.json")) {
        $this->markTestSkipped('Symlinks cannot be created here.');
    }

    [$files, $report] = loadTree($directory);

    expect($report->errors())->toBe([])
        ->and(loadedPaths($files))->toContain('passages/a1/alias.json');
});
