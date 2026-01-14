<?php

namespace App\Enums;

enum SourceType: string
{
    case Human = 'human';
    case AIAgent = 'ai_agent';
}
