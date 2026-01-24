<?php

declare(strict_types=1);

namespace App\Enums;

enum AgentMemoryScope: string
{
    case Project = 'project';
    case Client = 'client';
    case Org = 'org';
    case Chain = 'chain';
}
