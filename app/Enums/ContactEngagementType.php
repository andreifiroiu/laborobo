<?php

namespace App\Enums;

enum ContactEngagementType: string
{
    case Requester = 'requester';
    case Approver = 'approver';
    case Stakeholder = 'stakeholder';
    case Billing = 'billing';

    public function label(): string
    {
        return match ($this) {
            self::Requester => 'Requester',
            self::Approver => 'Approver',
            self::Stakeholder => 'Stakeholder',
            self::Billing => 'Billing',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Requester => 'Initiates work requests and provides requirements',
            self::Approver => 'Reviews and approves deliverables',
            self::Stakeholder => 'Involved in project but does not approve',
            self::Billing => 'Receives invoices and handles payments',
        };
    }
}
