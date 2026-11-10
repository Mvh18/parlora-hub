<?php

use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Support\Facades\Process;

/**
 * Runs the authorization coverage gate against a Clover report built from
 * the given files.
 *
 * @param  array<string, array{int, int}>  $files  relative path => [statements, covered]
 */
function runAuthorizationCoverage(array $files): ProcessResult
{
    $xml = '<?xml version="1.0"?><coverage><project>';
    foreach ($files as $path => [$statements, $covered]) {
        $xml .= sprintf(
            '<file name="%s"><metrics statements="%d" coveredstatements="%d"/></file>',
            base_path($path), $statements, $covered,
        );
    }
    $xml .= '</project></coverage>';

    $report = sys_get_temp_dir().'/clover-'.bin2hex(random_bytes(4)).'.xml';
    file_put_contents($report, $xml);

    return Process::run([PHP_BINARY, base_path('scripts/check-authorization-coverage.php'), $report]);
}

test('passes when every gate and policy file reaches 95 %', function () {
    $result = runAuthorizationCoverage([
        'app/Providers/HorizonServiceProvider.php' => [4, 4],
        'app/Policies/LessonPolicy.php' => [20, 19],
        'app/Models/User.php' => [10, 1],
    ]);

    expect($result->exitCode())->toBe(0)
        ->and($result->output())->not->toContain('User.php');
});

test('fails when a policy is below 95 %', function () {
    $result = runAuthorizationCoverage([
        'app/Providers/HorizonServiceProvider.php' => [4, 4],
        'app/Policies/LessonPolicy.php' => [20, 18],
    ]);

    expect($result->exitCode())->toBe(1)
        ->and($result->errorOutput())->toContain('app/Policies/LessonPolicy.php');
});

test('fails when a gate provider is below 95 %', function () {
    $result = runAuthorizationCoverage([
        'app/Providers/HorizonServiceProvider.php' => [4, 3],
    ]);

    expect($result->exitCode())->toBe(1);
});

test('fails when the report has no authorization files', function () {
    expect(runAuthorizationCoverage(['app/Models/User.php' => [10, 10]])->exitCode())->toBe(1);
});
