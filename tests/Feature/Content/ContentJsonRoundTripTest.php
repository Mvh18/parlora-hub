<?php

use App\Content\ContentImporter;
use App\Content\ContentTables;
use App\Filament\Support\ContentJson;
use App\Models\Exam;
use App\Models\Level;
use App\Models\Passage;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Model;
use Tests\Support\Content\FixtureTree;

/**
 * Objects with their members sorted by name and null members dropped (the
 * importer reads an absent member as null), lists left in order, so that
 * member order alone never fails a comparison.
 */
function canonicalFragment(mixed $value): mixed
{
    if (! is_array($value)) {
        return $value;
    }

    if (! array_is_list($value)) {
        $value = array_filter($value, fn (mixed $member): bool => $member !== null);
        ksort($value);
    }

    return array_map(canonicalFragment(...), $value);
}

/** Every fixture file as relative path → decoded body without its `$schema` hint. */
function fixtureFragments(): array
{
    $root = FixtureTree::fixtures();
    $fragments = [];
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));

    foreach ($files as $file) {
        if ($file->getExtension() !== 'json') {
            continue;
        }

        $data = json_decode((string) file_get_contents($file->getPathname()), true, flags: JSON_THROW_ON_ERROR);
        unset($data['$schema']);

        $fragments[str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1))] = $data;
    }

    ksort($fragments);

    return $fragments;
}

test('every fixture file comes back unchanged through import and Copy as JSON', function () {
    app(ContentImporter::class)->import(FixtureTree::tree(FixtureTree::fixtures()));

    $fragments = fixtureFragments();

    expect($fragments)->toHaveCount(7);

    foreach ($fragments as $path => $source) {
        /** @var Model $model */
        $model = collect([Level::class, Unit::class, Exam::class, Passage::class])
            ->map(fn (string $class) => $class::query()->where('key', $source['key'])->first())
            ->filter()
            ->sole();

        $exported = json_decode(ContentJson::encode($model), true, flags: JSON_THROW_ON_ERROR);

        expect(canonicalFragment($exported))->toBe(canonicalFragment($source), $path);
    }
});

test('the tree reads exactly the members each table lists', function () {
    $tree = FixtureTree::tree(FixtureTree::fixtures());

    foreach (ContentTables::all() as $name => $table) {
        $derived = $name === ContentTables::EXAMS ? ['kind'] : [];

        foreach ($tree->rows($name) as $row) {
            expect(array_keys($row->attributes))->toBe([...$derived, ...$table->members], $name);
        }
    }
});
