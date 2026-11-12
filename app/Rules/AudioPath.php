<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\ValidationException;

/**
 * A path on the private `content-audio` disk.
 *
 * Plain file names only: relative, no `..`, no leading slash or drive, no
 * hidden files or special characters, and an audio extension. The allow-list
 * of characters is what keeps traversal out; null is valid because the audio
 * column is nullable.
 */
final class AudioPath implements ValidationRule
{
    public const EXTENSIONS = ['mp3', 'ogg', 'opus', 'm4a', 'wav'];

    /** One path segment: starts with a letter or digit, never `.` or `..`. */
    private const SEGMENT = '/^[A-Za-z0-9][A-Za-z0-9._-]*$/';

    /** The problem with the path, or null when it is valid. */
    public static function error(mixed $value, string $attribute = 'audio_path'): ?string
    {
        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            return trans('validation.content.audio_path.not_string', ['attribute' => $attribute]);
        }

        foreach (explode('/', $value) as $segment) {
            if (preg_match(self::SEGMENT, $segment) !== 1) {
                return trans('validation.content.audio_path.invalid', ['attribute' => $attribute]);
            }
        }

        if (! in_array(strtolower(pathinfo($value, PATHINFO_EXTENSION)), self::EXTENSIONS, true)) {
            return trans('validation.content.audio_path.extension', [
                'attribute' => $attribute,
                'extensions' => implode(', ', self::EXTENSIONS),
            ]);
        }

        return null;
    }

    /**
     * @throws ValidationException naming the attribute
     */
    public static function assert(string $attribute, mixed $value): void
    {
        $error = self::error($value, $attribute);

        if ($error !== null) {
            throw ValidationException::withMessages([$attribute => [$error]]);
        }
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $error = self::error($value, $attribute);

        if ($error !== null) {
            $fail($error);
        }
    }
}
