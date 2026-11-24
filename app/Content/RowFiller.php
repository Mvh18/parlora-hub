<?php

namespace App\Content;

use App\Models\Concerns\HasContentTranslations;
use Illuminate\Database\Eloquent\Model;

/**
 * Puts a content row's values on a model, the same way for the planner (which
 * only asks whether anything changed) and the importer (which saves).
 */
final class RowFiller
{
    /**
     * @param  int|null  $parentId  the id of the parent row; null for roots and the placement exam
     * @param  int|null  $passageId  the id of the referenced passage, for tables that have one
     * @param  int|null  $position  the position to store, for positioned tables
     */
    public static function fill(Model $model, ContentTable $table, ContentRow $row, ?int $parentId, ?int $passageId, ?int $position): void
    {
        $attributes = $model->exists ? [] : ['key' => $row->key];

        if ($table->parentColumn !== null) {
            $attributes[$table->parentColumn] = $parentId;
        }

        // Row attributes keep their file order, so `type` is set before the payloads it types.
        $attributes += $row->attributes;

        if ($table->hasPassage) {
            $attributes['passage_id'] = $passageId;
        }

        if ($table->positioned) {
            $attributes['position'] = $position;
        }

        foreach ($attributes as $attribute => $value) {
            if ($value === null && self::isTranslatable($model, $attribute)) {
                // An absent translation clears the column. Neither the translatable setter
                // (`{"en": null}`) nor forgetAllTranslations() (unsets the attribute, so the
                // save skips it) does that.
                if (($model->getAttributes()[$attribute] ?? null) !== null) {
                    $model->setRawAttributes([$attribute => null] + $model->getAttributes());
                }

                unset($attributes[$attribute]);
            }
        }

        $model->fill($attributes);
    }

    /**
     * Whether saving the model would change anything. JSON columns (payloads,
     * translations) are compared by value, because PostgreSQL returns jsonb
     * with its own key order and spacing.
     */
    public static function hasChanges(Model $model): bool
    {
        if (! $model->exists) {
            return true;
        }

        $current = $model->getAttributes();

        foreach (array_keys($model->getDirty()) as $attribute) {
            if (! self::equivalent($current[$attribute] ?? null, $model->getRawOriginal($attribute))) {
                return true;
            }
        }

        return false;
    }

    private static function equivalent(mixed $new, mixed $old): bool
    {
        if ($new === $old) {
            return true;
        }

        if (is_numeric($new) && is_numeric($old)) {
            return (string) $new === (string) $old;
        }

        $newJson = self::jsonArray($new);
        $oldJson = self::jsonArray($old);

        return $newJson !== null && $oldJson !== null && self::canonical($newJson) === self::canonical($oldJson);
    }

    /**
     * A JSON column's value as an array, or null when the value is not JSON.
     * Null counts as empty: a cleared translation may be stored as null or,
     * after a panel save, as `[]`.
     *
     * @return array<array-key, mixed>|null
     */
    private static function jsonArray(mixed $value): ?array
    {
        if ($value === null) {
            return [];
        }

        $decoded = is_string($value) ? json_decode($value, true) : null;

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Objects with their members sorted by name, lists left in order.
     *
     * @param  array<array-key, mixed>  $value
     * @return array<array-key, mixed>
     */
    private static function canonical(array $value): array
    {
        if (! array_is_list($value)) {
            ksort($value);
        }

        return array_map(fn (mixed $item): mixed => is_array($item) ? self::canonical($item) : $item, $value);
    }

    private static function isTranslatable(Model $model, string $attribute): bool
    {
        return in_array(HasContentTranslations::class, class_uses_recursive($model), true)
            && $model->isTranslatableAttribute($attribute); // @phpstan-ignore method.notFound
    }
}
