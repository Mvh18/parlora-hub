<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

/** A level exam belongs to one CEFR level; the placement exam has no level. */
enum ExamKind: string
{
    use HasValues;

    case Level = 'level';
    case Placement = 'placement';
}
