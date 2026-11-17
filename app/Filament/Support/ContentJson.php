<?php

namespace App\Filament\Support;

use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A content row as key-based JSON, for "Copy as JSON" in the admin panel: the
 * shape an author pastes into `content/` after a touch-up.
 *
 * The row's own attributes come first under `key`; translations are
 * `{locale: text}` objects and payloads are exported as stored; parents and
 * the passage appear by key (`"lesson": "a1.u01.l02"`). Database ids,
 * foreign-key ids, timestamps and `archived_at` are left out. Hidden
 * attributes (solutions, transcripts) are included: only panel admins see it.
 */
final class ContentJson
{
    /** Foreign key => the relation that names the parent by key. */
    private const PARENTS = [
        'level_id' => 'level',
        'unit_id' => 'unit',
        'lesson_id' => 'lesson',
        'exam_id' => 'exam',
        'exam_section_id' => 'section',
        'passage_id' => 'passage',
    ];

    private const OMITTED = ['id', 'created_at', 'updated_at', 'archived_at'];

    private const PAYLOADS = ['content', 'solution'];

    /**
     * @return array<string, mixed>
     */
    public static function for(Model $record): array
    {
        $json = ['key' => $record->getAttribute('key')];

        foreach (array_keys($record->getAttributes()) as $attribute) {
            if ($attribute === 'key' || in_array($attribute, self::OMITTED, true)) {
                continue;
            }

            if (array_key_exists($attribute, self::PARENTS)) {
                $relation = self::PARENTS[$attribute];
                $json[$relation] = self::parentKey($record, $relation);

                continue;
            }

            $json[$attribute] = self::value($record, $attribute);
        }

        return $json;
    }

    public static function encode(Model $record): string
    {
        return json_encode(self::for($record), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    private static function parentKey(Model $record, string $relation): ?string
    {
        /** @var BelongsTo<Model, Model> $belongsTo */
        $belongsTo = $record->{$relation}();
        $parent = $belongsTo->first();

        $key = $parent?->getAttribute('key');

        return is_string($key) ? $key : null;
    }

    private static function value(Model $record, string $attribute): mixed
    {
        if (method_exists($record, 'isTranslatableAttribute')
            && method_exists($record, 'getTranslations')
            && $record->isTranslatableAttribute($attribute)) {
            return $record->getTranslations($attribute);
        }

        if (in_array($attribute, self::PAYLOADS, true)) {
            $raw = $record->getRawOriginal($attribute);

            return is_string($raw) ? json_decode($raw, true) : null;
        }

        $value = $record->getAttribute($attribute);

        return $value instanceof BackedEnum ? $value->value : $value;
    }
}
