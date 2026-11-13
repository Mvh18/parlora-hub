<?php

namespace App\Data\Exercises;

use Closure;
use Spatie\LaravelData\Data;

final class FillGapContent extends Data
{
    /**
     * @param  string  $text  Text with `{{gap-id}}` markers.
     */
    public function __construct(
        public string $text,
    ) {}

    /** @return array<string, mixed> */
    public static function rules(): array
    {
        return [
            'text' => [
                'required',
                'string',
                'max:2000',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (is_string($value) && self::gapIdsIn($value) === []) {
                        $fail(trans('validation.content.payload.no_gaps'));
                    }
                },
            ],
        ];
    }

    /**
     * The gap ids in the text, in order of appearance, without repeats.
     *
     * @return list<string>
     */
    public static function gapIdsIn(string $text): array
    {
        preg_match_all(ExerciseIds::GAP_MARKER, $text, $matches);

        return array_values(array_unique($matches[1]));
    }

    /** @return list<string> */
    public function gapIds(): array
    {
        return self::gapIdsIn($this->text);
    }
}
