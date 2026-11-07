<?php

/*
 * The starter kit was merged over existing governance files. These checks make
 * sure none of them was replaced or emptied by the merge or by a later
 * regeneration of the kit.
 */

dataset('governance files', [
    'licence' => ['LICENSE', 'PolyForm Noncommercial License 1.0.0'],
    'contributor licence agreement' => ['CLA.md', '# Parlora Hub Individual Contributor Licence Agreement'],
    'code of conduct' => ['CODE_OF_CONDUCT.md', '# Contributor Covenant 3.0 Code of Conduct'],
    'contributing guide' => ['CONTRIBUTING.md', '# Contributing to Parlora Hub'],
    'security policy' => ['SECURITY.md', '# Security policy'],
    'agent guide' => ['AGENTS.md', '# Parlora Hub — guide for AI coding agents'],
    'claude entry point' => ['CLAUDE.md', '@AGENTS.md'],
    'readme' => ['README.md', '# Parlora Hub'],
    'spanish readme' => ['README.es.md', '# Parlora Hub'],
    'changelog' => ['CHANGELOG.md', '# Changelog'],
    'integration notes' => ['docs/INTEGRATION.md', 'Parlora Tutor'],
    'repository settings' => ['docs/repository-settings.md', '# Repository settings'],
]);

test('governance files keep their content', function (string $path, string $marker) {
    $file = base_path($path);

    expect($file)->toBeFile()
        ->and(file_get_contents($file))->toContain($marker);
})->with('governance files');

test('the readme is not the starter kit readme', function () {
    expect(file_get_contents(base_path('README.md')))
        ->toContain('PolyForm Noncommercial')
        ->not->toContain('Laravel is a web application framework');
});

test('agent instructions are not git-ignored', function () {
    $rules = array_map(trim(...), file(base_path('.gitignore')));

    expect($rules)
        ->not->toContain('/AGENTS.md')
        ->not->toContain('AGENTS.md')
        ->not->toContain('/CLAUDE.md')
        ->not->toContain('CLAUDE.md');
});

test('private artefacts stay git-ignored', function () {
    $rules = array_map(trim(...), file(base_path('.gitignore')));

    expect($rules)->toContain('.claude/', 'openspec/', 'ROADMAP.md', 'CLAUDE.local.md', 'reference/', '.env');
});

test('only the pnpm lockfile is present', function () {
    expect(base_path('pnpm-lock.yaml'))->toBeFile()
        ->and(base_path('package-lock.json'))->not->toBeFile()
        ->and(base_path('yarn.lock'))->not->toBeFile();
});
