<?php

namespace App\Enums;

enum AuthorType: string
{
    case Human = 'human';
    case AiAgent = 'ai_agent';

    public function label(): string
    {
        return match ($this) {
            self::Human => 'Human',
            self::AiAgent => 'AI Agent',
        };
    }
}
