<?php

namespace App\Data\Exercises;

use Spatie\LaravelData\Data;

final class DictationSolution extends Data implements ValidatesAgainstContent
{
    /**
     * @param  string  $transcript  what the audio says
     * @param  list<string>  $accepted  extra accepted spellings
     */
    public function __construct(
        public string $transcript,
        public array $accepted = [],
    ) {}

    /** @return array<string, mixed> */
    public static function rules(): array
    {
        return [
            'transcript' => ['required', 'string', 'max:1000'],
            'accepted' => ['array'],
            'accepted.*' => ['required', 'string', 'max:1000'],
        ];
    }

    public function assertConsistentWith(Data $content): void
    {
        // No cross-field rules: the transcript is the reference answer.
    }
}
