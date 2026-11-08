<?php

use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Support\Facades\Process;

/**
 * Runs the licence policy against a lock file built from the given packages.
 *
 * @param  array<string, list<string>>  $packages  name => licence array
 */
function runLicencePolicy(array $packages, string $policy): ProcessResult
{
    $dir = sys_get_temp_dir().'/licence-policy-'.bin2hex(random_bytes(4));
    mkdir($dir);

    $lock = ['packages' => [], 'packages-dev' => []];
    foreach ($packages as $name => $licences) {
        $lock['packages'][] = ['name' => $name, 'license' => $licences];
    }

    file_put_contents("{$dir}/composer.lock", json_encode($lock));
    file_put_contents("{$dir}/policy.yml", $policy);

    return Process::run([PHP_BINARY, base_path('scripts/check-composer-licences.php'), "{$dir}/composer.lock", "{$dir}/policy.yml"]);
}

$policy = <<<'YAML'
allow-licenses:
  - MIT
  - BSD-3-Clause
  # comment inside the list
  - Apache-2.0

allow-dependencies-licenses:
  - pkg:composer/acme/reviewed
YAML;

test('allowed licences pass', function () use ($policy) {
    $result = runLicencePolicy(['acme/a' => ['MIT'], 'acme/b' => ['Apache-2.0']], $policy);

    expect($result->exitCode())->toBe(0)
        ->and($result->output())->toContain('2 Composer packages allowed');
});

test('a GPL-3.0 dependency fails', function () use ($policy) {
    $result = runLicencePolicy(['acme/a' => ['MIT'], 'acme/gpl' => ['GPL-3.0-only']], $policy);

    expect($result->exitCode())->toBe(1)
        ->and($result->errorOutput())->toContain('acme/gpl (GPL-3.0-only)')
        ->not->toContain('acme/a');
});

test('a licence array is a choice', function () use ($policy) {
    $result = runLicencePolicy(['acme/dual' => ['BSD-3-Clause', 'GPL-2.0-only', 'GPL-3.0-only']], $policy);

    expect($result->exitCode())->toBe(0);
});

test('SPDX OR needs one allowed licence and AND needs all', function (string $expression, int $exit) use ($policy) {
    expect(runLicencePolicy(['acme/x' => [$expression]], $policy)->exitCode())->toBe($exit);
})->with([
    'or with an allowed side' => ['(MIT or GPL-3.0-only)', 0],
    'and with a disallowed side' => ['MIT and GPL-3.0-only', 1],
    'and with allowed sides' => ['MIT and Apache-2.0', 0],
]);

test('a package without a licence fails', function () use ($policy) {
    expect(runLicencePolicy(['acme/none' => []], $policy)->exitCode())->toBe(1);
});

test('reviewed exceptions pass', function () use ($policy) {
    expect(runLicencePolicy(['acme/reviewed' => ['LGPL-3.0-only']], $policy)->exitCode())->toBe(0);
});

test('the real lock file follows the repository policy', function () {
    $result = Process::run([PHP_BINARY, base_path('scripts/check-composer-licences.php')]);

    expect($result->exitCode())->toBe(0, $result->errorOutput());
});
