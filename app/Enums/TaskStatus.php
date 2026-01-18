<?php

declare(strict_types=1);

namespace App\Enums;

enum TaskStatus: string
{
    case Todo = 'todo';
    case InProgress = 'in_progress';
    case InReview = 'in_review';
    case Approved = 'approved';
    case Done = 'done';
    case Blocked = 'blocked';
    case Cancelled = 'cancelled';
    case RevisionRequested = 'revision_requested';

    public function label(): string
    {
        return match ($this) {
            self::Todo => 'To Do',
            self::InProgress => 'In Progress',
            self::InReview => 'In Review',
            self::Approved => 'Approved',
            self::Done => 'Done',
            self::Blocked => 'Blocked',
            self::Cancelled => 'Cancelled',
            self::RevisionRequested => 'Revision Requested',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Todo => 'slate',
            self::InProgress => 'indigo',
            self::InReview => 'amber',
            self::Approved => 'emerald',
            self::Done => 'emerald',
            self::Blocked => 'red',
            self::Cancelled => 'red',
            self::RevisionRequested => 'orange',
        };
    }
}
