<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

/** The part of an exam a section belongs to. */
enum ExamSkill: string
{
    use HasValues;

    case UseOfEnglish = 'use_of_english';
    case Reading = 'reading';
    case Listening = 'listening';
    case Writing = 'writing';
    case Speaking = 'speaking';
}
