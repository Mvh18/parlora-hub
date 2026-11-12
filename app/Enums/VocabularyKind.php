<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum VocabularyKind: string
{
    use HasValues;

    case Word = 'word';
    case PhrasalVerb = 'phrasal_verb';
    case Expression = 'expression';
}
