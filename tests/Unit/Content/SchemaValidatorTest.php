<?php

use App\Content\SchemaError;
use App\Content\SchemaValidator;

/** @return list<string> "pointer: message" lines are not asserted, only pointers. */
function schemaPointers(string $kind, array $document): array
{
    return array_map(
        fn (SchemaError $error): string => $error->pointer,
        (new SchemaValidator)->validateArray($kind, $document),
    );
}

function validUnit(): array
{
    return [
        'key' => 'a1.u01',
        'title' => 'Greetings',
        'lessons' => [[
            'key' => 'a1.u01.l01',
            'type' => 'grammar',
            'title' => 'To be',
            'theory' => ['en' => 'Use **am/is/are**.', 'es' => 'Usa **am/is/are**.'],
            'vocabulary' => [[
                'key' => 'a1.u01.l01.v01',
                'term' => 'hello',
                'kind' => 'word',
                'meaning' => ['en' => 'a greeting', 'es' => 'un saludo'],
            ]],
            'exercises' => [[
                'key' => 'a1.u01.l01.ex01',
                'type' => 'multiple_choice',
                'skill' => 'grammar',
                'prompt' => 'Choose the correct form.',
                'content' => ['options' => [['id' => 'a', 'text' => 'am'], ['id' => 'b', 'text' => 'is']]],
                'solution' => ['correct' => ['b']],
            ]],
        ]],
    ];
}

test('a valid level passes', function () {
    expect(schemaPointers('level', ['key' => 'a1', 'code' => 'A1', 'title' => 'A1 course', 'pass_threshold' => 70]))->toBe([]);
});

test('an invalid level reports where it is wrong', function () {
    $pointers = schemaPointers('level', ['key' => 'A1', 'code' => 'A9', 'title' => '', 'pass_threshold' => 120]);

    expect($pointers)->toContain('/key', '/code', '/title', '/pass_threshold');
});

test('an unknown member is reported at its own pointer', function () {
    expect(schemaPointers('level', ['key' => 'a1', 'code' => 'A1', 'title' => 'A1', 'pass_threshold' => 70, 'position' => 1]))
        ->toBe(['/position']);
});

test('a valid unit passes', function () {
    expect(schemaPointers('unit', validUnit()))->toBe([]);
});

test('an invalid unit reports every problem with its pointer', function () {
    $unit = validUnit();
    $unit['lessons'][0]['theory'] = 'plain string';
    $unit['lessons'][0]['vocabulary'][0]['meaning'] = 'to look for';
    $unit['lessons'][0]['exercises'][0]['content']['options'] = 'a, b';

    $pointers = schemaPointers('unit', $unit);

    expect($pointers)->toContain(
        '/lessons/0/theory',
        '/lessons/0/vocabulary/0/meaning',
        '/lessons/0/exercises/0/content/options',
    );
});

test('a position member on an exercise is rejected at that member', function () {
    $unit = validUnit();
    $unit['lessons'][0]['exercises'][0]['position'] = 3;

    expect(schemaPointers('unit', $unit))->toBe(['/lessons/0/exercises/0/position']);
});

test('the editor hint is accepted and any other unknown member is not', function () {
    $unit = validUnit();
    $unit['$schema'] = '../../../resources/schemas/content/unit.schema.json';

    expect(schemaPointers('unit', $unit))->toBe([])
        ->and(schemaPointers('unit', [...validUnit(), 'level' => 'a1']))->toBe(['/level']);
});

test('a valid exam passes', function () {
    $exam = [
        'key' => 'a1.exam',
        'title' => 'A1 exam',
        'time_limit_minutes' => 60,
        'pass_score' => 70,
        'cooldown_hours' => 24,
        'sections' => [[
            'key' => 'a1.exam.uoe',
            'skill' => 'use_of_english',
            'points' => 20,
            'grading_mode' => 'auto',
            'questions' => [[
                'key' => 'a1.exam.uoe.q01',
                'type' => 'fill_gap',
                'points' => 2,
                'content' => ['text' => 'She {{g1}} home.'],
                'solution' => ['answers' => ['g1' => ['goes']]],
            ]],
        ]],
    ];

    expect(schemaPointers('exam', $exam))->toBe([]);
});

test('an invalid exam reports the section and question problems', function () {
    $exam = [
        'key' => 'a1.exam',
        'title' => 'A1 exam',
        'time_limit_minutes' => 0,
        'pass_score' => 70,
        'cooldown_hours' => 24,
        'sections' => [[
            'key' => 'a1.exam.uoe',
            'skill' => 'dancing',
            'points' => 20,
            'grading_mode' => 'auto',
            'questions' => [[
                'key' => 'a1.exam.uoe.q01',
                'type' => 'fill_gap',
                'points' => 2,
                'content' => ['text' => 'She {{g1}} home.'],
                'solution' => ['answers' => 'goes'],
            ]],
        ]],
    ];

    expect(schemaPointers('exam', $exam))->toContain(
        '/time_limit_minutes',
        '/sections/0/skill',
        '/sections/0/questions/0/solution/answers',
    );
});

test('a valid passage passes', function () {
    expect(schemaPointers('passage', ['key' => 'a1.reading-01', 'title' => 'A day', 'body' => 'Anna wakes up at seven.']))->toBe([])
        ->and(schemaPointers('passage', ['key' => 'a1.listening-01', 'title' => 'Call', 'transcript' => 'Hello?', 'audio_path' => 'a1/call.mp3']))->toBe([]);
});

test('an invalid passage is rejected', function () {
    expect(schemaPointers('passage', ['key' => 'a1 reading', 'title' => 'A day', 'audio_path' => '../secret.mp3']))
        ->toContain('/key', '/audio_path');
});

test('a document that is not an object is rejected at the root', function () {
    $errors = (new SchemaValidator)->validate('unit', json_decode('[]'));

    expect($errors)->toHaveCount(1)
        ->and($errors[0]->pointer)->toBe('');
});

test('an unknown kind is a programming error', function () {
    (new SchemaValidator)->validate('lesson', new stdClass);
})->throws(InvalidArgumentException::class);

test('every kind has a schema file', function (string $kind) {
    expect(SchemaValidator::schemaFile($kind))->toBeFile();
})->with(SchemaValidator::KINDS);
