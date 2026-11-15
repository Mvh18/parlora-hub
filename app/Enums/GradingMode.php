<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

/** How a section is marked: by the server, by Parlora Tutor, or by the learner. */
enum GradingMode: string
{
    use HasValues;

    case Auto = 'auto';
    case Ai = 'ai';
    case SelfAssessment = 'self_assessment';
}
