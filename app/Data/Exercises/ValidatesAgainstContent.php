<?php

namespace App\Data\Exercises;

use App\Exceptions\InvalidExercisePayload;
use Spatie\LaravelData\Data;

/**
 * A solution whose rules depend on the content it answers (ids that must
 * exist, permutations, gap markers…), beyond what attribute rules express.
 */
interface ValidatesAgainstContent
{
    /**
     * @throws InvalidExercisePayload naming every failing path, prefixed with `content.` or `solution.`
     */
    public function assertConsistentWith(Data $content): void;
}
