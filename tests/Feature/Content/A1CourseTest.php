<?php

use App\Content\ContentValidator;
use Tests\Support\Content\A1CourseRules;

/*
 * The A1 course shipped in content/ follows the course rules. The checks run
 * over whatever units exist, so each unit can land in its own pull request;
 * the last unit switches on the "eight units" assertion.
 */

function a1Root(): string
{
    return base_path('content');
}

/** @return array<string, array<string, mixed>> relative path → decoded file */
function a1Files(string $pattern): array
{
    $files = [];

    foreach (glob(a1Root().'/'.$pattern) ?: [] as $path) {
        $files[substr($path, strlen(a1Root()) + 1)] = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }

    ksort($files, SORT_STRING);

    return $files;
}

test('the shipped content validates', function () {
    $report = app(ContentValidator::class)->validate(a1Root());

    expect(array_map(strval(...), $report->errors()))->toBe([]);
});

test('the A1 level is A1 with an 80 % exercise threshold', function () {
    $levels = a1Files('levels/a1/level.json');

    expect($levels)->toHaveCount(1);

    foreach ($levels as $file => $level) {
        expect(A1CourseRules::level($file, $level))->toBe([]);
    }
});

test('every shipped unit follows the A1 template and minimums', function () {
    $violations = [];
    $number = 0;

    foreach (a1Files('levels/a1/units/*.json') as $file => $unit) {
        $violations = [...$violations, ...A1CourseRules::unit($file, ++$number, $unit)];
    }

    expect($violations)->toBe([]);
});

test('the A1 course has eight units of four lessons and its grammar spiral', function () {
    expect(A1CourseRules::course(a1Files('levels/a1/units/*.json')))->toBe([]);
});

test('the A1 exam follows its rules', function () {
    $exams = a1Files('levels/a1/exam.json');

    expect($exams)->toHaveCount(1);

    foreach ($exams as $file => $exam) {
        expect(A1CourseRules::exam($file, $exam))->toBe([]);
    }
});

test('every shipped A1 passage has its transcript and audio path', function () {
    $violations = [];

    foreach (a1Files('passages/a1/*.json') as $file => $passage) {
        $violations = [...$violations, ...A1CourseRules::passage($file, $passage)];
    }

    expect($violations)->toBe([]);
});
