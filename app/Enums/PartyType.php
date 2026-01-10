<?php

namespace App\Enums;

enum PartyType: string
{
    case Client = 'client';
    case Vendor = 'vendor';
    case Partner = 'partner';
    case Department = 'department';
    case InternalDepartment = 'internal-department';
    case TeamMember = 'team_member';

    public function label(): string
    {
        return match ($this) {
            self::Client => 'Client',
            self::Vendor => 'Vendor',
            self::Partner => 'Partner',
            self::Department => 'Department',
            self::InternalDepartment => 'Internal Department',
            self::TeamMember => 'Team Member',
        };
    }
}
