<?php

use App\Console\Commands\ContentImportCommand;
use App\Content\ContentTables;
use App\Models\Exam;
use App\Models\Exercise;
use App\Models\Lesson;
use App\Models\Unit;
use App\Models\VocabularyItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Support\Content\FixtureTree;

afterEach(fn () => FixtureTree::cleanUp());

const ARCHIVE_GREETINGS = 'levels/a1/units/01-greetings.json';
const ARCHIVE_FAMILY = 'levels/a1/units/02-family.json';

function importForArchive(?string $directory = null): void
{
    test()->artisan('content:import', ['--path' => $directory ?? FixtureTree::fixtures()])->assertExitCode(0);
}

/** Drop the third exercise of the first lesson (`a1.u01.l01.ex03`) from a copy of the fixtures. */
function withoutThirdExercise(string $directory): void
{
    FixtureTree::edit($directory, ARCHIVE_GREETINGS, function (array $unit) {
        array_splice($unit['lessons'][0]['exercises'], 2, 1);

        return $unit;
    });
}

/** Every content row, table by table, exactly as stored. */
function contentSnapshot(): array
{
    $snapshot = [];

    foreach (ContentTables::order() as $table) {
        $snapshot[$table] = DB::table($table)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
    }

    return $snapshot;
}

test('an exercise removed from its unit file is archived, not deleted', function () {
    importForArchive();
    $directory = FixtureTree::copy();
    withoutThirdExercise($directory);

    importForArchive($directory);

    $exercise = Exercise::query()->where('key', 'a1.u01.l01.ex03')->sole();

    expect($exercise->isArchived())->toBeTrue()
        ->and(Exercise::query()->active()->where('key', 'a1.u01.l01.ex03')->exists())->toBeFalse()
        ->and($exercise->lesson->key)->toBe('a1.u01.l01');
});

test('a removed unit file archives the unit and everything under it', function () {
    importForArchive();
    $directory = FixtureTree::copy();
    unlink($directory.'/'.ARCHIVE_FAMILY);

    importForArchive($directory);

    $unit = Unit::query()->where('key', 'a1.u02')->sole();
    $lessons = Lesson::query()->where('unit_id', $unit->id);
    $lessonIds = $lessons->pluck('id');

    expect($unit->isArchived())->toBeTrue()
        ->and($lessons->count())->toBe(4)
        ->and((clone $lessons)->active()->exists())->toBeFalse()
        ->and(Exercise::query()->whereIn('lesson_id', $lessonIds)->count())->toBe(4)
        ->and(Exercise::query()->whereIn('lesson_id', $lessonIds)->active()->exists())->toBeFalse()
        ->and(Unit::query()->where('key', 'a1.u01')->sole()->isArchived())->toBeFalse();
});

test('a vocabulary item of a removed lesson is archived with it', function () {
    importForArchive();
    $directory = FixtureTree::copy();

    FixtureTree::edit($directory, ARCHIVE_GREETINGS, function (array $unit) {
        array_splice($unit['lessons'], 2, 1);

        return $unit;
    });

    importForArchive($directory);

    expect(Lesson::query()->where('key', 'a1.u01.l03')->sole()->isArchived())->toBeTrue()
        ->and(VocabularyItem::query()->where('key', 'a1.u01.l03.v01')->sole()->isArchived())->toBeTrue()
        ->and(VocabularyItem::query()->where('key', 'a1.u01.l02.v01')->sole()->isArchived())->toBeFalse();
});

test('an archived exercise that returns to its file is restored and updated', function () {
    importForArchive();
    $id = Exercise::query()->where('key', 'a1.u01.l01.ex03')->value('id');
    $directory = FixtureTree::copy();
    withoutThirdExercise($directory);
    importForArchive($directory);

    $returning = FixtureTree::copy();
    FixtureTree::edit($returning, ARCHIVE_GREETINGS, function (array $unit) {
        $unit['lessons'][0]['exercises'][2]['prompt'] = 'A new prompt.';

        return $unit;
    });

    test()->artisan('content:import', ['--path' => $returning])
        ->expectsTable(ContentImportCommand::SUMMARY_HEADERS, [
            ['levels', 0, 0, 1, 0, 0],
            ['passages', 0, 0, 2, 0, 0],
            ['units', 0, 0, 2, 0, 0],
            ['lessons', 0, 0, 7, 0, 0],
            ['vocabulary_items', 0, 0, 3, 0, 0],
            ['exercises', 0, 0, 8, 0, 1],
            ['exams', 0, 0, 2, 0, 0],
            ['exam_sections', 0, 0, 6, 0, 0],
            ['exam_questions', 0, 0, 9, 0, 0],
        ])
        ->assertExitCode(0);

    expect(Exercise::query()->where('key', 'a1.u01.l01.ex03')->sole())
        ->id->toBe($id)
        ->archived_at->toBeNull()
        ->prompt->toBe('A new prompt.')
        ->position->toBe(3);
});

test('an archived exercise goes after its active siblings', function () {
    importForArchive();
    $directory = FixtureTree::copy();

    FixtureTree::edit($directory, ARCHIVE_GREETINGS, function (array $unit) {
        array_splice($unit['lessons'][0]['exercises'], 0, 1);

        return $unit;
    });

    importForArchive($directory);

    $lessonId = Lesson::query()->where('key', 'a1.u01.l01')->value('id');

    expect(Exercise::query()->where('lesson_id', $lessonId)->orderBy('position')->pluck('position', 'key')->all())->toBe([
        'a1.u01.l01.ex02' => 1,
        'a1.u01.l01.ex03' => 2,
        'a1.u01.l01.ex01' => 3,
    ]);
});

test('a placement exam under a new key replaces the old one', function () {
    importForArchive();
    $directory = FixtureTree::copy();

    FixtureTree::edit($directory, 'exams/placement.json', fn (array $exam) => ['key' => 'placement.v2'] + $exam);

    test()->artisan('content:import', ['--path' => $directory])
        ->expectsTable(ContentImportCommand::SUMMARY_HEADERS, [
            ['levels', 0, 0, 1, 0, 0],
            ['passages', 0, 0, 2, 0, 0],
            ['units', 0, 0, 2, 0, 0],
            ['lessons', 0, 0, 7, 0, 0],
            ['vocabulary_items', 0, 0, 3, 0, 0],
            ['exercises', 0, 0, 9, 0, 0],
            ['exams', 1, 0, 1, 1, 0],
            ['exam_sections', 0, 1, 5, 0, 0],
            ['exam_questions', 0, 0, 9, 0, 0],
        ])
        ->assertExitCode(0);

    expect(Exam::query()->where('key', 'placement')->sole()->isArchived())->toBeTrue()
        ->and(Exam::query()->active()->where('kind', 'placement')->sole()->key)->toBe('placement.v2');
});

test('a dry run reports the plan and writes nothing', function () {
    importForArchive();
    $directory = FixtureTree::copy();
    withoutThirdExercise($directory);

    FixtureTree::edit($directory, ARCHIVE_FAMILY, function (array $unit) {
        $unit['lessons'][] = ['key' => 'a1.u02.l05', 'type' => 'reading', 'title' => 'A new lesson'];

        return $unit;
    });

    $before = contentSnapshot();

    test()->artisan('content:import', ['--path' => $directory, '--dry-run' => true])
        ->expectsTable(ContentImportCommand::SUMMARY_HEADERS, [
            ['levels', 0, 0, 1, 0, 0],
            ['passages', 0, 0, 2, 0, 0],
            ['units', 0, 0, 2, 0, 0],
            ['lessons', 1, 0, 7, 0, 0],
            ['vocabulary_items', 0, 0, 3, 0, 0],
            ['exercises', 0, 0, 8, 1, 0],
            ['exams', 0, 0, 2, 0, 0],
            ['exam_sections', 0, 0, 6, 0, 0],
            ['exam_questions', 0, 0, 9, 0, 0],
        ])
        ->expectsOutputToContain('nothing was written')
        ->assertExitCode(0);

    expect(contentSnapshot())->toBe($before);
});

test('a dry run on an empty database creates nothing', function () {
    test()->artisan('content:import', ['--path' => FixtureTree::fixtures(), '--dry-run' => true])
        ->expectsOutputToContain('Dry run of 7 files: nothing was written.')
        ->assertExitCode(0);

    foreach (ContentTables::order() as $table) {
        expect(DB::table($table)->count())->toBe(0, $table);
    }
});

test('a missing audio file is a warning and the path is still stored', function () {
    Storage::fake('content-audio');
    Storage::disk('content-audio')->put('a1/listening-01.mp3', 'audio');

    test()->artisan('content:import', ['--path' => FixtureTree::fixtures()])
        ->expectsOutputToContain('warning '.ARCHIVE_FAMILY.' /lessons/1/exercises/0/audio_path: The audio file `a1/phone-call.mp3` was not found')
        ->doesntExpectOutputToContain('a1/listening-01.mp3')
        ->expectsOutputToContain('Content imported from 7 files.')
        ->assertExitCode(0);

    expect(Exercise::query()->where('key', 'a1.u02.l02.ex01')->value('audio_path'))->toBe('a1/phone-call.mp3');
});
