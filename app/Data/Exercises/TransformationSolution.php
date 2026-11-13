<?php

namespace App\Data\Exercises;

use Spatie\LaravelData\Data;

final class TransformationSolution extends Data implements ValidatesAgainstContent
{
    /**
     * @param  list<string>  $accepted  accepted rewrites
     */
    public function __construct(
        public array $accepted,
    ) {}

    /** @return array<string, mixed> */
    public static function rules(): array
    {
        return [
            'accepted' => ['required', 'array', 'min:1'],
            'accepted.*' => ['required', 'string', 'max:500'],
        ];
    }

    public function assertConsistentWith(Data $content): void
    {
        // No cross-field rules: any accepted rewrite stands on its own.
    }
}
