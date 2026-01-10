<?php

namespace App\Enums;

enum DeliverableStatus: string
{
    case Draft = 'draft';
    case InReview = 'in_review';
    case Approved = 'approved';
    case Delivered = 'delivered';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::InReview => 'In Review',
            self::Approved => 'Approved',
            self::Delivered => 'Delivered',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'slate',
            self::InReview => 'amber',
            self::Approved => 'emerald',
            self::Delivered => 'emerald',
        };
    }
}
