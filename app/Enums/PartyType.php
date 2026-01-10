<?php

namespace App\Enums;

enum PartyType: string
{
    case Client = 'client';
    case Vendor = 'vendor';
    case Department = 'department';
    case TeamMember = 'team_member';

    public function label(): string
    {
        return match ($this) {
            self::Client => 'Client',
            self::Vendor => 'Vendor',
            self::Department => 'Department',
            self::TeamMember => 'Team Member',
        };
    }
}
