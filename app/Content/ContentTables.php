<?php

namespace App\Content;

use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\Lesson;
use App\Models\Level;
use App\Models\Passage;
use App\Models\Unit;
use App\Models\VocabularyItem;
use InvalidArgumentException;

/**
 * The single map of content tables: which model each one is, who its parent
 * is, which ones have positions, and the order in which a writer must visit
 * them (parents before children). The loader, the validator, the importer and
 * the "Copy as JSON" exporter all read it, so they cannot disagree.
 */
final class ContentTables
{
    public const LEVELS = 'levels';

    public const PASSAGES = 'passages';

    public const UNITS = 'units';

    public const LESSONS = 'lessons';

    public const VOCABULARY_ITEMS = 'vocabulary_items';

    public const EXERCISES = 'exercises';

    public const EXAMS = 'exams';

    public const EXAM_SECTIONS = 'exam_sections';

    public const EXAM_QUESTIONS = 'exam_questions';

    /** @var array<string, ContentTable>|null */
    private static ?array $tables = null;

    /**
     * Every table in write order: parents before children.
     *
     * @return array<string, ContentTable>
     */
    public static function all(): array
    {
        return self::$tables ??= self::build();
    }

    public static function get(string $name): ContentTable
    {
        return self::all()[$name] ?? throw new InvalidArgumentException("Unknown content table [{$name}].");
    }

    /**
     * The table names in write order.
     *
     * @return list<string>
     */
    public static function order(): array
    {
        return array_keys(self::all());
    }

    /**
     * The tables whose rows carry a `position`.
     *
     * @return list<string>
     */
    public static function positioned(): array
    {
        return array_keys(array_filter(self::all(), fn (ContentTable $table): bool => $table->positioned));
    }

    /**
     * The table of the model class, for a row that came from the database.
     */
    public static function forModel(string $model): ContentTable
    {
        foreach (self::all() as $table) {
            if ($table->model === $model) {
                return $table;
            }
        }

        throw new InvalidArgumentException("[{$model}] is not a content model.");
    }

    /** @return array<string, ContentTable> */
    private static function build(): array
    {
        $tables = [
            new ContentTable(self::LEVELS, Level::class, null, null, null, null, positioned: true),
            new ContentTable(self::PASSAGES, Passage::class, null, null, null, null, positioned: false),
            new ContentTable(self::UNITS, Unit::class, self::LEVELS, 'level_id', 'level', 'units', positioned: true),
            new ContentTable(self::LESSONS, Lesson::class, self::UNITS, 'unit_id', 'unit', 'lessons', positioned: true),
            new ContentTable(self::VOCABULARY_ITEMS, VocabularyItem::class, self::LESSONS, 'lesson_id', 'lesson', 'vocabulary', positioned: true),
            new ContentTable(self::EXERCISES, Exercise::class, self::LESSONS, 'lesson_id', 'lesson', 'exercises', positioned: true, hasPassage: true),
            // Exams hang from a level only when they are level exams; the placement exam has no parent.
            new ContentTable(self::EXAMS, Exam::class, self::LEVELS, 'level_id', 'level', null, positioned: false),
            new ContentTable(self::EXAM_SECTIONS, ExamSection::class, self::EXAMS, 'exam_id', 'exam', 'sections', positioned: true),
            // Exam questions form a bank: no position column.
            new ContentTable(self::EXAM_QUESTIONS, ExamQuestion::class, self::EXAM_SECTIONS, 'exam_section_id', 'section', 'questions', positioned: false, hasPassage: true),
        ];

        $byName = [];

        foreach ($tables as $table) {
            $byName[$table->name] = $table;
        }

        return $byName;
    }
}
