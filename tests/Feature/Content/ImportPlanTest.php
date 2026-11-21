<?php

use App\Content\ContentImporter;
use App\Content\ContentTables;
use App\Content\ImportPlan;
use App\Models\Exam;
use App\Models\Exercise;
use App\Models\Lesson;
use App\Models\Level;
use Tests\Support\Content\FixtureTree;

afterEach(fn () => FixtureTree::cleanUp());

const PLAN_GREETINGS = 'levels/a1/units/01-greetings.json';

function importFixtures(?string $directory = null): void
{
    app(ContentImporter::class)->import(FixtureTree::tree($directory ?? FixtureTree::fixtures()));
}

test('against an empty database every row is created', function () {
    $tree = FixtureTree::tree(FixtureTree::fixtures());
    $plan = ImportPlan::build($tree);

    foreach (ContentTables::order() as $table) {
        expect($plan->keys($table, ImportPlan::CREATE))->toBe($tree->keys($table))
            ->and($plan->count($table, ImportPlan::UNCHANGED))->toBe(0);
    }

    expect($plan->hasErrors())->toBeFalse()
        ->and($plan->changesAnything())->toBeTrue();
});

test('after an import the same tree is unchanged everywhere', function () {
    importFixtures();

    $tree = FixtureTree::tree(FixtureTree::fixtures());
    $plan = ImportPlan::build($tree);

    foreach (ContentTables::order() as $table) {
        expect($plan->keys($table, ImportPlan::UNCHANGED))->toBe($tree->keys($table));
    }

    expect($plan->changesAnything())->toBeFalse();
});

test('a changed attribute, a moved row and a reorder are updates', function () {
    importFixtures();
    $directory = FixtureTree::copy();

    FixtureTree::edit($directory, PLAN_GREETINGS, function (array $unit) {
        $unit['lessons'][0]['title'] = 'The verb to be, again';
        // Swap the first two exercises of the first lesson.
        [$first, $second] = [$unit['lessons'][0]['exercises'][0], $unit['lessons'][0]['exercises'][1]];
        $unit['lessons'][0]['exercises'][0] = $second;
        $unit['lessons'][0]['exercises'][1] = $first;

        return $unit;
    });

    $plan = ImportPlan::build(FixtureTree::tree($directory));

    expect($plan->keys(ContentTables::LESSONS, ImportPlan::UPDATE))->toBe(['a1.u01.l01'])
        ->and($plan->keys(ContentTables::EXERCISES, ImportPlan::UPDATE))->toBe(['a1.u01.l01.ex02', 'a1.u01.l01.ex01'])
        ->and($plan->action(ContentTables::EXERCISES, 'a1.u01.l01.ex03'))->toBe(ImportPlan::UNCHANGED);
});

test('a translation stored in another key order is unchanged', function () {
    importFixtures();

    // PostgreSQL normalises jsonb, so the stored order never matches the file's anyway;
    // here the file lists `es` before `en` on purpose.
    $directory = FixtureTree::copy();
    FixtureTree::edit($directory, PLAN_GREETINGS, function (array $unit) {
        $unit['lessons'][0]['theory'] = array_reverse($unit['lessons'][0]['theory'], true);

        return $unit;
    });

    expect(ImportPlan::build(FixtureTree::tree($directory))->changesAnything())->toBeFalse();
});

test('a row missing from the files is to archive and an archived row in the files is to restore', function () {
    importFixtures();

    Exercise::query()->where('key', 'a1.u01.l01.ex03')->sole()->archive();
    $extra = Exercise::factory()->for(Lesson::query()->where('key', 'a1.u01.l01')->sole())->create(['key' => 'a1.u01.l01.extra', 'position' => 4]);

    $plan = ImportPlan::build(FixtureTree::tree(FixtureTree::fixtures()));

    expect($plan->keys(ContentTables::EXERCISES, ImportPlan::RESTORE))->toBe(['a1.u01.l01.ex03'])
        ->and($plan->keys(ContentTables::EXERCISES, ImportPlan::ARCHIVE))->toBe([$extra->key]);
});

test('a row whose parent is new is an update', function () {
    importFixtures();
    $directory = FixtureTree::copy();

    FixtureTree::edit($directory, PLAN_GREETINGS, function (array $unit) {
        $unit['lessons'][] = [
            'key' => 'a1.u01.l99',
            'type' => 'vocabulary',
            'title' => 'New lesson',
            'exercises' => [array_pop($unit['lessons'][0]['exercises'])],
        ];

        return $unit;
    });

    $plan = ImportPlan::build(FixtureTree::tree($directory));

    expect($plan->keys(ContentTables::LESSONS, ImportPlan::CREATE))->toBe(['a1.u01.l99'])
        ->and($plan->action(ContentTables::EXERCISES, 'a1.u01.l01.ex03'))->toBe(ImportPlan::UPDATE);
});

test('a level exam under a new key is an error naming both keys', function () {
    importFixtures();
    $directory = FixtureTree::copy();

    FixtureTree::edit($directory, 'levels/a1/exam.json', fn (array $exam) => ['key' => 'a1.final'] + $exam);

    $plan = ImportPlan::build(FixtureTree::tree($directory));

    expect($plan->hasErrors())->toBeTrue()
        ->and((string) $plan->errors()[0])->toContain('levels/a1/exam.json /key')
        ->toContain('`a1.exam`')
        ->toContain('`a1.final`');
});

test('an archived level exam still blocks a new key', function () {
    importFixtures();
    Exam::query()->where('key', 'a1.exam')->sole()->archive();
    $directory = FixtureTree::copy();

    FixtureTree::edit($directory, 'levels/a1/exam.json', fn (array $exam) => ['key' => 'a1.final'] + $exam);

    expect(ImportPlan::build(FixtureTree::tree($directory))->hasErrors())->toBeTrue();
});

test('a level code stored under another key is an error naming both keys', function () {
    Level::factory()->create(['key' => 'beginners', 'code' => 'A1', 'position' => 1]);

    $plan = ImportPlan::build(FixtureTree::tree(FixtureTree::fixtures()));

    expect($plan->hasErrors())->toBeTrue()
        ->and((string) $plan->errors()[0])->toContain('levels/a1/level.json /key')
        ->toContain('`beginners`')
        ->toContain('`a1`');
});

test('a missing optional translation matches one cleared in the panel', function () {
    importFixtures();

    // The panel stores a cleared translation as `[]`; the file simply leaves it out.
    DB::table('exercises')->where('key', 'a1.u01.l01.ex02')->update(['explanation' => '[]']);

    expect(ImportPlan::build(FixtureTree::tree(FixtureTree::fixtures()))->action(ContentTables::EXERCISES, 'a1.u01.l01.ex02'))
        ->toBe(ImportPlan::UNCHANGED);
});
