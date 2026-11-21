<?php

use App\Content\SchemaValidator;
use App\Enums\ExerciseType;
use App\Enums\LessonType;

/**
 * Every JSON file of the fixture tree with the schema kind its path implies.
 *
 * @return array<string, array{0: string, 1: string}> relative path => [absolute path, kind]
 */
function fixtureFiles(): array
{
    // Not base_path(): datasets are built before the application boots.
    $root = realpath(dirname(__DIR__, 2).'/Fixtures/content');
    $files = [];

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));

    foreach ($iterator as $file) {
        if ($file->getExtension() !== 'json') {
            continue;
        }

        $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));

        $kind = match (true) {
            str_ends_with($relative, '/level.json') => 'level',
            str_contains($relative, '/units/') => 'unit',
            str_ends_with($relative, '/exam.json'), $relative === 'exams/placement.json' => 'exam',
            str_starts_with($relative, 'passages/') => 'passage',
            default => throw new RuntimeException("No schema kind for fixture {$relative}."),
        };

        $files[$relative] = [$file->getPathname(), $kind];
    }

    ksort($files);

    return $files;
}

/** @return array<string, mixed> */
function fixtureDocument(string $relative): array
{
    return json_decode(file_get_contents(fixtureFiles()[$relative][0]), true, 512, JSON_THROW_ON_ERROR);
}

test('the fixture tree has the documented layout', function () {
    expect(array_keys(fixtureFiles()))->toBe([
        'exams/placement.json',
        'levels/a1/exam.json',
        'levels/a1/level.json',
        'levels/a1/units/01-greetings.json',
        'levels/a1/units/02-family.json',
        'passages/a1/listening-01.json',
        'passages/a1/reading-01.json',
    ]);
});

test('every fixture file validates against its schema', function (string $relative) {
    [$path, $kind] = fixtureFiles()[$relative];

    $errors = (new SchemaValidator)->validate($kind, json_decode(file_get_contents($path), false, 512, JSON_THROW_ON_ERROR));

    expect($errors)->toBe([]);
})->with(array_keys(fixtureFiles()));

test('every fixture file carries a relative schema hint that resolves to its schema', function (string $relative) {
    [$path, $kind] = fixtureFiles()[$relative];
    $hint = fixtureDocument($relative)['$schema'] ?? null;

    expect($hint)->toBeString()
        ->and($hint)->toStartWith('../')
        ->and(realpath(dirname($path).'/'.$hint))->toBe(realpath(SchemaValidator::schemaFile($kind)));
})->with(array_keys(fixtureFiles()));

test('the fixture payloads pass the exercise data classes', function () {
    $items = [];

    foreach (['levels/a1/units/01-greetings.json', 'levels/a1/units/02-family.json'] as $relative) {
        foreach (fixtureDocument($relative)['lessons'] as $lesson) {
            foreach ($lesson['exercises'] ?? [] as $exercise) {
                $items[$exercise['key']] = $exercise;
            }
        }
    }

    foreach (['levels/a1/exam.json', 'exams/placement.json'] as $relative) {
        foreach (fixtureDocument($relative)['sections'] as $section) {
            foreach ($section['questions'] as $question) {
                $items[$question['key']] = $question;
            }
        }
    }

    foreach ($items as $key => $item) {
        expect(payloadErrors(ExerciseType::from($item['type']), $item['content'], $item['solution']))->toBe([], $key);
    }
});

test('the fixture tree uses every lesson type and every exercise type', function () {
    $lessonTypes = [];
    $exerciseTypes = [];

    foreach (['levels/a1/units/01-greetings.json', 'levels/a1/units/02-family.json'] as $relative) {
        foreach (fixtureDocument($relative)['lessons'] as $lesson) {
            $lessonTypes[] = $lesson['type'];

            foreach ($lesson['exercises'] ?? [] as $exercise) {
                $exerciseTypes[] = $exercise['type'];
            }
        }
    }

    $expectedLessons = array_map(fn (LessonType $type): string => $type->value, LessonType::cases());
    $expectedExercises = array_map(fn (ExerciseType $type): string => $type->value, ExerciseType::cases());

    expect(array_diff($expectedLessons, $lessonTypes))->toBe([])
        ->and(array_diff($expectedExercises, $exerciseTypes))->toBe([]);
});

test('the fixture tree has two units in zero-padded order, a Spanish translation and both exams', function () {
    $greetings = fixtureDocument('levels/a1/units/01-greetings.json');

    expect(fixtureDocument('levels/a1/units/02-family.json')['key'])->toBe('a1.u02')
        ->and($greetings['key'])->toBe('a1.u01')
        ->and($greetings['lessons'][0]['theory'])->toHaveKey('es')
        ->and(fixtureDocument('levels/a1/exam.json')['key'])->toBe('a1.exam')
        ->and(fixtureDocument('exams/placement.json')['key'])->toBe('placement');
});

test('the fixture tree shares a passage between a lesson exercise and an exam question', function () {
    $exercise = fixtureDocument('levels/a1/units/02-family.json')['lessons'][0]['exercises'][0];
    $question = fixtureDocument('levels/a1/exam.json')['sections'][1]['questions'][0];

    expect($exercise['passage'])->toBe('a1.reading-01')
        ->and($question['passage'])->toBe('a1.reading-01')
        ->and(fixtureDocument('passages/a1/reading-01.json')['key'])->toBe('a1.reading-01');
});
