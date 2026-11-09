<?php

use Symfony\Component\Yaml\Yaml;

/*
 * The CI, scanning and labelling configuration is code: these checks keep the
 * required gates from being dropped by an unrelated edit.
 */

/**
 * @return array<string, mixed>
 */
function repositoryYaml(string $path): array
{
    return Yaml::parseFile(base_path($path));
}

test('ci-ok waits for every required job', function () {
    $jobs = repositoryYaml('.github/workflows/ci.yml')['jobs'];

    expect($jobs)->toHaveKeys(['hygiene', 'security', 'php', 'js', 'compose', 'ci-ok'])
        ->and($jobs['ci-ok']['needs'])->toEqualCanonicalizing(['hygiene', 'security', 'php', 'js', 'compose'])
        ->and($jobs['ci-ok']['if'])->toBe('always()');
});

test('the php job runs composer check against PostgreSQL with coverage', function () {
    $job = repositoryYaml('.github/workflows/ci.yml')['jobs']['php'];
    $steps = collect($job['steps']);

    expect($job['services'])->toHaveKey('postgres')
        ->and($job['env']['DB_CONNECTION'])->toBe('pgsql')
        ->and($steps->pluck('with.coverage')->filter()->first())->toBe('pcov')
        ->and($steps->pluck('run')->filter()->implode("\n"))->toContain('composer check');
});

test('the js job runs pnpm check and pnpm build', function () {
    $runs = collect(repositoryYaml('.github/workflows/ci.yml')['jobs']['js']['steps'])
        ->pluck('run')->filter()->implode("\n");

    expect($runs)->toContain('pnpm check')->toContain('pnpm build');
});

test('composer check enforces the coverage minimum', function () {
    $check = json_decode((string) file_get_contents(base_path('composer.json')), true)['scripts']['check'];

    expect(implode("\n", $check))
        ->toMatch('/--coverage --min=\d+/')
        ->toContain('scripts/check-authorization-coverage.php');
});

test('the security workflow audits both package managers and the licence policy', function () {
    $runs = collect(repositoryYaml('.github/workflows/security.yml')['jobs'])
        ->flatMap(fn (array $job) => $job['steps'] ?? [])
        ->pluck('run')->filter()->implode("\n");

    expect($runs)
        ->toContain('composer audit')
        ->toContain('pnpm audit --audit-level=high')
        ->toContain('composer licences:check');
});

test('dependabot covers every ecosystem with a seven-day cooldown', function () {
    $updates = collect(repositoryYaml('.github/dependabot.yml')['updates']);

    expect($updates->pluck('package-ecosystem')->all())
        ->toContain('github-actions', 'composer', 'npm', 'docker', 'docker-compose');

    $updates->each(fn (array $update) => expect($update['cooldown']['default-days'])->toBe(7));
});

test('codeql analyses actions and javascript-typescript', function () {
    $languages = repositoryYaml('.github/workflows/codeql.yml')['jobs']['analyze']['strategy']['matrix']['language'];

    expect($languages)->toContain('actions', 'javascript-typescript');
});

test('labels as code define the issue types and areas in use', function () {
    $names = collect(repositoryYaml('.github/labels.yml'))->pluck('name')->all();

    expect($names)->toContain(
        'type/epic', 'type/feature', 'type/bug', 'type/chore', 'type/docs',
        'area/backend', 'area/web', 'area/ci', 'area/security', 'area/integration',
        'phase/0', 'priority/p1', 'dependencies',
    );
});

test('feature tests use PostgreSQL in CI', function () {
    expect(config('database.default'))->toBe('pgsql');
})->skip(getenv('CI') !== 'true', 'Only enforced in CI, where the php job provides PostgreSQL.');

test('the php job provides Valkey for the queue test', function () {
    $job = repositoryYaml('.github/workflows/ci.yml')['jobs']['php'];

    expect($job['services'])->toHaveKey('valkey')
        ->and($job['env']['REDIS_HOST'])->toBe('127.0.0.1');
});

test('the compose job waits for every service to become healthy', function () {
    $runs = collect(repositoryYaml('.github/workflows/ci.yml')['jobs']['compose']['steps'])
        ->pluck('run')->filter()->implode("\n");

    expect($runs)->toContain('docker compose up -d --build --wait');
});
