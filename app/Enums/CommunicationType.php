<?php

declare(strict_types=1);

namespace App\Enums;

enum CommunicationType: string
{
    case StatusUpdate = 'status_update';
    case DeliverableNotification = 'deliverable_notification';
    case ClarificationRequest = 'clarification_request';
    case MilestoneAnnouncement = 'milestone_announcement';

    public function label(): string
    {
        return match ($this) {
            self::StatusUpdate => 'Status Update',
            self::DeliverableNotification => 'Deliverable Notification',
            self::ClarificationRequest => 'Clarification Request',
            self::MilestoneAnnouncement => 'Milestone Announcement',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::StatusUpdate => 'Regular progress update on project or work order status',
            self::DeliverableNotification => 'Notification that a deliverable is ready or has changed status',
            self::ClarificationRequest => 'Request for additional information or clarification from the client',
            self::MilestoneAnnouncement => 'Announcement of a completed milestone or significant achievement',
        };
    }
}
