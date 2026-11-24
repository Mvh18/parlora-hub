<?php

use App\Content\ContentImporter;
use App\Models\Exercise;
use App\Models\Lesson;
use App\Models\Level;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;
use Tests\Support\Content\FixtureTree;

afterEach(fn () => FixtureTree::cleanUp());

const IMPORTER_GREETINGS = 'levels/a1/units/01-greetings.json';
const IMPORTER_FAMILY = 'levels/a1/units/02-family.json';

function importTree(string $directory): void
{
    app(ContentImporter::class)->import(FixtureTree::tree($directory));
}

/**
 * @return array<string, int> key → position of a lesson's exercises
 */
function exercisePositions(string $lessonKey): array
{
    return Exercise::query()
        ->where('lesson_id', Lesson::query()->where('key', $lessonKey)->value('id'))
        ->orderBy('position')
        ->pluck('position', 'key')
        ->all();
}

test('the import takes the advisory lock inside its transaction', function () {
    $queries = [];
    DB::listen(function ($query) use (&$queries) {
        $queries[] = $query->sql;
    });

    importTree(FixtureTree::fixtures());

    expect($queries[0])->toBe('select pg_advisory_xact_lock(?)');
});

test('two exercises that swap places swap positions', function () {
    importTree(FixtureTree::fixtures());
    $directory = FixtureTree::copy();

    FixtureTree::edit($directory, IMPORTER_GREETINGS, function (array $unit) {
        $exercises = $unit['lessons'][0]['exercises'];
        $unit['lessons'][0]['exercises'] = [$exercises[1], $exercises[0], $exercises[2]];

        return $unit;
    });

    importTree($directory);

    expect(exercisePositions('a1.u01.l01'))->toBe([
        'a1.u01.l01.ex02' => 1,
        'a1.u01.l01.ex01' => 2,
        'a1.u01.l01.ex03' => 3,
    ]);
});

test('a lesson moved into an occupied first position pushes the other one down', function () {
    importTree(FixtureTree::fixtures());
    $directory = FixtureTree::copy();
    $moved = Lesson::query()->where('key', 'a1.u01.l03')->sole();

    $lesson = null;
    FixtureTree::edit($directory, IMPORTER_GREETINGS, function (array $unit) use (&$lesson) {
        $lesson = array_pop($unit['lessons']);

        return $unit;
    });
    FixtureTree::edit($directory, IMPORTER_FAMILY, function (array $unit) use (&$lesson) {
        array_unshift($unit['lessons'], $lesson);

        return $unit;
    });

    importTree($directory);

    $family = Unit::query()->where('key', 'a1.u02')->sole();

    expect($moved->fresh())
        ->id->toBe($moved->id)
        ->unit_id->toBe($family->id)
        ->position->toBe(1)
        ->and(Lesson::query()->where('key', 'a1.u02.l01')->value('position'))->toBe(2)
        ->and($family->lessons()->pluck('key')->all())->toBe(['a1.u01.l03', 'a1.u02.l01', 'a1.u02.l02', 'a1.u02.l03', 'a1.u02.l04']);
});

test('siblings that are not in the files go after the file rows in their previous order', function () {
    importTree(FixtureTree::fixtures());
    $lesson = Lesson::query()->where('key', 'a1.u01.l01')->sole();

    // Two rows the files do not know, between and before the file rows.
    DB::table('exercises')->where('lesson_id', $lesson->id)->update(['position' => DB::raw('position + 10')]);
    $archived = Exercise::factory()->for($lesson)->create(['key' => 'a1.u01.l01.old', 'position' => 1]);
    $archived->archive();
    Exercise::factory()->for($lesson)->create(['key' => 'a1.u01.l01.later', 'position' => 25]);

    importTree(FixtureTree::fixtures());

    expect(exercisePositions('a1.u01.l01'))->toBe([
        'a1.u01.l01.ex01' => 1,
        'a1.u01.l01.ex02' => 2,
        'a1.u01.l01.ex03' => 3,
        'a1.u01.l01.old' => 4,
        'a1.u01.l01.later' => 5,
    ]);
});

test('levels follow CEFR order and a level not in the files goes after them', function () {
    Level::factory()->create(['key' => 'b1', 'code' => 'B1', 'position' => 1]);

    importTree(FixtureTree::fixtures());

    expect(Level::query()->orderBy('position')->pluck('position', 'key')->all())->toBe(['a1' => 1, 'b1' => 2]);
});

test('units follow the file names', function () {
    $directory = FixtureTree::copy();
    rename("{$directory}/".IMPORTER_GREETINGS, "{$directory}/levels/a1/units/03-greetings.json");

    importTree($directory);

    expect(Unit::query()->orderBy('position')->pluck('position', 'key')->all())->toBe(['a1.u02' => 1, 'a1.u01' => 2]);
});
