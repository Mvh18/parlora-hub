<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use LogicException;

/**
 * A stable, human-readable identity for a content row, e.g. `a1.u03.l02.ex05`.
 *
 * The importer upserts by key, and content files never mention database ids,
 * so the key is validated when a row is created and can never change after.
 * Keys are chosen by authors, never derived from the row's parents, so moving
 * a lesson does not rename it. Uniqueness is enforced by the table's unique
 * index.
 *
 * @mixin Model
 */
trait HasStableKey
{
    /** Lowercase segments of letters and digits joined by dots or hyphens. */
    public const KEY_PATTERN = '/^[a-z0-9]+(?:[.-][a-z0-9]+)*$/';

    public const KEY_MAX_LENGTH = 120;

    protected static function bootHasStableKey(): void
    {
        static::saving(function (Model $model): void {
            if ($model->exists && $model->isDirty('key')) {
                throw new LogicException(sprintf('The key of a %s is immutable.', class_basename($model)));
            }

            if (! $model->exists) {
                self::validateKey($model->getAttribute('key'));
            }
        });
    }

    /**
     * @throws ValidationException when the key is missing, too long or malformed
     */
    public static function validateKey(mixed $key): void
    {
        Validator::make(['key' => $key], [
            'key' => ['required', 'string', 'max:'.self::KEY_MAX_LENGTH, 'regex:'.self::KEY_PATTERN],
        ])->validate();
    }
}
