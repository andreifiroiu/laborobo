<?php

namespace App\Enums;

enum InboxItemType: string
{
    case AgentDraft = 'agent_draft';
    case Approval = 'approval';
    case Flag = 'flag';
    case Mention = 'mention';

    public function label(): string
    {
        return match($this) {
            self::AgentDraft => 'Agent Draft',
            self::Approval => 'Approval Request',
            self::Flag => 'Flagged Item',
            self::Mention => 'Mention',
        };
    }
}
