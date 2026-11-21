<?php

use App\Content\ContentLoader;
use App\Content\ContentRow;
use App\Content\ContentTables;
use App\Content\ContentTree;
use App\Content\ValidationReport;
use App\Models\Exam;
use App\Models\Level;
use Illuminate\Database\Eloquent\Model;
use Tests\Support\Content\FixtureTree;

afterEach(fn () => FixtureTree::cleanUp());

function treeOf(string $directory): ContentTree
{
    return ContentTree::fromFiles((new ContentLoader)->load($directory, new ValidationReport));
}

/** @return array<string, ContentRow> rows of a table by key */
function rowsByKey(ContentTree $tree, string $table): array
{
    $rows = [];

    foreach ($tree->rows($table) as $row) {
        $rows[$row->key] = $row;
    }

    return $rows;
}

test('the tables are in write order, parents before children', function () {
    expect(ContentTables::order())->toBe([
        'levels', 'passages', 'units', 'lessons', 'vocabulary_items', 'exercises', 'exams', 'exam_sections', 'exam_questions',
    ]);

    $seen = [];

    foreach (ContentTables::all() as $name => $table) {
        if ($table->parentTable !== null) {
            expect($seen)->toContain($table->parentTable);
        }

        $seen[] = $name;
    }
});

test('the positioned tables are the ones with a position column', function () {
    expect(ContentTables::positioned())->toBe(['levels', 'units', 'lessons', 'vocabulary_items', 'exercises', 'exam_sections'])
        ->and(ContentTables::get('levels')->hasGlobalPositions())->toBeTrue()
        ->and(ContentTables::get('units')->hasGlobalPositions())->toBeFalse();
});

test('a table is found by its model and an unknown one is an error', function () {
    expect(ContentTables::forModel(Level::class)->name)->toBe('levels')
        ->and(ContentTables::forModel(Exam::class)->name)->toBe('exams');

    ContentTables::get('nope');
})->throws(InvalidArgumentException::class);

test('a class that is not a content model is an error', function () {
    ContentTables::forModel(stdClass::class);
})->throws(InvalidArgumentException::class);

test('every table maps to a model with the columns the tree writes', function () {
    foreach (ContentTables::all() as $table) {
        expect(is_subclass_of($table->model, Model::class))->toBeTrue();
    }
});

test('the fixture tree flattens to the expected rows per table', function () {
    $tree = treeOf(dirname(__DIR__, 2).'/Fixtures/content');

    expect($tree->keys('levels'))->toBe(['a1'])
        ->and($tree->keys('units'))->toBe(['a1.u01', 'a1.u02'])
        ->and($tree->keys('lessons'))->toHaveCount(7)
        ->and($tree->keys('passages'))->toBe(['a1.listening-01', 'a1.reading-01'])
        ->and($tree->keys('exams'))->toBe(['a1.exam', 'placement'])
        ->and($tree->keys('exam_sections'))->toHaveCount(6)
        ->and($tree->keys('exercises'))->toHaveCount(9);
});

test('parents are implied by nesting and positions by array order', function () {
    $tree = treeOf(dirname(__DIR__, 2).'/Fixtures/content');

    $units = rowsByKey($tree, 'units');
    $lessons = rowsByKey($tree, 'lessons');
    $exercises = rowsByKey($tree, 'exercises');

    expect($units['a1.u01']->parentKey)->toBe('a1')
        ->and($units['a1.u01']->position)->toBe(1)
        ->and($units['a1.u02']->position)->toBe(2)
        ->and($lessons['a1.u01.l02']->parentKey)->toBe('a1.u01')
        ->and($lessons['a1.u01.l02']->position)->toBe(2)
        ->and($lessons['a1.u01.l02']->pointer)->toBe('/lessons/1')
        ->and($exercises['a1.u01.l01.ex03']->parentKey)->toBe('a1.u01.l01')
        ->and($exercises['a1.u01.l01.ex03']->position)->toBe(3)
        ->and($exercises['a1.u01.l01.ex03']->pointer)->toBe('/lessons/0/exercises/2')
        ->and($exercises['a1.u01.l01.ex03']->file)->toBe('levels/a1/units/01-greetings.json');
});

test('the level position comes from the CEFR order and the exam kind from the path', function () {
    $tree = treeOf(dirname(__DIR__, 2).'/Fixtures/content');

    $level = rowsByKey($tree, 'levels')['a1'];
    $exams = rowsByKey($tree, 'exams');

    expect($level->position)->toBe(1)
        ->and($exams['a1.exam']->attributes['kind'])->toBe('level')
        ->and($exams['a1.exam']->parentKey)->toBe('a1')
        ->and($exams['placement']->attributes['kind'])->toBe('placement')
        ->and($exams['placement']->parentKey)->toBeNull();
});

test('passages are referenced by key and optional columns are explicit nulls', function () {
    $tree = treeOf(dirname(__DIR__, 2).'/Fixtures/content');

    $exercise = rowsByKey($tree, 'exercises')['a1.u02.l01.ex01'];
    $question = rowsByKey($tree, 'exam_questions')['a1.exam.reading.q01'];
    $vocabulary = rowsByKey($tree, 'vocabulary_items')['a1.u01.l02.v02'];

    expect($exercise->passageKey)->toBe('a1.reading-01')
        ->and($question->passageKey)->toBe('a1.reading-01')
        ->and($vocabulary->attributes['example'])->toBeNull()
        ->and($vocabulary->attributes['meaning'])->toBe(['en' => 'said when you meet someone for the first time'])
        ->and($exercise->attributes)->not->toHaveKeys(['key', 'position', 'passage', 'lesson']);
});

test('exam questions form a bank: no position', function () {
    $tree = treeOf(dirname(__DIR__, 2).'/Fixtures/content');

    foreach ($tree->rows('exam_questions') as $question) {
        expect($question->position)->toBeNull();
    }

    expect($tree->all())->not->toBeEmpty();
});
