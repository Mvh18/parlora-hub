<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

/**
 * The CEFR levels Hub teaches. Parlora Tutor also knows C2; Hub stops at C1.
 */
enum CefrLevel: string
{
    use HasValues;

    case A1 = 'A1';
    case A2 = 'A2';
    case B1 = 'B1';
    case B2 = 'B2';
    case C1 = 'C1';

    /** 1 for A1 up to 5 for C1: the natural order of the levels. */
    public function position(): int
    {
        return array_search($this, self::cases(), true) + 1;
    }
}
