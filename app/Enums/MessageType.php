<?php

namespace App\Enums;

enum MessageType: string
{
    case Note = 'note';
    case Suggestion = 'suggestion';
    case Decision = 'decision';
    case Question = 'question';

    public function label(): string
    {
        return match ($this) {
            self::Note => 'Note',
            self::Suggestion => 'Suggestion',
            self::Decision => 'Decision',
            self::Question => 'Question',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Note => 'slate',
            self::Suggestion => 'indigo',
            self::Decision => 'emerald',
            self::Question => 'amber',
        };
    }
}
