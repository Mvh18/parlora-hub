<?php

namespace App\Filament\Support;

use App\Content\ContentTable;
use App\Content\ContentTables;
use App\Models\Passage;
use BackedEnum;
use Illuminate\Database\Eloquent\Model;

/**
 * A content row as a content-file fragment, for "Copy as JSON" in the admin
 * panel: the shape the importer reads, so an author can paste it straight
 * into `content/`.
 *
 * The fragment starts with `key`, then the row's members in file order
 * (`ContentTable::$members`), its passage by key, and its active children
 * nested in order (a lesson's vocabulary and exercises, a unit's lessons, an
 * exam's sections and their questions; a level leaves out its units, which
 * live in their own files). Translations are `{locale: text}` objects and
 * payloads are exported as stored. Members without a value are left out, as
 * an author would leave them out. There are no ids, parent keys, positions,
 * timestamps or `archived_at`: nesting and array order say all of that.
 * Hidden attributes (solutions, transcripts) are included: only panel admins
 * see it.
 */
final class ContentJson
{
    private const PAYLOADS = ['content', 'solution'];

    /**
     * @return array<string, mixed>
     */
    public static function for(Model $record): array
    {
        return self::fragment($record, ContentTables::forModel($record::class));
    }

    public static function encode(Model $record): string
    {
        return json_encode(self::for($record), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string, mixed>
     */
    private static function fragment(Model $record, ContentTable $table): array
    {
        $json = ['key' => $record->getAttribute('key')];

        foreach ($table->members as $member) {
            $value = self::value($record, $member);

            if (in_array($member, self::PAYLOADS, true)) {
                // Payloads are required members, and an empty one is still an object.
                $json[$member] = $value === [] || $value === null ? (object) [] : $value;
            } elseif ($value !== null && $value !== []) {
                $json[$member] = $value;
            }
        }

        if ($table->hasPassage && $record->getAttribute('passage_id') !== null) {
            $json['passage'] = Passage::query()->whereKey($record->getAttribute('passage_id'))->value('key');
        }

        foreach ($table->children() as $child) {
            $rows = $child->model::query()
                // A child table always has a parent column.
                ->where((string) $child->parentColumn, $record->getKey())
                ->whereNull('archived_at')
                ->orderBy($child->positioned ? 'position' : 'id')
                ->get();

            if ($rows->isNotEmpty()) {
                $json[(string) $child->childrenMember] = $rows->map(fn (Model $row): array => self::fragment($row, $child))->all();
            }
        }

        return $json;
    }

    private static function value(Model $record, string $member): mixed
    {
        if (method_exists($record, 'isTranslatableAttribute')
            && method_exists($record, 'getTranslations')
            && $record->isTranslatableAttribute($member)) {
            return $record->getTranslations($member);
        }

        if (in_array($member, self::PAYLOADS, true)) {
            $raw = $record->getRawOriginal($member);

            return is_string($raw) ? json_decode($raw, true) : null;
        }

        $value = $record->getAttribute($member);

        return $value instanceof BackedEnum ? $value->value : $value;
    }
}
