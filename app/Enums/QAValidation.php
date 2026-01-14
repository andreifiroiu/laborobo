<?php

namespace App\Enums;

enum QAValidation: string
{
    case Passed = 'passed';
    case Failed = 'failed';
}
