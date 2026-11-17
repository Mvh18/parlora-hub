<?php

use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\Lesson;
use App\Models\Level;
use App\Models\Passage;
use App\Models\Unit;
use App\Models\User;
use App\Models\VocabularyItem;
use App\Policies\ContentPolicy;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

/** Abilities a qualified admin has on every content row. */
const ALLOWED_RECORD_ABILITIES = ['view', 'update', 'archive', 'restore'];

const ALLOWED_CLASS_ABILITIES = ['viewAny', 'reorder'];

/** Abilities nobody has: the panel retouches, it never creates or deletes. */
const DENIED_RECORD_ABILITIES = ['delete', 'forceDelete', 'replicate'];

const DENIED_CLASS_ABILITIES = ['create', 'deleteAny', 'forceDeleteAny', 'restoreAny'];

dataset('content models', [
    'level' => Level::class,
    'unit' => Unit::class,
    'lesson' => Lesson::class,
    'vocabulary item' => VocabularyItem::class,
    'passage' => Passage::class,
    'exercise' => Exercise::class,
    'exam' => Exam::class,
    'exam section' => ExamSection::class,
    'exam question' => ExamQuestion::class,
]);

/**
 * @param  class-string<Model>  $model
 * @return array<string, bool>
 */
function abilitiesFor(User $user, string $model): array
{
    /** @var Model $record */
    $record = $model::factory()->create();
    $gate = Gate::forUser($user);
    $results = [];

    foreach ([...ALLOWED_RECORD_ABILITIES, ...DENIED_RECORD_ABILITIES] as $ability) {
        $results[$ability] = $gate->allows($ability, $record);
    }

    foreach ([...ALLOWED_CLASS_ABILITIES, ...DENIED_CLASS_ABILITIES] as $ability) {
        $results[$ability] = $gate->allows($ability, $model);
    }

    return $results;
}

test('every content model has a policy built on ContentPolicy', function (string $model) {
    expect(Gate::getPolicyFor($model))->toBeInstanceOf(ContentPolicy::class);
})->with('content models');

test('a qualified admin may view, update, reorder, archive and restore', function (string $model) {
    $admin = User::factory()->admin()->withTwoFactor()->create();

    $abilities = abilitiesFor($admin, $model);

    foreach ([...ALLOWED_RECORD_ABILITIES, ...ALLOWED_CLASS_ABILITIES] as $ability) {
        expect($abilities[$ability])->toBeTrue("{$ability} should be allowed");
    }
})->with('content models');

test('nobody may create or delete content, not even a qualified admin', function (string $model) {
    $admin = User::factory()->admin()->withTwoFactor()->create();

    $abilities = abilitiesFor($admin, $model);

    foreach ([...DENIED_RECORD_ABILITIES, ...DENIED_CLASS_ABILITIES] as $ability) {
        expect($abilities[$ability])->toBeFalse("{$ability} should be denied");
    }
})->with('content models');

test('a learner is denied every ability', function (string $model) {
    expect(abilitiesFor(User::factory()->withTwoFactor()->create(), $model))
        ->each->toBeFalse();
})->with('content models');

test('an admin without two-factor is denied every ability', function (string $model) {
    expect(abilitiesFor(User::factory()->admin()->create(), $model))
        ->each->toBeFalse();
})->with('content models');

test('the panel runs with strict authorization', function () {
    expect(Filament::getPanel('admin')->isAuthorizationStrict())->toBeTrue();
});
