<?php

namespace Database\Factories\Support;

use App\Enums\ExerciseType;

/**
 * One valid `content` and `solution` pair per exercise type, shared by the
 * exercise and exam-question factories and by the payload tests.
 */
final class ExerciseSamples
{
    /** @return array<string, mixed> */
    public static function content(ExerciseType $type): array
    {
        return match ($type) {
            ExerciseType::MultipleChoice => [
                'options' => [
                    ['id' => 'a', 'text' => 'am'],
                    ['id' => 'b', 'text' => 'is'],
                    ['id' => 'c', 'text' => 'are'],
                ],
                'multiple' => false,
            ],
            ExerciseType::FillGap => ['text' => 'She {{g1}} to school and {{g2}} home at four.'],
            ExerciseType::Reorder => [
                'tokens' => [
                    ['id' => 't2', 'text' => 'likes'],
                    ['id' => 't1', 'text' => 'She'],
                    ['id' => 't3', 'text' => 'tea'],
                ],
            ],
            ExerciseType::Matching => [
                'left' => [['id' => 'l1', 'text' => 'dog'], ['id' => 'l2', 'text' => 'cat']],
                'right' => [['id' => 'r1', 'text' => 'perro'], ['id' => 'r2', 'text' => 'gato'], ['id' => 'r3', 'text' => 'pez']],
            ],
            ExerciseType::Transformation => [
                'sentence' => 'I started learning English three years ago.',
                'keyword' => 'FOR',
                'max_words' => 6,
            ],
            ExerciseType::Dictation => ['max_plays' => 3],
            ExerciseType::Writing => [
                'task' => 'Write an email to a friend about your weekend.',
                'min_words' => 60,
                'max_words' => 120,
                'rubric' => self::rubric(),
            ],
            ExerciseType::Speaking => [
                'task' => 'Describe your daily routine.',
                'prep_seconds' => 30,
                'max_seconds' => 60,
                'rubric' => self::rubric(),
            ],
        };
    }

    /** @return array<string, mixed> */
    public static function solution(ExerciseType $type): array
    {
        return match ($type) {
            ExerciseType::MultipleChoice => ['correct' => ['b']],
            ExerciseType::FillGap => ['answers' => ['g1' => ['goes'], 'g2' => ['gets', 'comes']]],
            ExerciseType::Reorder => ['orders' => [['t1', 't2', 't3']]],
            ExerciseType::Matching => ['pairs' => [['l1', 'r1'], ['l2', 'r2']]],
            ExerciseType::Transformation => ['accepted' => ['I have been learning English for three years.']],
            ExerciseType::Dictation => ['transcript' => 'The weather is lovely today.', 'accepted' => []],
            ExerciseType::Writing => ['model_answer' => 'Hi Anna, I had a great weekend...'],
            ExerciseType::Speaking => ['model_answer' => null],
        };
    }

    /** @return list<array{key: string, description: array<string, string>, max_points: int}> */
    public static function rubric(): array
    {
        return [
            ['key' => 'task', 'description' => ['en' => 'Answers the task', 'es' => 'Responde a la tarea'], 'max_points' => 5],
            ['key' => 'grammar', 'description' => ['en' => 'Grammar and vocabulary'], 'max_points' => 5],
        ];
    }
}
