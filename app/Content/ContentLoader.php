<?php

namespace App\Content;

use App\Enums\CefrLevel;
use FilesystemIterator;
use JsonException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Reads the content files from disk: only `*.json` files under the content
 * root, by real path. Anything that escapes the root (a symlink to a file or
 * folder outside it) is reported and never read. Files are size-capped and
 * decoded with a depth limit, and the result comes back in import order:
 * levels by CEFR order, units by file name.
 */
final class ContentLoader
{
    /** The largest content file that is read, in bytes. */
    public const MAX_FILE_BYTES = 1_048_576;

    /** The deepest JSON nesting that is decoded. */
    public const MAX_DEPTH = 64;

    private const LAYOUT = 'levels/<code>/level.json, levels/<code>/units/*.json, levels/<code>/exam.json, passages/**/*.json or exams/placement.json';

    /**
     * @return list<ContentFile> the readable files; problems go to the report
     */
    public function load(string $root, ValidationReport $report): array
    {
        $real = realpath($root);

        if ($real === false || ! is_dir($real)) {
            $report->error('', '', 'The content directory does not exist.');

            return [];
        }

        $files = [];

        foreach ($this->jsonEntries($real) as $entry) {
            $file = $this->read($real, $entry, $report);

            if ($file !== null) {
                $files[] = $file;
            }
        }

        usort($files, $this->importOrder(...));

        return $files;
    }

    /** @return iterable<SplFileInfo> */
    private function jsonEntries(string $root): iterable
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY,
        );

        foreach ($iterator as $entry) {
            if ($entry instanceof SplFileInfo) {
                yield $entry;
            }
        }
    }

    private function read(string $root, SplFileInfo $entry, ValidationReport $report): ?ContentFile
    {
        $relative = str_replace('\\', '/', substr($entry->getPathname(), strlen($root) + 1));

        if ($entry->isLink() && is_dir($entry->getPathname())) {
            $report->error($relative, '', 'Symlinked folders are not followed.');

            return null;
        }

        if ($entry->getExtension() !== 'json') {
            return null;
        }

        $path = $entry->getPathname();

        if ($entry->isLink()) {
            $target = realpath($path);

            if ($target === false || ! $this->inside($target, $root)) {
                $report->error($relative, '', 'This symlink points outside the content root; its target was not read.');

                return null;
            }

            $path = $target;
        }

        $classified = $this->classify($relative);

        if ($classified === null) {
            $report->error($relative, '', 'This file does not belong to the content layout ('.self::LAYOUT.').');

            return null;
        }

        $size = filesize($path);

        if ($size === false || $size > self::MAX_FILE_BYTES) {
            $report->error($relative, '', sprintf('The file is larger than %d KiB.', self::MAX_FILE_BYTES / 1024));

            return null;
        }

        $json = file_get_contents($path);

        if ($json === false) {
            $report->error($relative, '', 'The file could not be read.');

            return null;
        }

        try {
            $document = json_decode($json, false, self::MAX_DEPTH, JSON_THROW_ON_ERROR);
            $data = json_decode($json, true, self::MAX_DEPTH, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            $report->error($relative, '', 'Invalid JSON: '.$exception->getMessage().'.');

            return null;
        }

        return new ContentFile($relative, $classified['kind'], $classified['folder'], $classified['placement'], $document, $data);
    }

    /** @return array{kind: string, folder: string|null, placement: bool}|null */
    private function classify(string $relative): ?array
    {
        if (preg_match('#^levels/([^/]+)/level\.json$#', $relative, $match) === 1) {
            return ['kind' => 'level', 'folder' => $match[1], 'placement' => false];
        }

        if (preg_match('#^levels/([^/]+)/exam\.json$#', $relative, $match) === 1) {
            return ['kind' => 'exam', 'folder' => $match[1], 'placement' => false];
        }

        if (preg_match('#^levels/([^/]+)/units/[^/]+\.json$#', $relative, $match) === 1) {
            return ['kind' => 'unit', 'folder' => $match[1], 'placement' => false];
        }

        if ($relative === 'exams/placement.json') {
            return ['kind' => 'exam', 'folder' => null, 'placement' => true];
        }

        if (str_starts_with($relative, 'passages/')) {
            return ['kind' => 'passage', 'folder' => null, 'placement' => false];
        }

        return null;
    }

    private function inside(string $path, string $root): bool
    {
        $normalised = str_replace('\\', '/', $path);
        $base = rtrim(str_replace('\\', '/', $root), '/').'/';

        return str_starts_with($normalised, $base);
    }

    /** Levels in CEFR order, each with its level file, units by file name and exam; then the rest by path. */
    private function importOrder(ContentFile $a, ContentFile $b): int
    {
        return [$this->levelRank($a), $a->folder ?? '', $this->kindRank($a), $a->name()]
            <=> [$this->levelRank($b), $b->folder ?? '', $this->kindRank($b), $b->name()]
            ?: strcmp($a->path, $b->path);
    }

    private function levelRank(ContentFile $file): int
    {
        if ($file->folder === null) {
            return 1000;
        }

        $level = CefrLevel::tryFrom(strtoupper($file->folder));

        return $level === null ? 999 : $level->position();
    }

    private function kindRank(ContentFile $file): int
    {
        return match ($file->kind) {
            'level' => 0,
            'unit' => 1,
            'exam' => 2,
            default => 3,
        };
    }
}
