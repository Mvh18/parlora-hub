<?php

use Symfony\Component\Finder\Finder;

arch('laravel conventions')->preset()->laravel();

arch('security')->preset()->security();

arch('application code does not read the environment directly')
    ->expect('env')
    ->not->toBeUsed();

test('env() is only called inside config/', function () {
    $files = Finder::create()
        ->files()
        ->name('*.php')
        ->in(array_map(
            fn (string $dir): string => dirname(__DIR__, 2).'/'.$dir,
            ['app', 'bootstrap', 'database', 'routes'],
        ))
        ->exclude('cache');

    $offenders = [];

    foreach ($files as $file) {
        // A bare env( call; method calls such as ->env( or ::env( are fine.
        if (preg_match('/(?<![\w$>:])env\s*\(/', $file->getContents()) === 1) {
            $offenders[] = $file->getRelativePathname();
        }
    }

    expect($offenders)->toBeEmpty();
});
