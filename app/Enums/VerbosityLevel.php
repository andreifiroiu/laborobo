<?php

namespace App\Enums;

enum VerbosityLevel: string
{
    case Concise = 'concise';
    case Balanced = 'balanced';
    case Detailed = 'detailed';
}
