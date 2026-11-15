<?php

namespace App\Models\Concerns;

use App\Data\Exercises\ExercisePayloads;
use Illuminate\Database\Eloquent\Model;

/**
 * Validates the `content` and `solution` JSON of a model with a `type` column
 * (an exercise or an exam question) before every save.
 *
 * It reads the stored JSON, not the cast value, so changing `type` without a
 * matching payload fails before anything is written.
 *
 * @mixin Model
 */
trait ValidatesExercisePayloads
{
    protected static function bootValidatesExercisePayloads(): void
    {
        static::saving(function (self $model): void {
            ExercisePayloads::validate(
                $model->type,
                self::storedPayload($model, 'content'),
                self::storedPayload($model, 'solution'),
            );
        });
    }

    /** @return array<string, mixed> */
    private static function storedPayload(self $model, string $column): array
    {
        $raw = $model->getAttributes()[$column] ?? null;

        if (! is_string($raw)) {
            return [];
        }

        /** @var array<string, mixed>|null $payload */
        $payload = json_decode($raw, true);

        return is_array($payload) ? $payload : [];
    }
}
