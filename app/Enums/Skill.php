<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

/**
 * The skill an exercise trains: the axes of the progress statistics.
 */
enum Skill: string
{
    use HasValues;

    case Grammar = 'grammar';
    case Vocabulary = 'vocabulary';
    case Reading = 'reading';
    case Listening = 'listening';
    case Writing = 'writing';
    case Speaking = 'speaking';
}
