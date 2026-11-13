<?php

use App\Data\Exercises\ExercisePayloads;
use App\Enums\ExerciseType;
use App\Exceptions\InvalidExercisePayload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

// Payload tests need the container for validation messages and config, not the database.
pest()->extend(TestCase::class)
    ->in('Unit/Exercises');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * The error paths an exercise payload produces, or [] when it is valid.
 *
 * @param  array<string, mixed>  $content
 * @param  array<string, mixed>  $solution
 * @return list<string>
 */
function payloadErrors(ExerciseType $type, array $content, array $solution): array
{
    try {
        ExercisePayloads::validate($type, $content, $solution);
    } catch (InvalidExercisePayload $exception) {
        return array_keys($exception->errors());
    }

    return [];
}
