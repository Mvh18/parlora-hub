<?php

use App\Enums\ExerciseType;
use App\Models\Exercise;
use App\Models\Lesson;
use App\Models\Passage;
use Database\Factories\Support\ExerciseSamples;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Finder\Finder;

/**
 * Every string in a payload that the learner cannot already read in `content`.
 *
 * @param  array<string, mixed>  $solution
 * @return list<string>
 */
function secretsOf(array $solution, array $content): array
{
    $visible = json_encode($content);
    $strings = [];

    array_walk_recursive($solution, function (mixed $value) use (&$strings): void {
        if (is_string($value) && strlen($value) > 3) {
            $strings[] = $value;
        }
    });

    return array_values(array_filter($strings, fn (string $s): bool => ! str_contains((string) $visible, $s)));
}

/** @param  array<string, mixed>  $data */
function containsKey(array $data, string $key): bool
{
    foreach ($data as $name => $value) {
        if ($name === $key || (is_array($value) && containsKey($value, $key))) {
            return true;
        }
    }

    return false;
}

describe('exercise serialisation', function () {
    test('the solution is omitted from toArray and toJson for every type', function (ExerciseType $type) {
        $exercise = Exercise::factory()->ofType($type)->create()->fresh();
        $secrets = secretsOf(ExerciseSamples::solution($type), ExerciseSamples::content($type));

        expect($exercise->toArray())->not->toHaveKey('solution')
            ->and($exercise->toJson())->not->toContain('"solution"');

        foreach ($secrets as $secret) {
            expect($exercise->toJson())->not->toContain(json_encode($secret, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        // Still readable on the server through an explicit access.
        expect($exercise->solution)->toBeInstanceOf($type->solutionClass());
    })->with(ExerciseType::cases());

    test('the content stays serialised', function () {
        $exercise = Exercise::factory()->multipleChoice()->create()->fresh();

        expect($exercise->toArray()['content']['options'])->toHaveCount(3);
    });
});

describe('nested serialisation', function () {
    test('a lesson with its exercises and passage never exposes solutions or transcripts', function () {
        $lesson = Lesson::factory()->create();
        $passage = Passage::factory()->listening()->create(['transcript' => 'The hidden transcript says purple.']);

        foreach ([ExerciseType::MultipleChoice, ExerciseType::FillGap, ExerciseType::Dictation] as $index => $type) {
            Exercise::factory()->for($lesson)->ofType($type)->create([
                'passage_id' => $passage->id,
                'position' => $index + 1,
            ]);
        }

        $output = $lesson->load('exercises.passage')->toArray();
        $json = json_encode($output);

        expect($output['exercises'])->toHaveCount(3)
            ->and(containsKey($output, 'solution'))->toBeFalse()
            ->and(containsKey($output, 'transcript'))->toBeFalse()
            ->and($json)->not->toContain('purple')
            ->and($json)->not->toContain('lovely today');
    });

    test('a passage loaded with its exercises hides the transcript and the solutions', function () {
        $passage = Passage::factory()->listening()->create(['transcript' => 'Secret script.']);
        Exercise::factory()->dictation()->create(['passage_id' => $passage->id]);

        $output = $passage->load('exercises')->toArray();

        expect(containsKey($output, 'solution'))->toBeFalse()
            ->and(containsKey($output, 'transcript'))->toBeFalse();
    });
});

describe('architecture', function () {
    test('every model with a solution or transcript column hides it', function () {
        $checked = [];

        foreach (Finder::create()->files()->name('*.php')->in(app_path('Models'))->depth(0) as $file) {
            $class = 'App\\Models\\'.$file->getBasename('.php');

            if (! is_subclass_of($class, Model::class)) {
                continue;
            }

            /** @var Model $model */
            $model = new $class;

            foreach (['solution', 'transcript'] as $column) {
                if (Schema::hasColumn($model->getTable(), $column)) {
                    $checked[] = "{$class}::{$column}";
                    expect(in_array($column, $model->getHidden(), true))
                        ->toBeTrue("{$class} must hide {$column}");
                }
            }
        }

        // The rule must actually cover something: exercises and passages today.
        expect($checked)->toContain('App\\Models\\Exercise::solution', 'App\\Models\\Passage::transcript');
    });
});
