<?php

/*
 * Authorization coverage gate.
 *
 * Fails when the line coverage of the authorization code (gates and policies)
 * in a Clover report is below the minimum. The overall minimum (85 %) is
 * enforced by Pest; access rules get a stricter one because a gap there is a
 * security gap.
 *
 * Authorization code: every file under app/Policies/ plus the providers that
 * define gates, listed below. Add a provider here when it defines a gate.
 *
 * Usage: php scripts/check-authorization-coverage.php <clover.xml> [minimum]
 */

const GATE_FILES = [
    'app/Providers/HorizonServiceProvider.php',
];

const POLICY_DIRECTORY = 'app/Policies/';

$cloverPath = $argv[1] ?? null;
$minimum = (float) ($argv[2] ?? 95);

if ($cloverPath === null || ! is_file($cloverPath)) {
    fwrite(STDERR, "Clover report not found: {$cloverPath}\n");
    exit(2);
}

$clover = simplexml_load_file($cloverPath);

if ($clover === false) {
    fwrite(STDERR, "Cannot parse the Clover report: {$cloverPath}\n");
    exit(2);
}

$root = rtrim(str_replace('\\', '/', dirname(__DIR__)), '/').'/';
$failures = [];
$checked = 0;

foreach ($clover->xpath('//file') ?: [] as $file) {
    $path = str_replace('\\', '/', (string) $file['name']);
    $relative = str_starts_with($path, $root) ? substr($path, strlen($root)) : $path;

    if (! in_array($relative, GATE_FILES, true) && ! str_starts_with($relative, POLICY_DIRECTORY)) {
        continue;
    }

    $checked++;
    $statements = (int) $file->metrics['statements'];
    $covered = (int) $file->metrics['coveredstatements'];
    $percent = $statements === 0 ? 100.0 : $covered / $statements * 100;

    printf("%-60s %6.1f %%\n", $relative, $percent);

    if ($percent < $minimum) {
        $failures[] = $relative;
    }
}

if ($checked === 0) {
    fwrite(STDERR, "No authorization files found in the Clover report.\n");
    exit(1);
}

if ($failures !== []) {
    fwrite(STDERR, sprintf("Authorization coverage below %.0f %%: %s\n", $minimum, implode(', ', $failures)));
    exit(1);
}

printf("Authorization coverage is at least %.0f %% in %d file(s).\n", $minimum, $checked);
