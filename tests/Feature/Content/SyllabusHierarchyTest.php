<?php

use App\Enums\CefrLevel;
use App\Enums\LessonType;
use App\Enums\VocabularyKind;
use App\Models\Lesson;
use App\Models\Level;
use App\Models\Unit;
use App\Models\VocabularyItem;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** A raw lesson row, so the database constraints are exercised without the model's casts. */
function rawLesson(Unit $unit, array $overrides = []): array
{
    return [
        'key' => 'raw.lesson',
        'unit_id' => $unit->id,
        'type' => 'grammar',
        'title' => 'Raw',
        'position' => 1,
        'created_at' => now(),
        'updated_at' => now(),
        ...$overrides,
    ];
}

describe('hierarchy', function () {
    test('children are ordered by position', function () {
        $unit = Unit::factory()->create();
        Lesson::factory()->for($unit)->create(['position' => 3]);
        Lesson::factory()->for($unit)->create(['position' => 1]);
        Lesson::factory()->for($unit)->create(['position' => 2]);

        expect($unit->lessons->pluck('position')->all())->toBe([1, 2, 3]);
    });

    test('levels list their units and lessons list their vocabulary in order', function () {
        $level = Level::factory()->create();
        Unit::factory()->for($level)->create(['position' => 2]);
        Unit::factory()->for($level)->create(['position' => 1]);

        $lesson = Lesson::factory()->create();
        VocabularyItem::factory()->for($lesson)->create(['position' => 2]);
        VocabularyItem::factory()->for($lesson)->create(['position' => 1]);

        expect($level->units->pluck('position')->all())->toBe([1, 2])
            ->and($lesson->vocabularyItems->pluck('position')->all())->toBe([1, 2]);
    });

    test('every child knows its parent', function () {
        $item = VocabularyItem::factory()->create();

        expect($item->lesson->unit->level)->toBeInstanceOf(Level::class);
    });

    test('a duplicate position inside the same parent is rejected', function () {
        $unit = Unit::factory()->create();
        Lesson::factory()->for($unit)->create(['position' => 1]);

        expect(fn () => Lesson::factory()->for($unit)->create(['position' => 1]))
            ->toThrow(QueryException::class);
    });

    test('the same position in another parent is fine', function () {
        Lesson::factory()->create(['position' => 1]);
        Lesson::factory()->create(['position' => 1]);

        expect(Lesson::count())->toBe(2);
    });

    test('a level code can only be used once', function () {
        Level::factory()->code(CefrLevel::A1)->create();

        expect(fn () => Level::factory()->code(CefrLevel::A1)->create())->toThrow(QueryException::class);
    });

    test('a level pass threshold must be between 0 and 100', function (int $threshold) {
        try {
            Level::factory()->create(['pass_threshold' => $threshold]);
            $this->fail('Expected a ValidationException.');
        } catch (ValidationException $e) {
            expect($e->errors())->toHaveKey('pass_threshold');
        }

        expect(Level::count())->toBe(0);
    })->with([-1, 101, 255]);

    test('pass thresholds at the limits are accepted', function () {
        expect(Level::factory()->create(['pass_threshold' => 0])->pass_threshold)->toBe(0)
            ->and(Level::factory()->create(['pass_threshold' => 100])->pass_threshold)->toBe(100);
    });
});

describe('CEFR levels', function () {
    test('C2 and unknown codes are rejected and nothing is written', function (string $code) {
        expect(fn () => Level::factory()->create(['code' => $code]))->toThrow(ValueError::class);

        expect(Level::count())->toBe(0);
    })->with(['C2', 'D1']);

    test('the database rejects an unknown code even without the model', function () {
        expect(fn () => DB::table('levels')->insert([
            'key' => 'c2', 'code' => 'C2', 'title' => 'Mastery', 'position' => 6, 'pass_threshold' => 70,
            'created_at' => now(), 'updated_at' => now(),
        ]))->toThrow(QueryException::class);
    });

    test('the code is read back as an enum', function () {
        $level = Level::factory()->code(CefrLevel::B1)->create()->fresh();

        expect($level->code)->toBe(CefrLevel::B1)
            ->and($level->position)->toBe(3);
    });
});

describe('lesson and vocabulary types', function () {
    test('a skills lesson is stored and read back as an enum', function () {
        $lesson = Lesson::factory()->create(['type' => LessonType::Reading])->fresh();

        expect($lesson->type)->toBe(LessonType::Reading);
    });

    test('every lesson type can be stored', function (LessonType $type) {
        expect(Lesson::factory()->create(['type' => $type])->fresh()->type)->toBe($type);
    })->with(LessonType::cases());

    test('the database rejects an unknown lesson type', function () {
        $unit = Unit::factory()->create();

        expect(fn () => DB::table('lessons')->insert(rawLesson($unit, ['type' => 'poetry'])))
            ->toThrow(QueryException::class);
    });

    test('the database rejects an unknown vocabulary kind', function () {
        $lesson = Lesson::factory()->create();

        expect(fn () => DB::table('vocabulary_items')->insert([
            'key' => 'raw.vocab', 'lesson_id' => $lesson->id, 'term' => 'x', 'kind' => 'idiom',
            'meaning' => json_encode(['en' => 'x']), 'position' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]))->toThrow(QueryException::class);
    });

    test('vocabulary kinds are read back as enums', function () {
        $item = VocabularyItem::factory()->create(['kind' => VocabularyKind::PhrasalVerb])->fresh();

        expect($item->kind)->toBe(VocabularyKind::PhrasalVerb);
    });
});

describe('stable keys', function () {
    test('a duplicate key is rejected', function () {
        Lesson::factory()->create(['key' => 'a1.u01.l01']);

        expect(fn () => Lesson::factory()->create(['key' => 'a1.u01.l01']))->toThrow(QueryException::class);
    });

    test('a malformed key is rejected on every content model', function (string $model) {
        try {
            $model::factory()->create(['key' => 'A1 Unit 1']);
            $this->fail('Expected a ValidationException.');
        } catch (ValidationException $e) {
            expect($e->errors())->toHaveKey('key');
        }
    })->with([Level::class, Unit::class, Lesson::class, VocabularyItem::class]);

    test('a key can never change', function () {
        $lesson = Lesson::factory()->create(['key' => 'a1.u01.l01']);

        $lesson->key = 'a1.u01.l02';

        expect(fn () => $lesson->save())->toThrow(LogicException::class);
        expect($lesson->fresh()->key)->toBe('a1.u01.l01');
    });
});

describe('archival and deletion', function () {
    test('a level with units cannot be deleted', function () {
        $unit = Unit::factory()->create();

        expect(fn () => $unit->level->delete())->toThrow(QueryException::class);
    });

    test('a unit with lessons cannot be deleted', function () {
        $lesson = Lesson::factory()->create();

        expect(fn () => $lesson->unit->delete())->toThrow(QueryException::class);
    });

    test('a lesson with vocabulary cannot be deleted', function () {
        $item = VocabularyItem::factory()->create();

        expect(fn () => $item->lesson->delete())->toThrow(QueryException::class);
    });

    test('archived rows leave the active scope but stay in the relation', function () {
        $unit = Unit::factory()->create();
        Lesson::factory()->for($unit)->create(['position' => 1]);
        Lesson::factory()->for($unit)->create(['position' => 2]);
        Lesson::factory()->for($unit)->create(['position' => 3])->archive();

        expect($unit->lessons()->active()->count())->toBe(2)
            ->and($unit->lessons()->count())->toBe(3);
    });

    test('an archived row can be restored', function () {
        $lesson = Lesson::factory()->create();
        $lesson->archive();

        $lesson->restore();

        expect($lesson->fresh()->archived_at)->toBeNull()
            ->and(Lesson::query()->active()->count())->toBe(1);
    });

    test('archived_at cannot be mass assigned', function () {
        // Factories bypass $fillable, so go through create() like a request would.
        $lesson = Lesson::create([...Lesson::factory()->raw(), 'archived_at' => now()]);

        expect($lesson->fresh()->archived_at)->toBeNull();
    });
});

describe('factories', function () {
    test('they build valid rows with their parents', function () {
        $item = VocabularyItem::factory()->create();

        expect($item->exists)->toBeTrue()
            ->and($item->lesson->exists)->toBeTrue()
            ->and($item->lesson->unit->exists)->toBeTrue()
            ->and($item->lesson->unit->level->exists)->toBeTrue();
    });

    test('they can build five distinct levels', function () {
        Level::factory()->count(5)->create();

        expect(Level::pluck('code')->unique())->toHaveCount(5);
    });
});
