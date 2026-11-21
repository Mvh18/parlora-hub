<?php

namespace App\Content;

/**
 * Where the content files are: `--path` when given, otherwise
 * `hub.content_path`. A relative path is resolved against the project root.
 */
final class ContentPath
{
    public static function resolve(?string $path = null): string
    {
        if ($path === null || $path === '') {
            return (string) config('hub.content_path');
        }

        return self::isAbsolute($path) ? $path : base_path($path);
    }

    public static function isAbsolute(string $path): bool
    {
        return str_starts_with($path, '/')
            || str_starts_with($path, '\\')
            || preg_match('~^[A-Za-z]:[\\\\/]~', $path) === 1;
    }
}
