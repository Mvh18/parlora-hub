<?php

namespace App\Content;

use App\Data\Exercises\ExercisePayloads;
use App\Enums\ExerciseType;
use App\Exceptions\InvalidExercisePayload;
use App\Rules\AudioPath;
use App\Rules\TranslatedText;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Checks a content tree without touching the database, in two layers:
 *
 * 1. **Schema**, per file. A file that fails here is not checked further,
 *    but the other files still are.
 * 2. **Semantics**, across the files that passed: unique keys, passage
 *    references, the level folder matching its code, exercise payloads,
 *    translation locales and audio paths.
 *
 * Every problem lands in one report with its file and JSON pointer, so an
 * author sees all of them at once. `content:import` runs this same validator
 * first.
 */
final class ContentValidator
{
    /** The translatable attribute of each table that carries one. */
    private const TRANSLATIONS = [
        ContentTables::LESSONS => ['theory'],
        ContentTables::VOCABULARY_ITEMS => ['meaning'],
        ContentTables::EXERCISES => ['explanation'],
        ContentTables::EXAM_QUESTIONS => ['explanation'],
    ];

    public function __construct(
        private readonly ContentLoader $loader = new ContentLoader,
        private readonly SchemaValidator $schema = new SchemaValidator,
    ) {}

    public function validate(string $root): ValidationReport
    {
        $report = new ValidationReport;

        $files = $this->loader->load($root, $report);
        $report->files = count($files);

        $valid = $this->schemaLayer($files, $report);
        $this->levelFolders($files, $report);

        $tree = ContentTree::fromFiles($valid);
        $report->tree = $tree;

        $this->semanticLayer($valid, $tree, $report);
        $this->unitFileNames($files, $report);

        return $report;
    }

    /**
     * @param  list<ContentFile>  $files
     * @return list<ContentFile> the files that passed their schema
     */
    private function schemaLayer(array $files, ValidationReport $report): array
    {
        $valid = [];

        foreach ($files as $file) {
            $errors = $this->schema->validate($file->kind, $file->document);

            foreach ($errors as $error) {
                $report->error($file->path, $error->pointer, $error->message);
            }

            if ($errors === []) {
                $valid[] = $file;
            }
        }

        return $valid;
    }

    /**
     * A unit or level exam needs the `level.json` of its folder.
     *
     * @param  list<ContentFile>  $files
     */
    private function levelFolders(array $files, ValidationReport $report): void
    {
        $withLevel = [];

        foreach ($files as $file) {
            if ($file->kind === 'level' && $file->folder !== null) {
                $withLevel[$file->folder] = true;
            }
        }

        foreach ($files as $file) {
            if ($file->folder !== null && $file->kind !== 'level' && ! isset($withLevel[$file->folder])) {
                $report->error($file->path, '', "The folder levels/{$file->folder} has no level.json.");
            }
        }
    }

    /**
     * @param  list<ContentFile>  $valid
     */
    private function semanticLayer(array $valid, ContentTree $tree, ValidationReport $report): void
    {
        $this->levelFolderMatchesCode($valid, $report);
        $this->uniqueKeys($tree, $report);
        $this->passageReferences($tree, $report);

        foreach ($tree->all() as $row) {
            $this->payload($row, $report);
            $this->translations($row, $report);
            $this->audio($row, $report);
        }
    }

    /**
     * @param  list<ContentFile>  $valid
     */
    private function levelFolderMatchesCode(array $valid, ValidationReport $report): void
    {
        foreach ($valid as $file) {
            if ($file->kind !== 'level' || ! is_array($file->data)) {
                continue;
            }

            $code = (string) ($file->data['code'] ?? '');

            if (strtolower($code) !== $file->folder) {
                $report->error($file->path, '/code', "The level folder is `{$file->folder}` but the code is `{$code}`: the folder must be the code in lowercase.");
            }
        }
    }

    private function uniqueKeys(ContentTree $tree, ValidationReport $report): void
    {
        foreach (ContentTables::order() as $table) {
            /** @var array<string, ContentRow> $first */
            $first = [];
            /** @var array<string, true> $reported */
            $reported = [];

            foreach ($tree->rows($table) as $row) {
                $other = $first[$row->key] ?? null;

                if ($other === null) {
                    $first[$row->key] = $row;

                    continue;
                }

                if (! isset($reported[$row->key])) {
                    $reported[$row->key] = true;
                    $report->error($other->file, $other->pointer, "The key `{$row->key}` is also used at ".self::location($row).'.');
                }

                $report->error($row->file, $row->pointer, "The key `{$row->key}` is already used at ".self::location($other).'.');
            }
        }
    }

    private function passageReferences(ContentTree $tree, ValidationReport $report): void
    {
        $passages = array_flip($tree->keys(ContentTables::PASSAGES));

        foreach ($tree->all() as $row) {
            if ($row->passageKey !== null && ! isset($passages[$row->passageKey])) {
                $report->error($row->file, $row->pointer.'/passage', "There is no passage with the key `{$row->passageKey}`.");
            }
        }
    }

    private function payload(ContentRow $row, ValidationReport $report): void
    {
        if (! in_array($row->table, [ContentTables::EXERCISES, ContentTables::EXAM_QUESTIONS], true)) {
            return;
        }

        $type = ExerciseType::tryFrom((string) ($row->attributes['type'] ?? ''));
        $content = $row->attributes['content'] ?? null;
        $solution = $row->attributes['solution'] ?? null;

        if ($type === null || ! is_array($content) || ! is_array($solution)) {
            return;
        }

        try {
            ExercisePayloads::validate($type, $content, $solution);
        } catch (InvalidExercisePayload $exception) {
            foreach ($exception->errors() as $path => $messages) {
                $report->error($row->file, $row->pointer.self::pointerFromPath((string) $path), (string) ($messages[0] ?? $exception->getMessage()));
            }
        }
    }

    private function translations(ContentRow $row, ValidationReport $report): void
    {
        foreach (self::TRANSLATIONS[$row->table] ?? [] as $attribute) {
            $value = $row->attributes[$attribute] ?? null;

            if ($value === null) {
                continue;
            }

            foreach (TranslatedText::errors($value, $attribute) as $locale => $message) {
                $suffix = $locale === '' ? '' : '/'.self::escape((string) $locale);
                $report->error($row->file, "{$row->pointer}/{$attribute}{$suffix}", $message);
            }
        }
    }

    private function audio(ContentRow $row, ValidationReport $report): void
    {
        $audio = $row->attributes['audio_path'] ?? null;

        if ($audio === null) {
            return;
        }

        $pointer = $row->pointer.'/audio_path';
        $error = AudioPath::error($audio);

        if ($error !== null) {
            $report->error($row->file, $pointer, $error);

            return;
        }

        try {
            $missing = ! Storage::disk('content-audio')->exists((string) $audio);
        } catch (Throwable) {
            // The disk is not reachable: existence is a warning, so there is nothing to say.
            return;
        }

        if ($missing) {
            $report->warning($row->file, $pointer, "The audio file `{$audio}` was not found on the content-audio disk.");
        }
    }

    /**
     * @param  list<ContentFile>  $files
     */
    private function unitFileNames(array $files, ValidationReport $report): void
    {
        foreach ($files as $file) {
            if ($file->kind === 'unit' && preg_match('/^\d+-/', $file->name()) !== 1) {
                $report->warning($file->path, '', 'The file name has no numeric prefix such as `01-`; units are ordered by file name.');
            }
        }
    }

    private static function location(ContentRow $row): string
    {
        return $row->file.' '.($row->pointer === '' ? '/' : $row->pointer);
    }

    /** `content.options.1.text` becomes `/content/options/1/text`. */
    private static function pointerFromPath(string $path): string
    {
        return '/'.implode('/', array_map(self::escape(...), explode('.', $path)));
    }

    private static function escape(string $segment): string
    {
        return str_replace(['~', '/'], ['~0', '~1'], $segment);
    }
}
