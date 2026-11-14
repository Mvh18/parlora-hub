<?php

namespace App\Casts;

use App\Enums\ExerciseType;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Spatie\LaravelData\Data;

/**
 * Casts an exercise `content` or `solution` column to the data class that the
 * row's `type` selects (`ExercisePayloadCast:content` / `:solution`).
 *
 * Plain JSON goes to the database: the type column is the discriminator, so no
 * PHP class names are ever stored. Arrays are stored as given, without being
 * hydrated, so an invalid payload reaches the model's `saving` hook and is
 * reported there with `content.*` / `solution.*` paths.
 *
 * @implements CastsAttributes<Data, Data|array<string, mixed>>
 */
final class ExercisePayloadCast implements CastsAttributes
{
    public const CONTENT = 'content';

    public const SOLUTION = 'solution';

    public function __construct(private readonly string $side = self::CONTENT)
    {
        if (! in_array($side, [self::CONTENT, self::SOLUTION], true)) {
            throw new InvalidArgumentException("Unknown payload side [{$side}].");
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Data
    {
        if ($value === null) {
            return null;
        }

        $type = ExerciseType::from($this->typeOf($attributes['type']));
        $class = $this->side === self::CONTENT ? $type->contentClass() : $type->solutionClass();

        /** @var array<string, mixed> $payload */
        $payload = json_decode((string) $value, true, flags: JSON_THROW_ON_ERROR);

        return $class::from($payload);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        $payload = $value instanceof Data ? $value->toArray() : $value;

        if (! is_array($payload)) {
            throw new InvalidArgumentException("The {$key} of an exercise must be a data object or an array.");
        }

        return json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }

    private function typeOf(mixed $type): string
    {
        return $type instanceof ExerciseType ? $type->value : (string) $type;
    }
}
