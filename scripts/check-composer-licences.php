<?php

/*
 * Composer licence policy.
 *
 * Fails when a package in composer.lock (runtime or development) has no
 * licence from the allow-list. The allow-list and the reviewed exceptions are
 * read from .github/dependency-review-config.yml, so pull-request dependency
 * review and this check share one policy.
 *
 * A Composer licence array is a choice ("MIT or GPL"), so one allowed entry is
 * enough. Inside a single entry, SPDX "OR" also needs one allowed licence and
 * "AND" needs all of them.
 *
 * Plain PHP with no dependencies, so it runs before `composer install`.
 *
 * Usage: php scripts/check-composer-licences.php [composer.lock] [policy.yml]
 */

$root = dirname(__DIR__);
$lockPath = $argv[1] ?? $root.'/composer.lock';
$policyPath = $argv[2] ?? $root.'/.github/dependency-review-config.yml';

/**
 * Items of a top-level YAML block list, e.g. `allow-licenses:` followed by
 * `  - MIT` lines. Comments and blank lines are skipped.
 *
 * @return list<string>
 */
function yamlList(string $yaml, string $key): array
{
    $items = [];
    $inside = false;

    foreach (preg_split('/\R/', $yaml) ?: [] as $line) {
        if (preg_match('/^'.preg_quote($key, '/').':\s*$/', $line) === 1) {
            $inside = true;

            continue;
        }

        if (! $inside || trim($line) === '' || preg_match('/^\s*#/', $line) === 1) {
            continue;
        }

        if (preg_match('/^\s+-\s+(\S+)/', $line, $match) === 1) {
            $items[] = $match[1];

            continue;
        }

        break;
    }

    return $items;
}

/**
 * @param  list<string>  $allowed
 */
function entryIsAllowed(string $entry, array $allowed): bool
{
    $expression = trim($entry, " \t()");

    foreach (preg_split('/\s+or\s+/i', $expression) ?: [] as $alternative) {
        $terms = preg_split('/\s+and\s+/i', trim($alternative, " \t()")) ?: [];

        $all = array_reduce(
            $terms,
            fn (bool $carry, string $term): bool => $carry && in_array(trim($term, " \t()"), $allowed, true),
            true,
        );

        if ($terms !== [] && $all) {
            return true;
        }
    }

    return false;
}

$policy = @file_get_contents($policyPath);
$lock = json_decode((string) @file_get_contents($lockPath), true);

if ($policy === false || ! is_array($lock)) {
    fwrite(STDERR, "Cannot read {$lockPath} or {$policyPath}.\n");
    exit(2);
}

$allowed = yamlList($policy, 'allow-licenses');
$exceptions = yamlList($policy, 'allow-dependencies-licenses');

if ($allowed === []) {
    fwrite(STDERR, "No allow-licenses list found in {$policyPath}.\n");
    exit(2);
}

$violations = [];
$packages = array_merge($lock['packages'] ?? [], $lock['packages-dev'] ?? []);

foreach ($packages as $package) {
    $name = (string) ($package['name'] ?? '?');
    $licences = (array) ($package['license'] ?? []);

    if (in_array("pkg:composer/{$name}", $exceptions, true)) {
        continue;
    }

    $ok = array_filter($licences, fn (string $entry): bool => entryIsAllowed($entry, $allowed)) !== [];

    if (! $ok) {
        $violations[] = sprintf('%s (%s)', $name, $licences === [] ? 'no licence' : implode(' or ', $licences));
    }
}

if ($violations !== []) {
    fwrite(STDERR, "Composer packages with a licence outside the policy:\n  - ".implode("\n  - ", $violations)."\n");
    exit(1);
}

echo sprintf("Licence policy: %d Composer packages allowed.\n", count($packages));
