<?php

namespace App\Enums;

enum CommunicationPreference: string
{
    case Email = 'email';
    case Phone = 'phone';
    case Slack = 'slack';

    public function label(): string
    {
        return match ($this) {
            self::Email => 'Email',
            self::Phone => 'Phone',
            self::Slack => 'Slack',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Email => 'mail',
            self::Phone => 'phone',
            self::Slack => 'message-square',
        };
    }
}
