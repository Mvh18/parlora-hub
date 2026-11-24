<?php

use App\Filament\Support\ContentJson;
use App\Filament\Support\ReimportNotice;
use App\Models\Exercise;
use App\Models\Lesson;
use App\Models\User;
use Livewire\Livewire;
use Tests\Support\Admin\RecordActions;
use Tests\Support\Admin\RecordForm;

function qualified(): User
{
    return User::factory()->admin()->withTwoFactor()->create();
}

test('an admin archives an exercise', function () {
    $exercise = Exercise::factory()->create();

    Livewire::actingAs(qualified())
        ->test(RecordActions::class, ['record' => $exercise])
        ->callAction('archive');

    expect($exercise->fresh()->isArchived())->toBeTrue()
        ->and(Exercise::active()->whereKey($exercise->id)->exists())->toBeFalse();
});

test('an admin restores an archived exercise', function () {
    $exercise = Exercise::factory()->create();
    $exercise->archive();

    Livewire::actingAs(qualified())
        ->test(RecordActions::class, ['record' => $exercise->fresh()])
        ->callAction('restore');

    expect($exercise->fresh()->archived_at)->toBeNull();
});

test('archive is offered only on active rows and restore only on archived ones', function () {
    $active = Lesson::factory()->create();
    $archived = Lesson::factory()->create();
    $archived->archive();

    Livewire::actingAs(qualified())
        ->test(RecordActions::class, ['record' => $active])
        ->assertActionVisible('archive')
        ->assertActionHidden('restore');

    Livewire::actingAs(qualified())
        ->test(RecordActions::class, ['record' => $archived->fresh()])
        ->assertActionHidden('archive')
        ->assertActionVisible('restore');
});

test('a learner cannot archive, restore or copy through Livewire', function () {
    $exercise = Exercise::factory()->create();
    $archived = Exercise::factory()->create();
    $archived->archive();
    $learner = User::factory()->withTwoFactor()->create();

    Livewire::actingAs($learner)
        ->test(RecordActions::class, ['record' => $exercise])
        ->assertActionHidden('archive')
        ->assertActionHidden('copyAsJson')
        ->call('mountAction', 'archive')
        ->call('callMountedAction');

    Livewire::actingAs($learner)
        ->test(RecordActions::class, ['record' => $archived->fresh()])
        ->call('mountAction', 'restore')
        ->call('callMountedAction');

    expect($exercise->fresh()->isArchived())->toBeFalse()
        ->and($archived->fresh()->isArchived())->toBeTrue();
});

test('an admin without two-factor cannot archive', function () {
    $exercise = Exercise::factory()->create();

    Livewire::actingAs(User::factory()->admin()->create())
        ->test(RecordActions::class, ['record' => $exercise])
        ->call('mountAction', 'archive')
        ->call('callMountedAction');

    expect($exercise->fresh()->isArchived())->toBeFalse();
});

test('copy as JSON shows the content-file fragment', function () {
    $exercise = Exercise::factory()->create();

    Livewire::actingAs(qualified())
        ->test(RecordActions::class, ['record' => $exercise])
        ->mountAction('copyAsJson')
        ->assertActionMounted('copyAsJson')
        ->assertMountedActionModalSee([$exercise->key, $exercise->prompt])
        ->assertMountedActionModalDontSee(['lesson_id', $exercise->lesson->key, '"position"']);

    expect(ContentJson::for($exercise->fresh()))->toHaveKey('key', $exercise->key)->not->toHaveKey('lesson');
});

test('the re-import notice is shown on a form', function () {
    RecordForm::using(fn () => [ReimportNotice::make()]);

    Livewire::actingAs(qualified())
        ->test(RecordForm::class, ['record' => Lesson::factory()->create()])
        ->assertSee(__('admin.reimport_notice.heading'))
        ->assertSee(__('admin.reimport_notice.body'));
});

test('the raw Livewire calls the denial tests use do archive for an admin', function () {
    $exercise = Exercise::factory()->create();

    Livewire::actingAs(qualified())
        ->test(RecordActions::class, ['record' => $exercise])
        ->call('mountAction', 'archive')
        ->call('callMountedAction');

    expect($exercise->fresh()->isArchived())->toBeTrue();
});
