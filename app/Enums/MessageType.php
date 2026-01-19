<?php

declare(strict_types=1);

namespace App\Enums;

enum MessageType: string
{
    case Note = 'note';
    case Suggestion = 'suggestion';
    case Decision = 'decision';
    case Question = 'question';
    case StatusUpdate = 'status_update';
    case ApprovalRequest = 'approval_request';
    case Message = 'message';

    public function label(): string
    {
        return match ($this) {
            self::Note => 'Note',
            self::Suggestion => 'Suggestion',
            self::Decision => 'Decision',
            self::Question => 'Question',
            self::StatusUpdate => 'Status Update',
            self::ApprovalRequest => 'Approval Request',
            self::Message => 'Message',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Note => 'slate',
            self::Suggestion => 'indigo',
            self::Decision => 'emerald',
            self::Question => 'amber',
            self::StatusUpdate => 'blue',
            self::ApprovalRequest => 'purple',
            self::Message => 'gray',
        };
    }
}
