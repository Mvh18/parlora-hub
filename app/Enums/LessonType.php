<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum LessonType: string
{
    use HasValues;

    case Grammar = 'grammar';
    case Vocabulary = 'vocabulary';
    case PhrasalVerbs = 'phrasal_verbs';
    case Reading = 'reading';
    case Listening = 'listening';
    case Writing = 'writing';
    case Speaking = 'speaking';
}
