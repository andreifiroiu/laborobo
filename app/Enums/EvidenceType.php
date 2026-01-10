<?php

namespace App\Enums;

enum EvidenceType: string
{
    case Document = 'document';
    case Link = 'link';
    case Screenshot = 'screenshot';
    case Approval = 'approval';
    case CalendarInvite = 'calendar-invite';
}
