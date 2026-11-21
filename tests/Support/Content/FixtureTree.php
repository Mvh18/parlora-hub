<?php

namespace Tests\Support\Content;

use Illuminate\Support\Facades\File;

/**
 * A scratch copy of `tests/Fixtures/content` that a test can mutate to cover
 * removal, reordering, moves and errors without touching the fixtures.
 */
final class FixtureTree
{
    /** @var list<string> */
    private static array $created = [];

    /** Copy the fixture tree to a new temporary directory and return its path. */
    public static function copy(): string
    {
        $directory = sys_get_temp_dir().'/hub-content-'.bin2hex(random_bytes(6));

        File::copyDirectory(dirname(__DIR__, 2).'/Fixtures/content', $directory);
        self::$created[] = $directory;

        return $directory;
    }

    /** An empty temporary directory. */
    public static function empty(): string
    {
        $directory = sys_get_temp_dir().'/hub-content-'.bin2hex(random_bytes(6));

        mkdir($directory);
        self::$created[] = $directory;

        return $directory;
    }

    /**
     * Read a JSON file of the tree, let the callback change it, and write it back.
     *
     * @param  callable(array<string, mixed>): array<string, mixed>  $change
     */
    public static function edit(string $directory, string $relative, callable $change): void
    {
        $document = json_decode((string) file_get_contents("{$directory}/{$relative}"), true, 512, JSON_THROW_ON_ERROR);

        self::write($directory, $relative, $change($document));
    }

    /**
     * @param  array<string, mixed>  $document
     */
    public static function write(string $directory, string $relative, array $document): void
    {
        $path = "{$directory}/{$relative}";

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        file_put_contents($path, json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }

    /** Read a JSON file of the tree. */
    public static function read(string $directory, string $relative): mixed
    {
        return json_decode((string) file_get_contents("{$directory}/{$relative}"), true, 512, JSON_THROW_ON_ERROR);
    }

    /** Remove every scratch directory created so far. */
    public static function cleanUp(): void
    {
        foreach (self::$created as $directory) {
            File::deleteDirectory($directory);
        }

        self::$created = [];
    }
}
