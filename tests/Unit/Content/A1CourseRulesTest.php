<?php

use Tests\Support\Content\A1CourseRules;

const RULES_FILE = 'levels/a1/units/01-hello.json';

function bilingual(string $text = 'Text'): array
{
    return ['en' => $text, 'es' => "{$text} (es)"];
}

/** A lesson that meets every rule: ten exercises of four auto-graded types. */
function rulesLesson(string $key, string $type): array
{
    $types = ['multiple_choice', 'matching', 'fill_gap', 'reorder'];
    $exercises = [];

    for ($i = 1; $i <= 10; $i++) {
        $exercises[] = ['key' => sprintf('%s.ex%02d', $key, $i), 'type' => $types[$i % 4], 'explanation' => bilingual()];
    }

    if ($type === 'speaking' || $type === 'writing') {
        $exercises[9] = [
            'key' => "{$key}.ex10",
            'type' => $type,
            'content' => ['rubric' => [['key' => 'task', 'description' => bilingual('Does the task'), 'max_points' => 5]]],
        ];
    }

    if ($type === 'listening') {
        $exercises[9] = ['key' => "{$key}.ex10", 'type' => 'dictation', 'audio_path' => 'a1/u02/l03/ex10.mp3', 'explanation' => bilingual()];
    }

    $lesson = ['key' => $key, 'type' => $type, 'title' => 'A lesson', 'theory' => bilingual('Theory')];

    if ($type === 'vocabulary') {
        $lesson['vocabulary'] = array_map(
            fn (int $i) => ['key' => sprintf('%s.v%02d', $key, $i), 'term' => "word {$i}", 'kind' => 'word', 'meaning' => bilingual("word {$i}")],
            range(1, 12),
        );
    }

    return $lesson + ['exercises' => $exercises];
}

/** A unit that meets every rule. */
function rulesUnit(int $number): array
{
    $unitKey = sprintf('a1.u%02d', $number);
    $lessons = [];

    foreach (A1CourseRules::lessonTypes($number) as $index => $type) {
        $lessons[] = rulesLesson(sprintf('%s.l%02d', $unitKey, $index + 1), $type);
    }

    return ['key' => $unitKey, 'title' => 'A unit', 'lessons' => $lessons];
}

test('a unit that follows the template has no violations', function (int $number, string $file) {
    expect(A1CourseRules::unit($file, $number, rulesUnit($number)))->toBe([]);
})->with([
    'odd unit' => [1, 'levels/a1/units/01-hello.json'],
    'even unit' => [2, 'levels/a1/units/02-family-and-friends.json'],
]);

test('lessons alternate reading and speaking with listening and writing', function () {
    expect(A1CourseRules::lessonTypes(1))->toBe(['grammar', 'vocabulary', 'reading', 'speaking'])
        ->and(A1CourseRules::lessonTypes(8))->toBe(['grammar', 'vocabulary', 'listening', 'writing']);
});

test('a lesson out of order names the file and the lesson pointer', function () {
    $unit = rulesUnit(1);
    $unit['lessons'][2]['type'] = 'writing';

    expect(A1CourseRules::unit(RULES_FILE, 1, $unit))
        ->toContain(RULES_FILE.' /lessons/2/type: expected `reading`, found `"writing"`');
});

test('a unit with three lessons is reported', function () {
    $unit = rulesUnit(1);
    array_pop($unit['lessons']);

    expect(A1CourseRules::unit(RULES_FILE, 1, $unit))->toContain(RULES_FILE.' /lessons: expected 4 lessons, found 3');
});

test('a misnamed unit file or key is reported', function () {
    $unit = rulesUnit(1);
    $unit['key'] = 'a1.unit1';

    expect(A1CourseRules::unit('levels/a1/units/hello.json', 1, $unit))
        ->toContain('levels/a1/units/hello.json : unit 1 must be named 01-<slug>.json')
        ->toContain('levels/a1/units/hello.json /key: expected `a1.u01`');
});

test('keys that break the pattern are reported', function () {
    $unit = rulesUnit(1);
    $unit['lessons'][0]['key'] = 'a1.u01.grammar';
    $unit['lessons'][1]['vocabulary'][0]['key'] = 'a1.u01.l02.hello';
    $unit['lessons'][1]['exercises'][0]['key'] = 'a1.u01.l02.ex1';

    expect(A1CourseRules::unit(RULES_FILE, 1, $unit))
        ->toContain(RULES_FILE.' /lessons/0/key: expected `a1.u01.l01`')
        ->toContain(RULES_FILE.' /lessons/1/vocabulary/0/key: expected `a1.u01.l02.v01`')
        ->toContain(RULES_FILE.' /lessons/1/exercises/0/key: expected `a1.u01.l02.ex01`');
});

test('a lesson with nine exercises names the lesson key', function () {
    $unit = rulesUnit(1);
    array_pop($unit['lessons'][0]['exercises']);

    expect(A1CourseRules::unit(RULES_FILE, 1, $unit))
        ->toContain(RULES_FILE.' /lessons/0/exercises: a1.u01.l01 has 9 exercises, at least 10 needed');
});

test('a lesson with three exercise types names the lesson key', function () {
    $unit = rulesUnit(1);

    foreach ($unit['lessons'][0]['exercises'] as $i => $exercise) {
        if ($exercise['type'] === 'reorder') {
            $unit['lessons'][0]['exercises'][$i]['type'] = 'fill_gap';
        }
    }

    expect(A1CourseRules::unit(RULES_FILE, 1, $unit))
        ->toContain(RULES_FILE.' /lessons/0/exercises: a1.u01.l01 uses 3 exercise types, at least 4 needed');
});

test('a vocabulary lesson with eleven items is reported', function () {
    $unit = rulesUnit(1);
    array_pop($unit['lessons'][1]['vocabulary']);

    expect(A1CourseRules::unit(RULES_FILE, 1, $unit))
        ->toContain(RULES_FILE.' /lessons/1/vocabulary: a1.u01.l02 has 11 vocabulary items, at least 12 needed');
});

test('a speaking task outside the production lesson names the exercise key', function () {
    $unit = rulesUnit(1);
    $unit['lessons'][0]['exercises'][9] = $unit['lessons'][3]['exercises'][9];
    $unit['lessons'][0]['exercises'][9]['key'] = 'a1.u01.l01.ex10';

    expect(A1CourseRules::unit(RULES_FILE, 1, $unit))
        ->toContain(RULES_FILE.' /lessons/0/exercises/9/type: a1.u01.l01.ex10: speaking tasks belong only in the production lesson');
});

test('more than two production tasks or a missing rubric are reported', function () {
    $unit = rulesUnit(1);
    $task = $unit['lessons'][3]['exercises'][9];

    foreach ([6, 7, 8] as $i) {
        $unit['lessons'][3]['exercises'][$i] = ['key' => sprintf('a1.u01.l04.ex%02d', $i + 1)] + $task;
    }

    unset($unit['lessons'][3]['exercises'][9]['content']);

    expect(A1CourseRules::unit(RULES_FILE, 1, $unit))
        ->toContain(RULES_FILE.' /lessons/3/exercises: a1.u01.l04 has 4 writing or speaking tasks, at most 2')
        ->toContain(RULES_FILE.' /lessons/3/exercises/9/content/rubric: a1.u01.l04.ex10 needs a rubric');
});

test('a dictation outside a listening lesson or without audio is reported', function () {
    $unit = rulesUnit(1);
    $unit['lessons'][0]['exercises'][0] = ['key' => 'a1.u01.l01.ex01', 'type' => 'dictation', 'explanation' => bilingual()];

    expect(A1CourseRules::unit(RULES_FILE, 1, $unit))
        ->toContain(RULES_FILE.' /lessons/0/exercises/0/type: a1.u01.l01.ex01: dictation belongs only in listening lessons')
        ->toContain(RULES_FILE.' /lessons/0/exercises/0/audio_path: a1.u01.l01.ex01 needs an audio_path under a1/');
});

test('a missing Spanish translation names the key and the member', function (string $pointer, Closure $break, string $key) {
    $unit = rulesUnit(1);
    $break($unit);

    expect(A1CourseRules::unit(RULES_FILE, 1, $unit))->toContain(RULES_FILE." {$pointer}: {$key} is missing es");
})->with([
    'explanation' => ['/lessons/0/exercises/0/explanation', function (array &$unit) {
        unset($unit['lessons'][0]['exercises'][0]['explanation']['es']);
    }, 'a1.u01.l01.ex01'],
    'theory' => ['/lessons/0/theory', function (array &$unit) {
        $unit['lessons'][0]['theory']['es'] = ' ';
    }, 'a1.u01.l01'],
    'meaning' => ['/lessons/1/vocabulary/0/meaning', function (array &$unit) {
        unset($unit['lessons'][1]['vocabulary'][0]['meaning']['es']);
    }, 'a1.u01.l02.v01'],
    'rubric description' => ['/lessons/3/exercises/9/content/rubric/0/description', function (array &$unit) {
        unset($unit['lessons'][3]['exercises'][9]['content']['rubric'][0]['description']['es']);
    }, 'a1.u01.l04.ex10'],
]);

test('a listening passage needs a transcript and an audio path under a1/', function () {
    $file = 'passages/a1/u02-listening.json';

    expect(A1CourseRules::passage($file, ['key' => 'a1.u02.listening', 'title' => 'A photo']))
        ->toContain("{$file} /transcript: a listening passage needs a transcript")
        ->and(A1CourseRules::passage($file, ['key' => 'a1.u02.listening', 'title' => 'A photo', 'transcript' => 'Hi.']))
        ->toContain("{$file} /audio_path: a passage with a transcript needs an audio_path")
        ->and(A1CourseRules::passage($file, ['key' => 'a1.u02.listening', 'title' => 'A photo', 'transcript' => 'Hi.', 'audio_path' => 'b1/x.mp3']))
        ->toContain("{$file} /audio_path: A1 audio lives under a1/")
        ->and(A1CourseRules::passage($file, ['key' => 'a1.u02.listening', 'title' => 'A photo', 'transcript' => 'Hi.', 'audio_path' => 'a1/u02/listening.mp3']))
        ->toBe([])
        ->and(A1CourseRules::passage('passages/a1/u01-reading.json', ['key' => 'a1.u01.reading', 'title' => 'Hello', 'body' => 'Hi.']))
        ->toBe([]);
});

test('the level must be A1 with an 80 % threshold', function () {
    $file = 'levels/a1/level.json';

    expect(A1CourseRules::level($file, ['key' => 'a1', 'code' => 'A1', 'title' => 'Beginner', 'pass_threshold' => 80]))->toBe([])
        ->and(A1CourseRules::level($file, ['key' => 'a1', 'code' => 'A1', 'title' => 'Beginner', 'pass_threshold' => 70]))
        ->toBe(["{$file} /pass_threshold: expected 80, found 70"]);
});

/** Eight rule-abiding units whose exercises carry the D1 grammar spiral. */
function rulesCourse(): array
{
    $spiral = [2 => 'She is my sister.', 3 => 'There are two rooms.', 5 => "He doesn't swim.", 6 => 'Do we have any eggs?', 7 => 'There is a bank.', 8 => 'It was cold.'];
    $course = [];

    foreach (range(1, 8) as $number) {
        $unit = rulesUnit($number);
        $unit['lessons'][0]['exercises'][0]['solution'] = ['accepted' => [$spiral[$number] ?? 'Hello.']];
        $course[sprintf('levels/a1/units/%02d-unit.json', $number)] = $unit;
    }

    return $course;
}

test('a complete course with its grammar spiral has no violations', function () {
    expect(A1CourseRules::course(rulesCourse()))->toBe([]);
});

test('a course without eight units of four lessons is reported', function () {
    $course = rulesCourse();
    array_pop($course);

    expect(A1CourseRules::course($course))
        ->toContain('levels/a1/units : expected 8 units, found 7')
        ->toContain('levels/a1/units : expected 32 lessons, found 28');
});

test('a unit that drops its spiral grammar point names the unit file', function () {
    $course = rulesCourse();
    $course['levels/a1/units/07-unit.json']['lessons'][0]['exercises'][0]['solution'] = ['accepted' => ['Turn left.']];

    expect(A1CourseRules::course($course))
        ->toBe(['levels/a1/units/07-unit.json /lessons: unit 7 should bring back there is/are (grammar spiral)']);
});

/** An A1 exam that meets every rule. */
function rulesExam(): array
{
    $auto = fn (string $key, ?string $passage = null) => array_filter([
        'key' => $key,
        'type' => 'fill_gap',
        'points' => 1,
        'passage' => $passage,
        'content' => ['text' => 'I {{g1}} here.'],
        'solution' => ['answers' => ['g1' => ['am']]],
        'explanation' => bilingual(),
    ]);
    $task = fn (string $key, string $type) => [
        'key' => $key,
        'type' => $type,
        'points' => 20,
        'content' => ['task' => 'Do it.', 'rubric' => [['key' => 'task', 'description' => bilingual('Did it'), 'max_points' => 20]]],
        'solution' => ['model_answer' => 'Done.'],
    ];

    $uoe = [];
    foreach (range(1, 8) as $unit) {
        foreach (range(1, 4) as $q) {
            $uoe[] = $auto(sprintf('a1.exam.uoe.u%02d.q%02d', $unit, $q));
        }
    }

    $sections = [];
    foreach (A1CourseRules::EXAM_SECTIONS as $key => $section) {
        $questions = match ($section['skill']) {
            'use_of_english' => $uoe,
            'reading', 'listening' => array_map(fn (int $q) => $auto("{$key}.q{$q}", "{$key}-1"), range(1, 10)),
            default => array_map(fn (int $q) => $task("{$key}.q{$q}", $section['skill']), range(1, 4)),
        };
        $perAttempt = match ($section['skill']) {
            'use_of_english' => 15,
            'reading', 'listening' => 5,
            default => 1,
        };
        $sections[] = ['key' => $key, ...$section, 'questions_per_attempt' => $perAttempt, 'questions' => $questions];
    }

    return [...A1CourseRules::EXAM, 'title' => 'A1 exam', 'sections' => $sections];
}

const EXAM_FILE = 'levels/a1/exam.json';

test('an exam that follows the rules has no violations', function () {
    expect(A1CourseRules::exam(EXAM_FILE, rulesExam()))->toBe([]);
});

test('exam settings and section points are checked', function () {
    $exam = rulesExam();
    $exam['pass_score'] = 70;
    $exam['max_attempts'] = 3;
    $exam['sections'][4]['points'] = 20;
    $exam['sections'][3]['grading_mode'] = 'ai';

    expect(A1CourseRules::exam(EXAM_FILE, $exam))
        ->toContain(EXAM_FILE.' /pass_score: expected 60, found 70')
        ->toContain(EXAM_FILE.' /max_attempts: expected null, found 3')
        ->toContain(EXAM_FILE.' /sections: section points total 105, expected 100')
        ->toContain(EXAM_FILE.' /sections/4/points: a1.exam.speaking: expected 15, found 20')
        ->toContain(EXAM_FILE.' /sections/3/grading_mode: a1.exam.writing: expected "self_assessment", found "ai"');
});

test('a bank smaller than twice the attempt names the section key', function () {
    $exam = rulesExam();
    $exam['sections'][1]['questions'] = array_slice($exam['sections'][1]['questions'], 0, 8);

    expect(A1CourseRules::exam(EXAM_FILE, $exam))
        ->toBe([EXAM_FILE.' /sections/1/questions: a1.exam.reading has 8 questions for 5 per attempt; the bank must hold at least twice the attempt']);
});

test('missing solutions, explanations, passages and unit coverage are reported', function () {
    $exam = rulesExam();
    unset($exam['sections'][2]['questions'][0]['solution'], $exam['sections'][2]['questions'][0]['passage']);
    $exam['sections'][0]['questions'][0]['explanation'] = ['en' => 'Only English'];
    $exam['sections'][0]['questions'] = array_values(array_filter(
        $exam['sections'][0]['questions'],
        fn (array $q) => ! str_starts_with($q['key'], 'a1.exam.uoe.u05.') || $q['key'] === 'a1.exam.uoe.u05.q01',
    ));

    expect(A1CourseRules::exam(EXAM_FILE, $exam))
        ->toContain(EXAM_FILE.' /sections/2/questions/0/solution: a1.exam.listening.q1 needs a solution')
        ->toContain(EXAM_FILE.' /sections/2/questions/0/passage: a1.exam.listening.q1 needs a passage')
        ->toContain(EXAM_FILE.' /sections/0/questions/0/explanation: a1.exam.uoe.u01.q01 is missing es')
        ->toContain(EXAM_FILE.' /sections/0/questions: unit 5 has 1 Use of English questions, at least 2 needed');
});
