<?php

namespace App\Content;

use App\Enums\CefrLevel;

/**
 * The content files flattened into normalised rows, one list per table, in
 * file order. Parents are implied by nesting and positions by array order, so
 * a row here has exactly what the database needs and nothing it must not
 * have. Build it from files that already passed their schema.
 */
final class ContentTree
{
    /** @var array<string, list<ContentRow>> */
    private array $rows = [];

    private function __construct()
    {
        foreach (ContentTables::order() as $table) {
            $this->rows[$table] = [];
        }
    }

    /**
     * @param  list<ContentFile>  $files  schema-valid files in import order
     */
    public static function fromFiles(array $files): self
    {
        $tree = new self;

        $levelKeys = [];

        foreach ($files as $file) {
            if ($file->kind === 'level' && $file->folder !== null && is_array($file->data) && is_string($file->data['key'] ?? null)) {
                $levelKeys[$file->folder] = $file->data['key'];
            }
        }

        $unitCounts = [];

        foreach ($files as $file) {
            if (! is_array($file->data)) {
                continue;
            }

            match ($file->kind) {
                'level' => $tree->addLevel($file),
                'unit' => $tree->addUnit($file, $levelKeys[$file->folder ?? ''] ?? null, $unitCounts),
                'exam' => $tree->addExam($file, $file->placement ? null : ($levelKeys[$file->folder ?? ''] ?? null)),
                'passage' => $tree->addPassage($file),
                default => null,
            };
        }

        return $tree;
    }

    /**
     * @return list<ContentRow>
     */
    public function rows(string $table): array
    {
        return $this->rows[$table] ?? [];
    }

    /**
     * Every row, tables in write order.
     *
     * @return list<ContentRow>
     */
    public function all(): array
    {
        return array_merge(...array_values($this->rows));
    }

    /**
     * The keys of a table.
     *
     * @return list<string>
     */
    public function keys(string $table): array
    {
        return array_map(fn (ContentRow $row): string => $row->key, $this->rows($table));
    }

    private function add(ContentRow $row): void
    {
        $this->rows[$row->table][] = $row;
    }

    private function addLevel(ContentFile $file): void
    {
        /** @var array<string, mixed> $data */
        $data = $file->data;
        $level = CefrLevel::tryFrom((string) ($data['code'] ?? ''));

        $this->add(new ContentRow(
            ContentTables::LEVELS,
            (string) $data['key'],
            null,
            $level?->position(),
            [
                'code' => $data['code'] ?? null,
                'title' => $data['title'] ?? null,
                'pass_threshold' => $data['pass_threshold'] ?? null,
            ],
            null,
            $file->path,
            '',
        ));
    }

    private function addPassage(ContentFile $file): void
    {
        /** @var array<string, mixed> $data */
        $data = $file->data;

        $this->add(new ContentRow(
            ContentTables::PASSAGES,
            (string) $data['key'],
            null,
            null,
            [
                'title' => $data['title'] ?? null,
                'body' => $data['body'] ?? null,
                'transcript' => $data['transcript'] ?? null,
                'audio_path' => $data['audio_path'] ?? null,
            ],
            null,
            $file->path,
            '',
        ));
    }

    /**
     * @param  array<string, int>  $unitCounts  units seen so far by level folder
     */
    private function addUnit(ContentFile $file, ?string $levelKey, array &$unitCounts): void
    {
        /** @var array<string, mixed> $data */
        $data = $file->data;
        $folder = (string) $file->folder;
        $unitCounts[$folder] = ($unitCounts[$folder] ?? 0) + 1;
        $unitKey = (string) $data['key'];

        $this->add(new ContentRow(
            ContentTables::UNITS,
            $unitKey,
            $levelKey,
            $unitCounts[$folder],
            ['title' => $data['title'] ?? null],
            null,
            $file->path,
            '',
        ));

        foreach ($this->items($data, 'lessons') as $index => $lesson) {
            $this->addLesson($file, $unitKey, $lesson, $index);
        }
    }

    /**
     * @param  array<string, mixed>  $lesson
     */
    private function addLesson(ContentFile $file, string $unitKey, array $lesson, int $index): void
    {
        $pointer = "/lessons/{$index}";
        $lessonKey = (string) $lesson['key'];

        $this->add(new ContentRow(
            ContentTables::LESSONS,
            $lessonKey,
            $unitKey,
            $index + 1,
            [
                'type' => $lesson['type'] ?? null,
                'title' => $lesson['title'] ?? null,
                'theory' => $lesson['theory'] ?? null,
            ],
            null,
            $file->path,
            $pointer,
        ));

        foreach ($this->items($lesson, 'vocabulary') as $position => $item) {
            $this->add(new ContentRow(
                ContentTables::VOCABULARY_ITEMS,
                (string) $item['key'],
                $lessonKey,
                $position + 1,
                [
                    'term' => $item['term'] ?? null,
                    'kind' => $item['kind'] ?? null,
                    'meaning' => $item['meaning'] ?? null,
                    'example' => $item['example'] ?? null,
                ],
                null,
                $file->path,
                "{$pointer}/vocabulary/{$position}",
            ));
        }

        foreach ($this->items($lesson, 'exercises') as $position => $exercise) {
            $this->add(new ContentRow(
                ContentTables::EXERCISES,
                (string) $exercise['key'],
                $lessonKey,
                $position + 1,
                [
                    'type' => $exercise['type'] ?? null,
                    'skill' => $exercise['skill'] ?? null,
                    'prompt' => $exercise['prompt'] ?? null,
                    'content' => $exercise['content'] ?? [],
                    'solution' => $exercise['solution'] ?? [],
                    'explanation' => $exercise['explanation'] ?? null,
                    'audio_path' => $exercise['audio_path'] ?? null,
                ],
                is_string($exercise['passage'] ?? null) ? $exercise['passage'] : null,
                $file->path,
                "{$pointer}/exercises/{$position}",
            ));
        }
    }

    private function addExam(ContentFile $file, ?string $levelKey): void
    {
        /** @var array<string, mixed> $data */
        $data = $file->data;
        $examKey = (string) $data['key'];

        $this->add(new ContentRow(
            ContentTables::EXAMS,
            $examKey,
            $levelKey,
            null,
            [
                'kind' => $file->placement ? 'placement' : 'level',
                'title' => $data['title'] ?? null,
                'time_limit_minutes' => $data['time_limit_minutes'] ?? null,
                'pass_score' => $data['pass_score'] ?? null,
                'max_attempts' => $data['max_attempts'] ?? null,
                'cooldown_hours' => $data['cooldown_hours'] ?? null,
            ],
            null,
            $file->path,
            '',
        ));

        foreach ($this->items($data, 'sections') as $index => $section) {
            $pointer = "/sections/{$index}";
            $sectionKey = (string) $section['key'];

            $this->add(new ContentRow(
                ContentTables::EXAM_SECTIONS,
                $sectionKey,
                $examKey,
                $index + 1,
                [
                    'skill' => $section['skill'] ?? null,
                    'points' => $section['points'] ?? null,
                    'grading_mode' => $section['grading_mode'] ?? null,
                    'questions_per_attempt' => $section['questions_per_attempt'] ?? null,
                ],
                null,
                $file->path,
                $pointer,
            ));

            foreach ($this->items($section, 'questions') as $position => $question) {
                $this->add(new ContentRow(
                    ContentTables::EXAM_QUESTIONS,
                    (string) $question['key'],
                    $sectionKey,
                    null,
                    [
                        'type' => $question['type'] ?? null,
                        'content' => $question['content'] ?? [],
                        'solution' => $question['solution'] ?? [],
                        'explanation' => $question['explanation'] ?? null,
                        'points' => $question['points'] ?? null,
                    ],
                    is_string($question['passage'] ?? null) ? $question['passage'] : null,
                    $file->path,
                    "{$pointer}/questions/{$position}",
                ));
            }
        }
    }

    /**
     * The rows listed under a member, or none when it is absent.
     *
     * @param  array<string, mixed>  $parent
     * @return array<int, array<string, mixed>>
     */
    private function items(array $parent, string $member): array
    {
        $items = $parent[$member] ?? [];

        return is_array($items) ? array_values(array_filter($items, is_array(...))) : [];
    }
}
