<?php

use App\Casts\ExercisePayloadCast;
use App\Data\Exercises\FillGapContent;
use App\Data\Exercises\MultipleChoiceContent;
use App\Data\Exercises\MultipleChoiceSolution;
use App\Enums\ExerciseType;
use App\Exceptions\InvalidExercisePayload;
use App\Models\Exercise;
use App\Models\Lesson;
use App\Models\Passage;
use Database\Factories\Support\ExerciseSamples;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

describe('payload casting', function () {
    test('a factory exercise of every type saves and passes validation', function (ExerciseType $type) {
        $exercise = Exercise::factory()->ofType($type)->create();

        expect($exercise->fresh()->type)->toBe($type)
            ->and($exercise->fresh()->content)->toBeInstanceOf($type->contentClass())
            ->and($exercise->fresh()->solution)->toBeInstanceOf($type->solutionClass());
    })->with(ExerciseType::cases());

    test('round trip keeps the typed payloads', function () {
        $exercise = Exercise::factory()->multipleChoice()->create()->fresh();

        expect($exercise->content)->toBeInstanceOf(MultipleChoiceContent::class)
            ->and($exercise->content->options)->toHaveCount(3)
            ->and($exercise->solution)->toBeInstanceOf(MultipleChoiceSolution::class)
            ->and($exercise->solution->correct)->toBe(['b']);
    });

    test('data objects can be assigned instead of arrays', function () {
        $exercise = Exercise::factory()->fillGap()->create();

        $exercise->content = FillGapContent::from(['text' => 'We {{g1}} tea.']);
        $exercise->solution = ['answers' => ['g1' => ['drink']]];
        $exercise->save();

        expect($exercise->fresh()->content)->toBeInstanceOf(FillGapContent::class)
            ->and($exercise->fresh()->content->text)->toBe('We {{g1}} tea.');
    });

    test('no php class names reach the database', function () {
        // One lesson for all: every new lesson would create a level, and there are only five CEFR codes.
        $lesson = Lesson::factory()->create();

        foreach (ExerciseType::cases() as $type) {
            $exercise = Exercise::factory()->for($lesson)->ofType($type)->create();
            $row = DB::table('exercises')->where('id', $exercise->id)->first();

            expect($row->content.$row->solution)->not->toContain('App\\');
        }
    });

    test('payloads are stored as plain json objects', function () {
        $exercise = Exercise::factory()->fillGap()->create();
        $row = DB::table('exercises')->where('id', $exercise->id)->first();

        expect(json_decode($row->content, true))->toBe(ExerciseSamples::content(ExerciseType::FillGap));
    });

    test('an unknown payload side is rejected', function () {
        expect(fn () => new ExercisePayloadCast('answer'))->toThrow(InvalidArgumentException::class);
    });

    test('a payload that is neither array nor data object is rejected', function () {
        expect(fn () => Exercise::factory()->make(['content' => 'text']))->toThrow(InvalidArgumentException::class);
    });
});

describe('validation on save', function () {
    test('an invalid payload names its path and writes nothing', function () {
        $solution = ExerciseSamples::solution(ExerciseType::MultipleChoice);
        $solution['correct'] = ['d'];

        try {
            Exercise::factory()->create(['solution' => $solution]);
            $this->fail('Expected an exception.');
        } catch (InvalidExercisePayload $exception) {
            expect(array_keys($exception->errors()))->toContain('solution.correct.0');
        }

        expect(Exercise::query()->count())->toBe(0);
    });

    test('changing the type without a new payload fails and keeps the stored row', function () {
        $exercise = Exercise::factory()->multipleChoice()->create();
        $exercise->type = ExerciseType::Matching;

        expect(fn () => $exercise->save())->toThrow(InvalidExercisePayload::class);
        expect(Exercise::query()->find($exercise->id)->type)->toBe(ExerciseType::MultipleChoice);
    });

    test('changing the type together with a valid payload succeeds', function () {
        $exercise = Exercise::factory()->multipleChoice()->create();

        $exercise->update([
            'type' => ExerciseType::Transformation,
            'content' => ExerciseSamples::content(ExerciseType::Transformation),
            'solution' => ExerciseSamples::solution(ExerciseType::Transformation),
        ]);

        expect($exercise->fresh()->type)->toBe(ExerciseType::Transformation)
            ->and($exercise->fresh()->content)->toBeInstanceOf(ExerciseType::Transformation->contentClass());
    });

    test('a dictation exercise is accepted without audio', function () {
        $exercise = Exercise::factory()->dictation()->create();

        expect($exercise->fresh()->audio_path)->toBeNull();
    });

    test('a bad audio path is rejected', function () {
        expect(fn () => Exercise::factory()->dictation()->create(['audio_path' => '../../.env']))
            ->toThrow(ValidationException::class, 'audio_path');

        expect(Exercise::query()->count())->toBe(0);
    });

    test('a valid audio path is stored unchanged', function () {
        $exercise = Exercise::factory()->dictation()->create(['audio_path' => 'a1/u03/dictation-01.mp3']);

        expect($exercise->fresh()->audio_path)->toBe('a1/u03/dictation-01.mp3');
    });
});

describe('identity, translations and ordering', function () {
    test('duplicate keys are rejected', function () {
        Exercise::factory()->create(['key' => 'a1.u01.l01.ex01']);

        expect(fn () => Exercise::factory()->create(['key' => 'a1.u01.l01.ex01']))
            ->toThrow(QueryException::class);
    });

    test('malformed keys are rejected', function () {
        expect(fn () => Exercise::factory()->create(['key' => 'A1 Unit 1']))
            ->toThrow(ValidationException::class, 'key');
    });

    test('the key is immutable', function () {
        $exercise = Exercise::factory()->create();
        $exercise->key = 'changed.key';

        expect(fn () => $exercise->save())->toThrow(LogicException::class);
    });

    test('the explanation is optional and falls back to English', function () {
        $without = Exercise::factory()->create();
        $with = Exercise::factory()->create(['explanation' => ['en' => 'Use "is" with he, she, it.']]);

        expect($without->fresh()->getRawOriginal('explanation'))->toBeNull()
            ->and($with->fresh()->getTranslation('explanation', 'es'))->toBe('Use "is" with he, she, it.');
    });

    test('an explanation needs English', function () {
        expect(fn () => Exercise::factory()->create(['explanation' => ['es' => 'Usa "is".']]))
            ->toThrow(ValidationException::class, 'English text of explanation');
    });

    test('a lesson returns its exercises ordered by position', function () {
        $lesson = Lesson::factory()->create();
        Exercise::factory()->for($lesson)->create(['position' => 3]);
        Exercise::factory()->for($lesson)->create(['position' => 1]);
        Exercise::factory()->for($lesson)->create(['position' => 2]);

        expect($lesson->exercises->pluck('position')->all())->toBe([1, 2, 3]);
    });

    test('a position is unique within a lesson', function () {
        $lesson = Lesson::factory()->create();
        Exercise::factory()->for($lesson)->create(['position' => 1]);

        expect(fn () => Exercise::factory()->for($lesson)->create(['position' => 1]))
            ->toThrow(QueryException::class);
    });

    test('unknown type and skill values are rejected by the database', function (string $column) {
        $exercise = Exercise::factory()->create();

        expect(fn () => DB::table('exercises')->where('id', $exercise->id)->update([$column => 'poetry']))
            ->toThrow(QueryException::class);
    })->with(['type', 'skill']);
});

describe('archival and restricted deletes', function () {
    test('archived exercises leave active queries but stay in history', function () {
        $lesson = Lesson::factory()->create();
        $exercises = Exercise::factory()->count(3)->for($lesson)->sequence(
            ['position' => 1], ['position' => 2], ['position' => 3],
        )->create();

        $exercises[0]->archive();

        expect($lesson->exercises()->active()->count())->toBe(2)
            ->and($lesson->exercises()->count())->toBe(3);

        $exercises[0]->restore();

        expect($lesson->exercises()->active()->count())->toBe(3);
    });

    test('a lesson with exercises cannot be deleted', function () {
        $exercise = Exercise::factory()->create();

        expect(fn () => $exercise->lesson->delete())->toThrow(QueryException::class);
    });

    test('a passage used by an exercise cannot be deleted', function () {
        $passage = Passage::factory()->reading()->create();
        Exercise::factory()->create(['passage_id' => $passage->id]);

        // Query before the delete: on PostgreSQL the failed statement aborts the test transaction.
        expect($passage->exercises)->toHaveCount(1);
        expect(fn () => $passage->delete())->toThrow(QueryException::class);
    });

    test('an exercise can exist without a passage', function () {
        expect(Exercise::factory()->create()->passage)->toBeNull();
    });
});
