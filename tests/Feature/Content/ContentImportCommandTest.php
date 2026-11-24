<?php

use App\Console\Commands\ContentImportCommand;
use App\Content\ContentTables;
use App\Models\Exam;
use App\Models\Exercise;
use App\Models\Lesson;
use App\Models\VocabularyItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Tests\Support\Content\FixtureTree;

afterEach(fn () => FixtureTree::cleanUp());

const COMMAND_GREETINGS = 'levels/a1/units/01-greetings.json';
const COMMAND_FAMILY = 'levels/a1/units/02-family.json';

/** Every content row as table → key → [parent key, position], read back from the database. */
function storedTree(): array
{
    $tree = [];

    foreach (ContentTables::all() as $name => $table) {
        $parents = $table->parentTable === null
            ? []
            : DB::table($table->parentTable)->pluck('key', 'id')->all();

        foreach (DB::table($name)->get() as $row) {
            $tree[$name][$row->key] = [
                $table->parentColumn === null ? null : ($parents[$row->{$table->parentColumn}] ?? null),
                $table->positioned ? $row->position : null,
            ];
        }
    }

    return $tree;
}

test('the fixture tree imports into an empty database with every key, parent and position', function () {
    $this->artisan('content:import', ['--path' => 'tests/Fixtures/content'])
        ->expectsOutputToContain('Content imported from 7 files.')
        ->assertExitCode(0);

    $expected = [];

    foreach (FixtureTree::tree(FixtureTree::fixtures())->all() as $row) {
        $expected[$row->table][$row->key] = [$row->parentKey, $row->position];
    }

    $stored = storedTree();

    foreach (ContentTables::order() as $table) {
        expect($stored[$table] ?? [])->toEqualCanonicalizing($expected[$table], $table);
    }

    // The file's `$schema` member is an editor hint, not data.
    expect(Exercise::query()->where('key', 'a1.u01.l01.ex02')->sole()->prompt)->toBe('Complete the sentence.');
});

test('lessons take the order of their array', function () {
    $directory = FixtureTree::copy();

    FixtureTree::edit($directory, COMMAND_GREETINGS, function (array $unit) {
        [$unit['lessons'][0], $unit['lessons'][1]] = [$unit['lessons'][1], $unit['lessons'][0]];

        return $unit;
    });

    $this->artisan('content:import', ['--path' => $directory])->assertExitCode(0);

    expect(Lesson::query()->where('key', 'a1.u01.l02')->value('position'))->toBe(1)
        ->and(Lesson::query()->where('key', 'a1.u01.l01')->value('position'))->toBe(2);
});

test('a second import of the same tree changes nothing', function () {
    $this->artisan('content:import', ['--path' => 'tests/Fixtures/content'])->assertExitCode(0);

    $before = [];

    foreach (ContentTables::order() as $table) {
        $before[$table] = DB::table($table)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
    }

    Date::setTestNow(now()->addHour());

    $this->artisan('content:import', ['--path' => 'tests/Fixtures/content'])
        ->expectsTable(ContentImportCommand::SUMMARY_HEADERS, [
            ['levels', 0, 0, 1, 0, 0],
            ['passages', 0, 0, 2, 0, 0],
            ['units', 0, 0, 2, 0, 0],
            ['lessons', 0, 0, 7, 0, 0],
            ['vocabulary_items', 0, 0, 3, 0, 0],
            ['exercises', 0, 0, 9, 0, 0],
            ['exams', 0, 0, 2, 0, 0],
            ['exam_sections', 0, 0, 6, 0, 0],
            ['exam_questions', 0, 0, 9, 0, 0],
        ])
        ->assertExitCode(0);

    foreach (ContentTables::order() as $table) {
        expect(DB::table($table)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all())->toBe($before[$table]);
    }
});

test('a changed lesson title is updated and counted', function () {
    $this->artisan('content:import', ['--path' => 'tests/Fixtures/content'])->assertExitCode(0);
    $directory = FixtureTree::copy();

    FixtureTree::edit($directory, COMMAND_GREETINGS, function (array $unit) {
        $unit['lessons'][0]['title'] = 'Be: am, is, are';

        return $unit;
    });

    $this->artisan('content:import', ['--path' => $directory])
        ->expectsOutputToContain('lessons')
        ->expectsTable(ContentImportCommand::SUMMARY_HEADERS, [
            ['levels', 0, 0, 1, 0, 0],
            ['passages', 0, 0, 2, 0, 0],
            ['units', 0, 0, 2, 0, 0],
            ['lessons', 0, 1, 6, 0, 0],
            ['vocabulary_items', 0, 0, 3, 0, 0],
            ['exercises', 0, 0, 9, 0, 0],
            ['exams', 0, 0, 2, 0, 0],
            ['exam_sections', 0, 0, 6, 0, 0],
            ['exam_questions', 0, 0, 9, 0, 0],
        ])
        ->assertExitCode(0);

    expect(Lesson::query()->where('key', 'a1.u01.l01')->value('title'))->toBe('Be: am, is, are');
});

test('a lesson moved to another unit file keeps its id', function () {
    $this->artisan('content:import', ['--path' => 'tests/Fixtures/content'])->assertExitCode(0);
    $lesson = Lesson::query()->where('key', 'a1.u01.l02')->sole();
    $vocabulary = $lesson->vocabularyItems()->pluck('id')->all();
    $directory = FixtureTree::copy();

    $moved = null;
    FixtureTree::edit($directory, COMMAND_GREETINGS, function (array $unit) use (&$moved) {
        $moved = array_splice($unit['lessons'], 1, 1)[0];

        return $unit;
    });
    FixtureTree::edit($directory, COMMAND_FAMILY, function (array $unit) use (&$moved) {
        $unit['lessons'][] = $moved;

        return $unit;
    });

    $this->artisan('content:import', ['--path' => $directory])->assertExitCode(0);

    expect($lesson->fresh())
        ->unit->key->toBe('a1.u02')
        ->position->toBe(5)
        ->and($lesson->fresh()->vocabularyItems()->pluck('id')->all())->toBe($vocabulary)
        ->and(Lesson::query()->where('key', 'a1.u01.l03')->value('position'))->toBe(2);
});

test('a translation removed from the file is removed from the row', function () {
    $this->artisan('content:import', ['--path' => 'tests/Fixtures/content'])->assertExitCode(0);
    $directory = FixtureTree::copy();

    FixtureTree::edit($directory, COMMAND_GREETINGS, function (array $unit) {
        unset($unit['lessons'][1]['vocabulary'][0]['meaning']['es']);

        return $unit;
    });

    $this->artisan('content:import', ['--path' => $directory])->assertExitCode(0);

    expect(VocabularyItem::query()->where('key', 'a1.u01.l02.v01')->sole()->getTranslations('meaning'))->toBe(['en' => 'a greeting']);
});

test('an optional translation removed from the file is cleared', function () {
    $this->artisan('content:import', ['--path' => 'tests/Fixtures/content'])->assertExitCode(0);
    $directory = FixtureTree::copy();

    FixtureTree::edit($directory, COMMAND_GREETINGS, function (array $unit) {
        unset($unit['lessons'][0]['exercises'][0]['explanation']);

        return $unit;
    });

    $this->artisan('content:import', ['--path' => $directory])->assertExitCode(0);

    expect(Exercise::query()->where('key', 'a1.u01.l01.ex01')->sole()->getTranslations('explanation'))->toBe([]);
});

test('an edit made in the panel is overwritten by the files', function () {
    $this->artisan('content:import', ['--path' => 'tests/Fixtures/content'])->assertExitCode(0);
    Exercise::query()->where('key', 'a1.u01.l01.ex01')->sole()->update(['prompt' => 'Edited in the panel']);

    $this->artisan('content:import', ['--path' => 'tests/Fixtures/content'])->assertExitCode(0);

    expect(Exercise::query()->where('key', 'a1.u01.l01.ex01')->value('prompt'))->toBe('Choose the correct form.');
});

test('an invalid tree writes nothing', function () {
    $directory = FixtureTree::copy();

    FixtureTree::edit($directory, COMMAND_FAMILY, function (array $unit) {
        $unit['lessons'][0]['exercises'][0]['solution'] = [];

        return $unit;
    });

    $this->artisan('content:import', ['--path' => $directory])
        ->expectsOutputToContain(COMMAND_FAMILY.' /lessons/0/exercises/0')
        ->expectsOutputToContain('nothing was imported')
        ->assertExitCode(1);

    foreach (ContentTables::order() as $table) {
        expect(DB::table($table)->count())->toBe(0, $table);
    }
});

test('a level exam under a new key fails before anything is written', function () {
    $this->artisan('content:import', ['--path' => 'tests/Fixtures/content'])->assertExitCode(0);
    $directory = FixtureTree::copy();

    FixtureTree::edit($directory, 'levels/a1/exam.json', fn (array $exam) => ['key' => 'a1.final'] + $exam);
    FixtureTree::edit($directory, COMMAND_GREETINGS, function (array $unit) {
        $unit['lessons'][0]['title'] = 'Not written';

        return $unit;
    });

    $this->artisan('content:import', ['--path' => $directory])
        ->expectsOutputToContain('levels/a1/exam.json /key: The level `a1` already has the exam `a1.exam`')
        ->expectsOutputToContain('nothing was imported')
        ->assertExitCode(1);

    expect(Lesson::query()->where('key', 'a1.u01.l01')->value('title'))->toBe('The verb to be')
        ->and(Exam::query()->where('key', 'a1.final')->exists())->toBeFalse();
});

test('a failure while writing the exams rolls back the whole import', function () {
    Exam::saving(function (Model $exam): void {
        throw new RuntimeException('Simulated failure.');
    });

    expect(fn () => $this->artisan('content:import', ['--path' => 'tests/Fixtures/content'])->run())
        ->toThrow(RuntimeException::class, 'Simulated failure.');

    foreach (ContentTables::order() as $table) {
        expect(DB::table($table)->count())->toBe(0, $table);
    }
});
