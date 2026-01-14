<?php

namespace App\Enums;

enum BlockerReason: string
{
    case WaitingOnExternal = 'waiting_on_external';
    case MissingInformation = 'missing_information';
    case TechnicalIssue = 'technical_issue';
    case WaitingOnApproval = 'waiting_on_approval';

    public function label(): string
    {
        return match ($this) {
            self::WaitingOnExternal => 'Waiting on External',
            self::MissingInformation => 'Missing Information',
            self::TechnicalIssue => 'Technical Issue',
            self::WaitingOnApproval => 'Waiting on Approval',
        };
    }
}
