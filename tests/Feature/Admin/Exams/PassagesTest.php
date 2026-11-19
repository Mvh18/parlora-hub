<?php

use App\Filament\Resources\Passages\Pages\EditPassage;
use App\Filament\Resources\Passages\Pages\ListPassages;
use App\Filament\Resources\Passages\PassageResource;
use App\Filament\Resources\Passages\RelationManagers\ExercisesRelationManager;
use App\Models\Exercise;
use App\Models\Passage;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
    Filament::setCurrentPanel('admin');
});

function passagePage(Passage $passage)
{
    return Livewire::test(EditPassage::class, ['record' => $passage->getRouteKey()]);
}

test('the passages list searches by key and title and shows how many rows use each passage', function () {
    $shared = Passage::factory()->create(['key' => 'a1.p01', 'title' => 'A day at the market']);
    $other = Passage::factory()->create(['key' => 'a1.p02', 'title' => 'Weekend plans']);
    Exercise::factory()->count(2)->create(['passage_id' => $shared->id]);

    Livewire::test(ListPassages::class)
        ->assertCanSeeTableRecords([$shared, $other])
        ->assertTableColumnStateSet('exercises_count', 2, $shared)
        ->assertTableColumnStateSet('exercises_count', 0, $other)
        ->searchTable('market')
        ->assertCanSeeTableRecords([$shared])
        ->assertCanNotSeeTableRecords([$other])
        ->searchTable('a1.p02')
        ->assertCanSeeTableRecords([$other])
        ->assertCanNotSeeTableRecords([$shared]);
});

test('the passages list hides archived passages until the filter is cleared', function () {
    $active = Passage::factory()->create();
    $archived = Passage::factory()->create();
    $archived->archive();

    Livewire::test(ListPassages::class)
        ->assertCanSeeTableRecords([$active])
        ->assertCanNotSeeTableRecords([$archived])
        ->removeTableFilter('archived')
        ->assertCanSeeTableRecords([$active, $archived]);
});

test('a passage body, transcript and audio path are edited', function () {
    $passage = Passage::factory()->listening()->create(['transcript' => 'Hello there.']);

    passagePage($passage)
        ->assertFormSet(['transcript' => 'Hello there.'])
        ->set('data.title', 'Greetings')
        ->set('data.body', '## Notes')
        ->set('data.transcript', 'Hello, how are you?')
        ->set('data.audio_path', 'a1/greetings.mp3')
        ->call('save')
        ->assertHasNoFormErrors();

    $passage->refresh();

    expect($passage->title)->toBe('Greetings')
        ->and($passage->body)->toBe('## Notes')
        ->and($passage->transcript)->toBe('Hello, how are you?')
        ->and($passage->audio_path)->toBe('a1/greetings.mp3');
});

test('saving a passage without touching it keeps its transcript', function () {
    $passage = Passage::factory()->listening()->create(['transcript' => 'Keep me.']);

    passagePage($passage)->call('save')->assertHasNoFormErrors();

    expect($passage->fresh()->transcript)->toBe('Keep me.');
});

test('an unsafe audio path is rejected', function () {
    $passage = Passage::factory()->create();

    passagePage($passage)
        ->set('data.audio_path', '../escape.mp3')
        ->call('save')
        ->assertHasFormErrors(['audio_path']);

    expect($passage->fresh()->audio_path)->toBeNull();
});

test('a passage key is not editable', function () {
    $passage = Passage::factory()->create(['key' => 'a1.p01']);

    passagePage($passage)
        ->set('data.key', 'hacked')
        ->call('save')
        ->assertHasNoFormErrors();

    expect($passage->fresh()->key)->toBe('a1.p01');
});

test('a passage is archived and restored from its page', function () {
    $passage = Passage::factory()->create();

    passagePage($passage)->callAction('archive');
    expect($passage->fresh()->isArchived())->toBeTrue();

    passagePage($passage->fresh())->callAction('restore');
    expect($passage->fresh()->isArchived())->toBeFalse();
});

test('the passage page shows the notice and copies the row by key', function () {
    $passage = Passage::factory()->listening()->create(['transcript' => 'A hidden script.']);

    passagePage($passage)
        ->assertSee(__('admin.reimport_notice.heading'))
        ->mountAction('copyAsJson')
        ->assertMountedActionModalSee([$passage->key, 'A hidden script.'])
        ->assertMountedActionModalDontSee(['"id"', 'created_at']);
});

test('the exercises that use a passage are listed read-only', function () {
    $passage = Passage::factory()->create();
    $used = Exercise::factory()->create(['passage_id' => $passage->id]);
    $unrelated = Exercise::factory()->create();

    Livewire::test(ExercisesRelationManager::class, ['ownerRecord' => $passage, 'pageClass' => EditPassage::class])
        ->assertCanSeeTableRecords([$used])
        ->assertCanNotSeeTableRecords([$unrelated])
        ->assertTableActionExists('edit')
        ->assertTableActionDoesNotExist('archive')
        ->assertTableActionDoesNotExist('restore')
        ->assertTableActionDoesNotExist('delete')
        ->assertTableBulkActionDoesNotExist('delete');
});

test('a passage\'s exercise list cannot be reordered', function () {
    $passage = Passage::factory()->create();
    $one = Exercise::factory()->create(['passage_id' => $passage->id, 'position' => 1]);
    $two = Exercise::factory()->create(['passage_id' => $passage->id, 'position' => 2]);

    Livewire::test(ExercisesRelationManager::class, ['ownerRecord' => $passage, 'pageClass' => EditPassage::class])
        ->call('reorderTable', [$two->id, $one->id]);

    expect($one->fresh()->position)->toBe(1)
        ->and($two->fresh()->position)->toBe(2);
});

test('no create or delete is offered for passages', function () {
    Passage::factory()->create();

    Livewire::test(ListPassages::class)
        ->assertActionDoesNotExist('create')
        ->assertTableActionDoesNotExist('delete')
        ->assertTableBulkActionDoesNotExist('delete');

    expect(PassageResource::canCreate())->toBeFalse();
});

test('a learner is denied the passage pages', function () {
    $passage = Passage::factory()->create();

    $this->actingAs(User::factory()->create());

    $this->get(PassageResource::getUrl('index'))->assertForbidden();
    $this->get(PassageResource::getUrl('edit', ['record' => $passage]))->assertForbidden();
});

test('an admin opens the passage pages', function () {
    $passage = Passage::factory()->create();

    $this->get(PassageResource::getUrl('index'))->assertOk();
    $this->get(PassageResource::getUrl('edit', ['record' => $passage]))->assertOk();
});
