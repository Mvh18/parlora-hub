<?php

use App\Enums\CefrLevel;
use App\Filament\Resources\Levels\LevelResource;
use App\Filament\Resources\Levels\Pages\EditLevel;
use App\Filament\Resources\Levels\Pages\ListLevels;
use App\Filament\Resources\Levels\RelationManagers\UnitsRelationManager;
use App\Filament\Resources\Units\Pages\EditUnit;
use App\Filament\Resources\Units\RelationManagers\LessonsRelationManager;
use App\Filament\Resources\Units\UnitResource;
use App\Models\Lesson;
use App\Models\Level;
use App\Models\Unit;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->admin()->withTwoFactor()->create();
    $this->actingAs($this->admin);
    Filament::setCurrentPanel('admin');
});

function levelRelation(Level $level): array
{
    return ['ownerRecord' => $level, 'pageClass' => EditLevel::class];
}

function unitRelation(Unit $unit): array
{
    return ['ownerRecord' => $unit, 'pageClass' => EditUnit::class];
}

test('the levels list shows the levels in order and searches by key and title', function () {
    $a2 = Level::factory()->code(CefrLevel::A2)->create();
    $a1 = Level::factory()->code(CefrLevel::A1)->create();

    Livewire::test(ListLevels::class)
        ->assertCanSeeTableRecords([$a1, $a2], inOrder: true)
        ->searchTable('a2')
        ->assertCanSeeTableRecords([$a2])
        ->assertCanNotSeeTableRecords([$a1])
        ->searchTable('A1 course')
        ->assertCanSeeTableRecords([$a1])
        ->assertCanNotSeeTableRecords([$a2]);
});

test('the levels list hides archived rows until the filter is cleared', function () {
    $active = Level::factory()->code(CefrLevel::A1)->create();
    $archived = Level::factory()->code(CefrLevel::A2)->create();
    $archived->archive();

    Livewire::test(ListLevels::class)
        ->assertCanSeeTableRecords([$active])
        ->assertCanNotSeeTableRecords([$archived])
        ->removeTableFilter('archived')
        ->assertCanSeeTableRecords([$active, $archived]);
});

test('a level is archived and restored from the list', function () {
    $level = Level::factory()->code(CefrLevel::A1)->create();

    Livewire::test(ListLevels::class)->callAction(TestAction::make('archive')->table($level));
    expect($level->fresh()->isArchived())->toBeTrue();

    Livewire::test(ListLevels::class)
        ->removeTableFilter('archived')
        ->callAction(TestAction::make('restore')->table($level));
    expect($level->fresh()->archived_at)->toBeNull();
});

test('a level title and threshold are edited, its key and code are not', function () {
    $level = Level::factory()->code(CefrLevel::A1)->create();

    Livewire::test(EditLevel::class, ['record' => $level->getRouteKey()])
        ->assertFormSet(['key' => 'a1', 'title' => 'A1 course'])
        ->set('data.title', 'Beginner')
        ->set('data.pass_threshold', 80)
        ->set('data.key', 'hacked')
        ->set('data.code', 'C1')
        ->call('save')
        ->assertHasNoFormErrors();

    $level->refresh();

    expect($level->title)->toBe('Beginner')
        ->and($level->pass_threshold)->toBe(80)
        ->and($level->key)->toBe('a1')
        ->and($level->code)->toBe(CefrLevel::A1);
});

test('a threshold outside 0-100 is a form error', function () {
    $level = Level::factory()->code(CefrLevel::A1)->create();

    Livewire::test(EditLevel::class, ['record' => $level->getRouteKey()])
        ->set('data.pass_threshold', 101)
        ->call('save')
        ->assertHasFormErrors(['pass_threshold']);
});

test('the re-import notice and the row actions are on the level page', function () {
    $level = Level::factory()->code(CefrLevel::A1)->create();

    Livewire::test(EditLevel::class, ['record' => $level->getRouteKey()])
        ->assertSee(__('admin.reimport_notice.heading'))
        ->assertActionVisible('copyAsJson')
        ->assertActionVisible('archive')
        ->assertActionHidden('restore')
        ->assertActionDoesNotExist('delete');
});

test('the units relation lists a level\'s units ordered by position', function () {
    $level = Level::factory()->code(CefrLevel::A1)->create();
    $third = Unit::factory()->for($level)->create(['position' => 3]);
    $first = Unit::factory()->for($level)->create(['position' => 1]);
    $second = Unit::factory()->for($level)->create(['position' => 2]);
    $other = Unit::factory()->for(Level::factory()->code(CefrLevel::B2))->create(['position' => 1]);

    Livewire::test(UnitsRelationManager::class, levelRelation($level))
        ->assertCanSeeTableRecords([$first, $second, $third], inOrder: true)
        ->assertCanNotSeeTableRecords([$other]);
});

test('units are archived, restored and filtered in the relation', function () {
    $level = Level::factory()->code(CefrLevel::A1)->create();
    $unit = Unit::factory()->for($level)->create(['position' => 1]);
    $archived = Unit::factory()->for($level)->create(['position' => 2]);
    $archived->archive();

    Livewire::test(UnitsRelationManager::class, levelRelation($level))
        ->assertCanSeeTableRecords([$unit])
        ->assertCanNotSeeTableRecords([$archived])
        ->removeTableFilter('archived')
        ->assertCanSeeTableRecords([$unit, $archived])
        ->callAction(TestAction::make('archive')->table($unit))
        ->callAction(TestAction::make('restore')->table($archived));

    expect($unit->fresh()->isArchived())->toBeTrue()
        ->and($archived->fresh()->isArchived())->toBeFalse();
});

test('the units of a level are reordered', function () {
    $level = Level::factory()->code(CefrLevel::A1)->create();
    $one = Unit::factory()->for($level)->create(['position' => 1]);
    $two = Unit::factory()->for($level)->create(['position' => 2]);
    $three = Unit::factory()->for($level)->create(['position' => 3]);

    Livewire::test(UnitsRelationManager::class, levelRelation($level))
        ->call('reorderTable', [$three->id, $one->id, $two->id]);

    expect($three->fresh()->position)->toBe(1)
        ->and($one->fresh()->position)->toBe(2)
        ->and($two->fresh()->position)->toBe(3);
});

test('a learner cannot reorder through the relation', function () {
    $level = Level::factory()->code(CefrLevel::A1)->create();
    $one = Unit::factory()->for($level)->create(['position' => 1]);
    $two = Unit::factory()->for($level)->create(['position' => 2]);

    // The page was opened by a qualified admin; the next request comes from a
    // user who no longer qualifies (a Livewire update is a separate request).
    $component = Livewire::test(UnitsRelationManager::class, levelRelation($level));
    $this->actingAs(User::factory()->create());

    $component->call('reorderTable', [$two->id, $one->id])->assertForbidden();

    expect($one->fresh()->position)->toBe(1);
});

test('a unit title is edited and its key is not', function () {
    $unit = Unit::factory()->create(['key' => 'a1.u01', 'title' => 'Greetings']);

    Livewire::test(EditUnit::class, ['record' => $unit->getRouteKey()])
        ->set('data.title', 'Hello')
        ->set('data.key', 'hacked')
        ->call('save')
        ->assertHasNoFormErrors();

    expect($unit->fresh()->title)->toBe('Hello')
        ->and($unit->fresh()->key)->toBe('a1.u01');
});

test('a unit moves to another level at a free position', function () {
    $unit = Unit::factory()->for(Level::factory()->code(CefrLevel::A1))->create(['position' => 1]);
    $target = Level::factory()->code(CefrLevel::B1)->create();

    Livewire::test(EditUnit::class, ['record' => $unit->getRouteKey()])
        ->set('data.level_id', $target->id)
        ->call('save')
        ->assertHasNoFormErrors();

    expect($unit->fresh()->level_id)->toBe($target->id);
});

test('a unit moved onto a taken position is a form error, not a database error', function () {
    $target = Level::factory()->code(CefrLevel::B1)->create();
    Unit::factory()->for($target)->create(['position' => 1]);
    $unit = Unit::factory()->for(Level::factory()->code(CefrLevel::A1))->create(['position' => 1]);
    $original = $unit->level_id;

    Livewire::test(EditUnit::class, ['record' => $unit->getRouteKey()])
        ->set('data.level_id', $target->id)
        ->call('save')
        ->assertHasFormErrors(['position']);

    expect($unit->fresh()->level_id)->toBe($original);
});

test('the lessons relation lists a unit\'s lessons ordered by position', function () {
    $unit = Unit::factory()->create();
    $third = Lesson::factory()->for($unit)->create(['position' => 3]);
    $first = Lesson::factory()->for($unit)->create(['position' => 1]);
    $second = Lesson::factory()->for($unit)->create(['position' => 2]);

    Livewire::test(LessonsRelationManager::class, unitRelation($unit))
        ->assertCanSeeTableRecords([$first, $second, $third], inOrder: true);
});

test('the third lesson is moved to the first place', function () {
    $unit = Unit::factory()->create();
    $one = Lesson::factory()->for($unit)->create(['position' => 1]);
    $two = Lesson::factory()->for($unit)->create(['position' => 2]);
    $three = Lesson::factory()->for($unit)->create(['position' => 3]);

    Livewire::test(LessonsRelationManager::class, unitRelation($unit))
        ->call('reorderTable', [$three->id, $one->id, $two->id]);

    expect($three->fresh()->position)->toBe(1)
        ->and($one->fresh()->position)->toBe(2)
        ->and($two->fresh()->position)->toBe(3);
});

test('no create, delete or bulk action exists on a level, its units or the unit lessons', function () {
    $level = Level::factory()->code(CefrLevel::A1)->create();
    $unit = Unit::factory()->for($level)->create();
    Lesson::factory()->for($unit)->create();

    Livewire::test(ListLevels::class)
        ->assertActionDoesNotExist('create')
        ->assertTableActionDoesNotExist('delete')
        ->assertTableBulkActionDoesNotExist('delete');

    foreach ([[UnitsRelationManager::class, levelRelation($level)], [LessonsRelationManager::class, unitRelation($unit)]] as [$manager, $props]) {
        Livewire::test($manager, $props)
            ->assertActionDoesNotExist('create')
            ->assertTableActionDoesNotExist('delete')
            ->assertTableBulkActionDoesNotExist('delete');
    }

    expect(LevelResource::canCreate())->toBeFalse()
        ->and(UnitResource::canCreate())->toBeFalse()
        ->and(Gate::forUser($this->admin)->denies('create', Unit::class))->toBeTrue()
        ->and(Gate::forUser($this->admin)->denies('delete', $unit))->toBeTrue();
});

test('the navigation lists levels and hides the deeper resources', function () {
    expect(LevelResource::shouldRegisterNavigation())->toBeTrue()
        ->and(UnitResource::shouldRegisterNavigation())->toBeFalse();
});

test('a learner is denied every syllabus page', function () {
    $level = Level::factory()->code(CefrLevel::A1)->create();
    $unit = Unit::factory()->for($level)->create();

    $this->actingAs(User::factory()->create());

    $this->get(LevelResource::getUrl('index'))->assertForbidden();
    $this->get(LevelResource::getUrl('edit', ['record' => $level]))->assertForbidden();
    $this->get(UnitResource::getUrl('edit', ['record' => $unit]))->assertForbidden();
});

test('an admin opens the level and unit pages', function () {
    $level = Level::factory()->code(CefrLevel::A1)->create();
    $unit = Unit::factory()->for($level)->create();

    $this->get(LevelResource::getUrl('index'))->assertOk();
    $this->get(LevelResource::getUrl('edit', ['record' => $level]))->assertOk();
    $this->get(UnitResource::getUrl('edit', ['record' => $unit]))->assertOk();
});
