<?php

use App\Enums\ExamKind;
use App\Enums\ExamSkill;
use App\Enums\ExerciseType;
use App\Enums\GradingMode;
use App\Exceptions\InvalidExercisePayload;
use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\ExamSection;
use App\Models\Level;
use App\Models\Passage;
use Database\Factories\Support\ExerciseSamples;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Runs a statement that the database must reject inside a savepoint, so the
 * test transaction survives the failure on PostgreSQL.
 */
function rejectedByDatabase(Closure $statement): bool
{
    try {
        DB::transaction($statement);
    } catch (QueryException) {
        return true;
    }

    return false;
}

describe('exams', function () {
    test('a level exam belongs to its level', function () {
        $level = Level::factory()->create();
        $exam = Exam::factory()->forLevel($level)->create()->fresh();

        expect($exam->kind)->toBe(ExamKind::Level)
            ->and($exam->level->is($level))->toBeTrue()
            ->and($level->exam->is($exam))->toBeTrue();
    });

    test('a level exam without a level is rejected by the database', function () {
        $row = Exam::factory()->make(['level_id' => null])->getAttributes();

        expect(rejectedByDatabase(fn () => DB::table('exams')->insert($row + ['created_at' => now()])))->toBeTrue();
    });

    test('a placement exam with a level is rejected by the database', function () {
        $level = Level::factory()->create();
        $row = Exam::factory()->placement()->make(['level_id' => $level->id])->getAttributes();

        expect(rejectedByDatabase(fn () => DB::table('exams')->insert($row + ['created_at' => now()])))->toBeTrue();
    });

    test('a second exam for the same level is rejected', function () {
        $level = Level::factory()->create();
        Exam::factory()->forLevel($level)->create();

        expect(rejectedByDatabase(fn () => Exam::factory()->forLevel($level)->create()))->toBeTrue();
    });

    test('only one placement exam can be active', function () {
        $first = Exam::factory()->placement()->create();

        expect($first->fresh()->level_id)->toBeNull()
            ->and(rejectedByDatabase(fn () => Exam::factory()->placement()->create()))->toBeTrue();

        $first->archive();

        $second = Exam::factory()->placement()->create();

        expect($second->kind)->toBe(ExamKind::Placement)
            ->and(Exam::query()->active()->where('kind', 'placement')->count())->toBe(1);
    });

    test('level exams and the placement exam coexist', function () {
        Exam::factory()->placement()->create();
        Exam::factory()->create();

        expect(Exam::query()->count())->toBe(2);
    });

    test('a pass score outside 0-100 is rejected', function () {
        expect(rejectedByDatabase(fn () => Exam::factory()->create(['pass_score' => 101])))->toBeTrue();
    });

    test('unlimited attempts are stored as null', function () {
        expect(Exam::factory()->create(['max_attempts' => null])->fresh()->max_attempts)->toBeNull()
            ->and(Exam::factory()->placement()->create(['max_attempts' => 3])->fresh()->max_attempts)->toBe(3);
    });

    test('an unknown kind is rejected by the database', function () {
        $exam = Exam::factory()->create();

        expect(rejectedByDatabase(fn () => DB::table('exams')->where('id', $exam->id)->update(['kind' => 'final'])))->toBeTrue();
    });

    test('keys are validated and immutable', function () {
        expect(fn () => Exam::factory()->create(['key' => 'Exam A1']))->toThrow(ValidationException::class, 'key');

        $exam = Exam::factory()->create(['key' => 'exam.a1']);
        $exam->key = 'exam.other';

        expect(fn () => $exam->save())->toThrow(LogicException::class);
    });

    test('a level with an exam cannot be deleted', function () {
        $exam = Exam::factory()->create();

        expect(rejectedByDatabase(fn () => $exam->level->delete()))->toBeTrue();
    });

    test('exams are archived, not deleted', function () {
        $exam = Exam::factory()->create();
        $exam->archive();

        expect(Exam::query()->active()->count())->toBe(0)
            ->and(Exam::query()->count())->toBe(1);

        $exam->restore();

        expect(Exam::query()->active()->count())->toBe(1);
    });
});

describe('exam sections', function () {
    test('sections are ordered by position', function () {
        $exam = Exam::factory()->create();
        ExamSection::factory()->for($exam)->sequence(['position' => 3], ['position' => 1], ['position' => 2])->count(3)->create();

        expect($exam->sections->pluck('position')->all())->toBe([1, 2, 3]);
    });

    test('a duplicate position in an exam is rejected', function () {
        $exam = Exam::factory()->create();
        ExamSection::factory()->for($exam)->create(['position' => 1]);

        expect(rejectedByDatabase(fn () => ExamSection::factory()->for($exam)->create(['position' => 1])))->toBeTrue();
    });

    test('skill and grading mode are cast to enums', function () {
        $section = ExamSection::factory()->create([
            'skill' => ExamSkill::Speaking,
            'grading_mode' => GradingMode::SelfAssessment,
        ])->fresh();

        expect($section->skill)->toBe(ExamSkill::Speaking)
            ->and($section->grading_mode)->toBe(GradingMode::SelfAssessment);
    });

    test('unknown skill or grading mode values are rejected by the database', function (string $column) {
        $section = ExamSection::factory()->create();

        expect(rejectedByDatabase(fn () => DB::table('exam_sections')->where('id', $section->id)->update([$column => 'poetry'])))->toBeTrue();
    })->with(['skill', 'grading_mode']);

    test('questions per attempt is optional', function () {
        expect(ExamSection::factory()->create()->fresh()->questions_per_attempt)->toBeNull()
            ->and(ExamSection::factory()->create(['questions_per_attempt' => 10])->fresh()->questions_per_attempt)->toBe(10);
    });

    test('the ai state grades through Tutor', function () {
        $section = ExamSection::factory()->ai(ExamSkill::Speaking)->create();

        expect($section->grading_mode)->toBe(GradingMode::Ai)
            ->and($section->skill)->toBe(ExamSkill::Speaking);
    });

    test('an exam with sections cannot be deleted', function () {
        $section = ExamSection::factory()->create();

        expect(rejectedByDatabase(fn () => $section->exam->delete()))->toBeTrue();
    });
});

describe('exam questions', function () {
    test('a factory question of every type saves and passes validation', function (ExerciseType $type) {
        $question = ExamQuestion::factory()->ofType($type)->create()->fresh();

        expect($question->type)->toBe($type)
            ->and($question->content)->toBeInstanceOf($type->contentClass())
            ->and($question->solution)->toBeInstanceOf($type->solutionClass());
    })->with(ExerciseType::cases());

    test('questions of a section are listed from the section', function () {
        $section = ExamSection::factory()->create();
        ExamQuestion::factory()->count(2)->for($section, 'section')->create();

        expect($section->questions)->toHaveCount(2)
            ->and($section->questions->first()->section->is($section))->toBeTrue();
    });

    test('an invalid payload names its path and writes nothing', function () {
        $solution = ExerciseSamples::solution(ExerciseType::MultipleChoice);
        $solution['correct'] = ['d'];

        try {
            ExamQuestion::factory()->create(['solution' => $solution]);
            $this->fail('Expected an exception.');
        } catch (InvalidExercisePayload $exception) {
            expect(array_keys($exception->errors()))->toContain('solution.correct.0');
        }

        expect(ExamQuestion::query()->count())->toBe(0);
    });

    test('changing the type without a new payload fails', function () {
        $question = ExamQuestion::factory()->multipleChoice()->create();
        $question->type = ExerciseType::Matching;

        expect(fn () => $question->save())->toThrow(InvalidExercisePayload::class);
        expect(ExamQuestion::query()->find($question->id)->type)->toBe(ExerciseType::MultipleChoice);
    });

    test('no php class names reach the database', function () {
        $section = ExamSection::factory()->create();

        foreach (ExerciseType::cases() as $type) {
            $question = ExamQuestion::factory()->for($section, 'section')->ofType($type)->create();
            $row = DB::table('exam_questions')->where('id', $question->id)->first();

            expect($row->content.$row->solution)->not->toContain('App\\');
        }
    });

    test('the explanation is translatable and optional', function () {
        $question = ExamQuestion::factory()->create([
            'explanation' => ['en' => 'Third person takes -s.', 'es' => 'La tercera persona lleva -s.'],
        ]);

        expect($question->fresh()->getTranslation('explanation', 'es'))->toBe('La tercera persona lleva -s.')
            ->and(ExamQuestion::factory()->create()->fresh()->explanation)->toBeNull();

        try {
            ExamQuestion::factory()->create(['explanation' => ['es' => 'Solo español']]);
            $this->fail('Expected an exception.');
        } catch (ValidationException $exception) {
            expect(array_keys($exception->errors()))->toContain('explanation.en');
        }
    });

    test('questions are archived, not deleted', function () {
        $section = ExamSection::factory()->create();
        $questions = ExamQuestion::factory()->count(3)->for($section, 'section')->create();

        $questions[0]->archive();

        expect($section->questions()->active()->count())->toBe(2)
            ->and($section->questions()->count())->toBe(3);
    });

    test('a section with questions cannot be deleted', function () {
        $question = ExamQuestion::factory()->create();

        expect(rejectedByDatabase(fn () => $question->section->delete()))->toBeTrue();
    });
});

describe('shared passages', function () {
    test('two questions can share one passage', function () {
        $passage = Passage::factory()->reading()->create();
        $section = ExamSection::factory()->create();
        ExamQuestion::factory()->count(2)->for($section, 'section')->create(['passage_id' => $passage->id]);

        expect($passage->examQuestions)->toHaveCount(2)
            ->and($passage->examQuestions->first()->passage->is($passage))->toBeTrue();
    });

    test('a passage used by a question cannot be deleted', function () {
        $passage = Passage::factory()->reading()->create();
        ExamQuestion::factory()->create(['passage_id' => $passage->id]);

        expect(rejectedByDatabase(fn () => $passage->delete()))->toBeTrue();
    });

    test('a question can exist without a passage', function () {
        expect(ExamQuestion::factory()->create()->passage)->toBeNull();
    });
});

describe('serialisation', function () {
    test('the solution is omitted for every type but stays readable on the server', function (ExerciseType $type) {
        $question = ExamQuestion::factory()->ofType($type)->create()->fresh();

        expect($question->toArray())->not->toHaveKey('solution')
            ->and($question->toJson())->not->toContain('"solution"')
            ->and($question->solution)->toBeInstanceOf($type->solutionClass());
    })->with(ExerciseType::cases());

    test('an exam with sections, questions and passage never exposes solutions or transcripts', function () {
        $exam = Exam::factory()->create();
        $section = ExamSection::factory()->for($exam)->create();
        $passage = Passage::factory()->listening()->create(['transcript' => 'The hidden transcript says purple.']);
        ExamQuestion::factory()->dictation()->for($section, 'section')->create(['passage_id' => $passage->id]);
        ExamQuestion::factory()->fillGap()->for($section, 'section')->create(['passage_id' => $passage->id]);

        $output = $exam->load('sections.questions.passage')->toArray();
        $json = json_encode($output);

        expect($output['sections'][0]['questions'])->toHaveCount(2)
            ->and($json)->not->toContain('"solution"')
            ->and($json)->not->toContain('"transcript"')
            ->and($json)->not->toContain('purple');
    });
});
