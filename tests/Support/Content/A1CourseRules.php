<?php

namespace Tests\Support\Content;

/**
 * The A1 course rules that a schema cannot express: the unit template, the
 * per-lesson minimums, the bilingual rule and the audio references. Each
 * method returns the violations as `<file> <pointer>: <message>` lines, so a
 * failing test tells an author exactly where to look.
 *
 * The rules read decoded content files (arrays), not database rows, so they
 * run in CI without importing anything.
 */
final class A1CourseRules
{
    public const MIN_EXERCISES = 10;

    public const MIN_TYPES = 4;

    public const MIN_VOCABULARY = 12;

    public const MAX_PRODUCTION_TASKS = 2;

    public const UNITS = 8;

    /**
     * The D1 grammar spiral: each point comes back in later units. A unit
     * covers a point when one of its exercises uses it in its content or
     * solution (the text the learner reads or must produce).
     *
     * @var array<string, array{units: list<int>, pattern: string}>
     */
    public const SPIRAL = [
        'be' => ['units' => [2, 3, 8], 'pattern' => '/\b(am|is|are|was|were|isn\'t|aren\'t|wasn\'t|weren\'t)\b/'],
        'present simple' => ['units' => [5, 6], 'pattern' => '/\b(do|does|don\'t|doesn\'t)\b/'],
        'there is/are' => ['units' => [7], 'pattern' => '/\bthere (is|are|isn\'t|aren\'t|\{\{g\d+\}\})|\bthere\'s\b/'],
    ];

    /** The A1 exam settings (D6). */
    public const EXAM = [
        'key' => 'a1.exam',
        'time_limit_minutes' => 60,
        'pass_score' => 60,
        'max_attempts' => null,
        'cooldown_hours' => 24,
    ];

    /**
     * The A1 exam sections in order (D6): skill, points and grading mode.
     *
     * @var array<string, array{skill: string, points: int, grading_mode: string}>
     */
    public const EXAM_SECTIONS = [
        'a1.exam.uoe' => ['skill' => 'use_of_english', 'points' => 25, 'grading_mode' => 'auto'],
        'a1.exam.reading' => ['skill' => 'reading', 'points' => 20, 'grading_mode' => 'auto'],
        'a1.exam.listening' => ['skill' => 'listening', 'points' => 20, 'grading_mode' => 'auto'],
        'a1.exam.writing' => ['skill' => 'writing', 'points' => 20, 'grading_mode' => 'self_assessment'],
        'a1.exam.speaking' => ['skill' => 'speaking', 'points' => 15, 'grading_mode' => 'self_assessment'],
    ];

    public const MIN_UOE_PER_UNIT = 2;

    private const PRODUCTION_TYPES = ['writing', 'speaking'];

    /**
     * The lesson types of unit `$number`, in order: grammar, vocabulary, then
     * reading and speaking in odd units, listening and writing in even ones.
     *
     * @return list<string>
     */
    public static function lessonTypes(int $number): array
    {
        return $number % 2 === 1
            ? ['grammar', 'vocabulary', 'reading', 'speaking']
            : ['grammar', 'vocabulary', 'listening', 'writing'];
    }

    /**
     * @param  array<string, mixed>  $level
     * @return list<string>
     */
    public static function level(string $file, array $level): array
    {
        $violations = [];

        foreach (['key' => 'a1', 'code' => 'A1', 'pass_threshold' => 80] as $member => $expected) {
            if (($level[$member] ?? null) !== $expected) {
                $violations[] = self::issue($file, "/{$member}", 'expected '.json_encode($expected).', found '.json_encode($level[$member] ?? null));
            }
        }

        return $violations;
    }

    /**
     * @param  string  $file  the unit file, relative to the content root
     * @param  int  $number  the unit's 1-based position in file order
     * @param  array<string, mixed>  $unit
     * @return list<string>
     */
    public static function unit(string $file, int $number, array $unit): array
    {
        $violations = [];
        $unitKey = sprintf('a1.u%02d', $number);

        if (preg_match('/^'.sprintf('%02d', $number).'-[a-z0-9]+(?:-[a-z0-9]+)*\.json$/', basename($file)) !== 1) {
            $violations[] = self::issue($file, '', sprintf('unit %d must be named %02d-<slug>.json', $number, $number));
        }

        if (($unit['key'] ?? null) !== $unitKey) {
            $violations[] = self::issue($file, '/key', "expected `{$unitKey}`");
        }

        $lessons = self::list($unit['lessons'] ?? null);
        $types = self::lessonTypes($number);

        if (count($lessons) !== count($types)) {
            $violations[] = self::issue($file, '/lessons', sprintf('expected %d lessons, found %d', count($types), count($lessons)));
        }

        foreach ($lessons as $index => $lesson) {
            $violations = [...$violations, ...self::lesson($file, "/lessons/{$index}", sprintf('%s.l%02d', $unitKey, $index + 1), $types[$index] ?? null, $lesson)];
        }

        return $violations;
    }

    /**
     * The whole course: exactly eight units of four lessons, and the grammar
     * spiral covered in the units D1 names.
     *
     * @param  array<string, array<string, mixed>>  $units  unit files in order, relative path → decoded file
     * @return list<string>
     */
    public static function course(array $units): array
    {
        $violations = [];
        $lessons = array_sum(array_map(fn (array $unit): int => count(self::list($unit['lessons'] ?? null)), $units));

        if (count($units) !== self::UNITS) {
            $violations[] = self::issue('levels/a1/units', '', sprintf('expected %d units, found %d', self::UNITS, count($units)));
        }

        if ($lessons !== self::UNITS * 4) {
            $violations[] = self::issue('levels/a1/units', '', sprintf('expected %d lessons, found %d', self::UNITS * 4, $lessons));
        }

        $files = array_keys($units);

        foreach (self::SPIRAL as $point => ['units' => $numbers, 'pattern' => $pattern]) {
            foreach ($numbers as $number) {
                $file = $files[$number - 1] ?? null;

                if ($file === null) {
                    continue;
                }

                $text = '';

                foreach (self::list($units[$file]['lessons'] ?? null) as $lesson) {
                    foreach (self::list($lesson['exercises'] ?? null) as $exercise) {
                        $text .= ' '.json_encode([$exercise['content'] ?? null, $exercise['solution'] ?? null], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    }
                }

                if (preg_match($pattern, mb_strtolower($text)) !== 1) {
                    $violations[] = self::issue($file, '/lessons', sprintf('unit %d should bring back %s (grammar spiral)', $number, $point));
                }
            }
        }

        return $violations;
    }

    /**
     * The A1 exam (D6): settings, the five sections with their points and
     * grading modes, banks at least twice the attempt, a solution on every
     * question, bilingual explanations and rubrics, passages on reading and
     * listening questions, and Use of English questions for every unit. Use
     * of English keys carry their unit: `a1.exam.uoe.u03.q01`.
     *
     * @param  array<string, mixed>  $exam
     * @return list<string>
     */
    public static function exam(string $file, array $exam): array
    {
        $violations = [];

        foreach (self::EXAM as $member => $expected) {
            if (($exam[$member] ?? null) !== $expected) {
                $violations[] = self::issue($file, "/{$member}", 'expected '.json_encode($expected).', found '.json_encode($exam[$member] ?? null));
            }
        }

        $sections = self::list($exam['sections'] ?? null);
        $expectedKeys = array_keys(self::EXAM_SECTIONS);
        $foundKeys = array_map(fn (array $section): string => (string) ($section['key'] ?? ''), $sections);

        if ($foundKeys !== $expectedKeys) {
            $violations[] = self::issue($file, '/sections', 'expected sections '.implode(', ', $expectedKeys).', found '.implode(', ', $foundKeys));
        }

        $total = array_sum(array_map(fn (array $section): int => (int) ($section['points'] ?? 0), $sections));

        if ($total !== 100) {
            $violations[] = self::issue($file, '/sections', "section points total {$total}, expected 100");
        }

        $uoePerUnit = array_fill(1, self::UNITS, 0);

        foreach ($sections as $index => $section) {
            $pointer = "/sections/{$index}";
            $sectionKey = (string) ($section['key'] ?? '');

            foreach (self::EXAM_SECTIONS[$sectionKey] ?? [] as $member => $expected) {
                if (($section[$member] ?? null) !== $expected) {
                    $violations[] = self::issue($file, "{$pointer}/{$member}", "{$sectionKey}: expected ".json_encode($expected).', found '.json_encode($section[$member] ?? null));
                }
            }

            $questions = self::list($section['questions'] ?? null);
            $perAttempt = $section['questions_per_attempt'] ?? null;

            if (! is_int($perAttempt) || count($questions) < 2 * $perAttempt) {
                $violations[] = self::issue($file, "{$pointer}/questions", sprintf('%s has %d questions for %s per attempt; the bank must hold at least twice the attempt', $sectionKey, count($questions), json_encode($perAttempt)));
            }

            foreach ($questions as $q => $question) {
                $questionPointer = "{$pointer}/questions/{$q}";
                $questionKey = (string) ($question['key'] ?? '');
                $type = $question['type'] ?? null;

                if (! is_array($question['solution'] ?? null) || $question['solution'] === []) {
                    $violations[] = self::issue($file, "{$questionPointer}/solution", "{$questionKey} needs a solution");
                }

                if (in_array($type, self::PRODUCTION_TYPES, true)) {
                    $rubric = self::list($question['content']['rubric'] ?? null);

                    if ($rubric === []) {
                        $violations[] = self::issue($file, "{$questionPointer}/content/rubric", "{$questionKey} needs a rubric");
                    }

                    foreach ($rubric as $criterion => $row) {
                        $violations = [...$violations, ...self::bilingual($file, "{$questionPointer}/content/rubric/{$criterion}/description", $questionKey, $row['description'] ?? null)];
                    }
                } else {
                    $violations = [...$violations, ...self::bilingual($file, "{$questionPointer}/explanation", $questionKey, $question['explanation'] ?? null)];
                }

                if (in_array($sectionKey, ['a1.exam.reading', 'a1.exam.listening'], true) && ! is_string($question['passage'] ?? null)) {
                    $violations[] = self::issue($file, "{$questionPointer}/passage", "{$questionKey} needs a passage");
                }

                if ($sectionKey === 'a1.exam.uoe') {
                    if (preg_match('/^a1\.exam\.uoe\.u0([1-8])\.q\d{2}$/', $questionKey, $match) === 1) {
                        $uoePerUnit[(int) $match[1]]++;
                    } else {
                        $violations[] = self::issue($file, "{$questionPointer}/key", "{$questionKey}: Use of English keys follow a1.exam.uoe.uNN.qNN");
                    }
                }
            }
        }

        foreach ($uoePerUnit as $unit => $count) {
            if ($count < self::MIN_UOE_PER_UNIT) {
                $violations[] = self::issue($file, '/sections/0/questions', sprintf('unit %d has %d Use of English questions, at least %d needed', $unit, $count, self::MIN_UOE_PER_UNIT));
            }
        }

        return $violations;
    }

    /**
     * @param  array<string, mixed>  $passage
     * @return list<string>
     */
    public static function passage(string $file, array $passage): array
    {
        $violations = [];
        $key = (string) ($passage['key'] ?? '');
        $hasTranscript = is_string($passage['transcript'] ?? null) && trim($passage['transcript']) !== '';
        $audio = $passage['audio_path'] ?? null;

        if (str_contains($key, 'listening') && ! $hasTranscript) {
            $violations[] = self::issue($file, '/transcript', 'a listening passage needs a transcript');
        }

        if ($hasTranscript && ! is_string($audio)) {
            $violations[] = self::issue($file, '/audio_path', 'a passage with a transcript needs an audio_path');
        }

        if (is_string($audio) && ! str_starts_with($audio, 'a1/')) {
            $violations[] = self::issue($file, '/audio_path', 'A1 audio lives under a1/');
        }

        return $violations;
    }

    /**
     * @param  array<string, mixed>  $lesson
     * @return list<string>
     */
    private static function lesson(string $file, string $pointer, string $lessonKey, ?string $expectedType, array $lesson): array
    {
        $violations = [];
        $type = $lesson['type'] ?? null;

        if (($lesson['key'] ?? null) !== $lessonKey) {
            $violations[] = self::issue($file, "{$pointer}/key", "expected `{$lessonKey}`");
        }

        if ($expectedType !== null && $type !== $expectedType) {
            $violations[] = self::issue($file, "{$pointer}/type", "expected `{$expectedType}`, found `".json_encode($type).'`');
        }

        $violations = [...$violations, ...self::bilingual($file, "{$pointer}/theory", $lessonKey, $lesson['theory'] ?? null)];

        $vocabulary = self::list($lesson['vocabulary'] ?? null);

        if ($type === 'vocabulary' && count($vocabulary) < self::MIN_VOCABULARY) {
            $violations[] = self::issue($file, "{$pointer}/vocabulary", sprintf('%s has %d vocabulary items, at least %d needed', $lessonKey, count($vocabulary), self::MIN_VOCABULARY));
        }

        foreach ($vocabulary as $index => $item) {
            $itemKey = sprintf('%s.v%02d', $lessonKey, $index + 1);

            if (($item['key'] ?? null) !== $itemKey) {
                $violations[] = self::issue($file, "{$pointer}/vocabulary/{$index}/key", "expected `{$itemKey}`");
            }

            $violations = [...$violations, ...self::bilingual($file, "{$pointer}/vocabulary/{$index}/meaning", $itemKey, $item['meaning'] ?? null)];
        }

        $exercises = self::list($lesson['exercises'] ?? null);
        $exerciseTypes = array_unique(array_map(fn (array $exercise): string => (string) ($exercise['type'] ?? ''), $exercises));

        if (count($exercises) < self::MIN_EXERCISES) {
            $violations[] = self::issue($file, "{$pointer}/exercises", sprintf('%s has %d exercises, at least %d needed', $lessonKey, count($exercises), self::MIN_EXERCISES));
        }

        if (count($exerciseTypes) < self::MIN_TYPES) {
            $violations[] = self::issue($file, "{$pointer}/exercises", sprintf('%s uses %d exercise types, at least %d needed', $lessonKey, count($exerciseTypes), self::MIN_TYPES));
        }

        $production = 0;

        foreach ($exercises as $index => $exercise) {
            $exercisePointer = "{$pointer}/exercises/{$index}";
            $exerciseKey = sprintf('%s.ex%02d', $lessonKey, $index + 1);
            $exerciseType = $exercise['type'] ?? null;

            if (($exercise['key'] ?? null) !== $exerciseKey) {
                $violations[] = self::issue($file, "{$exercisePointer}/key", "expected `{$exerciseKey}`");
            }

            if (in_array($exerciseType, self::PRODUCTION_TYPES, true)) {
                $production++;

                if (! in_array($type, self::PRODUCTION_TYPES, true)) {
                    $violations[] = self::issue($file, "{$exercisePointer}/type", "{$exerciseKey}: {$exerciseType} tasks belong only in the production lesson");
                }

                $rubric = self::list($exercise['content']['rubric'] ?? null);

                if ($rubric === []) {
                    $violations[] = self::issue($file, "{$exercisePointer}/content/rubric", "{$exerciseKey} needs a rubric");
                }

                foreach ($rubric as $criterion => $row) {
                    $violations = [...$violations, ...self::bilingual($file, "{$exercisePointer}/content/rubric/{$criterion}/description", $exerciseKey, $row['description'] ?? null)];
                }
            } else {
                $violations = [...$violations, ...self::bilingual($file, "{$exercisePointer}/explanation", $exerciseKey, $exercise['explanation'] ?? null)];
            }

            if ($exerciseType === 'dictation') {
                if ($type !== 'listening') {
                    $violations[] = self::issue($file, "{$exercisePointer}/type", "{$exerciseKey}: dictation belongs only in listening lessons");
                }

                if (! is_string($exercise['audio_path'] ?? null) || ! str_starts_with($exercise['audio_path'], 'a1/')) {
                    $violations[] = self::issue($file, "{$exercisePointer}/audio_path", "{$exerciseKey} needs an audio_path under a1/");
                }
            }
        }

        if ($production > self::MAX_PRODUCTION_TASKS) {
            $violations[] = self::issue($file, "{$pointer}/exercises", sprintf('%s has %d writing or speaking tasks, at most %d', $lessonKey, $production, self::MAX_PRODUCTION_TASKS));
        }

        return $violations;
    }

    /**
     * A translatable member must carry non-empty English and Spanish.
     *
     * @return list<string>
     */
    private static function bilingual(string $file, string $pointer, string $key, mixed $value): array
    {
        $missing = [];

        foreach (['en', 'es'] as $locale) {
            if (! is_array($value) || ! is_string($value[$locale] ?? null) || trim($value[$locale]) === '') {
                $missing[] = $locale;
            }
        }

        return $missing === [] ? [] : [self::issue($file, $pointer, "{$key} is missing ".implode(' and ', $missing))];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function list(mixed $value): array
    {
        return is_array($value) ? array_values(array_filter($value, is_array(...))) : [];
    }

    private static function issue(string $file, string $pointer, string $message): string
    {
        return "{$file} {$pointer}: {$message}";
    }
}
