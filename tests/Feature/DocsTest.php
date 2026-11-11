<?php

use Illuminate\Support\Facades\File;

/*
 * Public documentation stays in step with the project: the ADRs keep their
 * structure, the licence is described correctly, the quick start matches
 * ADR 0002 and the agent guide carries the skeleton's rules.
 */

/**
 * @return array<string, string> Relative path => contents.
 */
function publicDocs(): array
{
    $paths = ['README.md', 'README.es.md', 'CONTRIBUTING.md', 'AGENTS.md', 'SECURITY.md'];

    foreach (File::allFiles(base_path('docs')) as $file) {
        // docs/ROADMAP.md is private and git-ignored.
        if ($file->getExtension() === 'md' && $file->getFilename() !== 'ROADMAP.md') {
            $paths[] = 'docs/'.str_replace('\\', '/', $file->getRelativePathname());
        }
    }

    return collect($paths)
        ->mapWithKeys(fn (string $path) => [$path => (string) file_get_contents(base_path($path))])
        ->all();
}

test('ADR 0001 and ADR 0002 exist', function (string $pattern) {
    expect(glob(base_path("docs/adr/{$pattern}")))->toHaveCount(1);
})->with(['0001-*.md', '0002-*.md']);

test('every ADR has a status, context, decision and consequences', function () {
    $adrs = glob(base_path('docs/adr/[0-9][0-9][0-9][0-9]-*.md')) ?: [];

    expect($adrs)->not->toBeEmpty();

    foreach ($adrs as $adr) {
        $text = (string) file_get_contents($adr);

        expect($text)
            ->toMatch('/^# ADR \d{4}: /m', basename($adr).' title')
            ->toMatch('/^- \*\*Status:\*\* (Proposed|Accepted|Superseded|Deprecated)/m', basename($adr).' status')
            ->toMatch('/^## Context$/m', basename($adr).' context')
            ->toMatch('/^## Decision$/m', basename($adr).' decision')
            ->toMatch('/^## Consequences$/m', basename($adr).' consequences');
    }
});

test('the public docs never call the project open source', function () {
    $offenders = [];

    foreach (publicDocs() as $path => $text) {
        preg_match_all('/open[\s-]+source/i', $text, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[0] as [, $offset]) {
            // A mention is fine only when it is negated just before it
            // ("not open source", "never as open source", "No es open source").
            $before = substr($text, max(0, $offset - 40), min(40, $offset));

            if (preg_match('/\b(not|never|no)\b[^.]*$/i', $before) !== 1) {
                $line = substr_count(substr($text, 0, $offset), "\n") + 1;
                $offenders[] = "{$path}:{$line}";
            }
        }
    }

    expect($offenders)->toBe([]);
});

test('both READMEs name the PolyForm Noncommercial licence', function (string $readme) {
    expect((string) file_get_contents(base_path($readme)))
        ->toContain('PolyForm Noncommercial License 1.0.0')
        ->toContain('source-available');
})->with(['README.md', 'README.es.md']);

test('both READMEs give the quick start from ADR 0002', function (string $readme) {
    expect((string) file_get_contents(base_path($readme)))
        ->toContain('git clone https://github.com/Mvh18/parlora-hub.git')
        ->toContain('docker compose up -d --wait')
        ->toContain('http://127.0.0.1:8000')
        ->toContain('docs/adr/0002-php-environment.md');
})->with(['README.md', 'README.es.md']);

test('AGENTS.md documents the checks and the skeleton rules', function () {
    expect((string) file_get_contents(base_path('AGENTS.md')))
        ->toContain('composer check')
        ->toContain('pnpm check')
        ->toContain('lang/en')
        ->toContain('design tokens')
        ->toContain('is_admin')
        ->toContain('hub:make-admin');
});

test('the architecture overview covers the boundaries later phases rely on', function () {
    expect((string) file_get_contents(base_path('docs/architecture.md')))
        ->toContain('## Parlora Tutor boundary')
        ->toContain('parlora-net')
        ->toContain('config/parlora_tutor.php')
        ->toContain('resources/css/tokens.css')
        ->toContain('lang/en');
});

test('relative links in the public docs point to files that exist', function () {
    $broken = [];

    foreach (publicDocs() as $path => $text) {
        preg_match_all('/\]\((?!https?:|mailto:|#)([^)#\s]+)/', $text, $matches);

        foreach ($matches[1] as $target) {
            $resolved = dirname(base_path($path)).'/'.$target;

            if (! file_exists($resolved)) {
                $broken[] = "{$path} → {$target}";
            }
        }
    }

    expect($broken)->toBe([]);
});
