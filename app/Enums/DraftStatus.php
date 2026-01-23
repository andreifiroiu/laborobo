<?php

declare(strict_types=1);

namespace App\Enums;

enum DraftStatus: string
{
    case Draft = 'draft';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Sent = 'sent';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Sent => 'Sent',
        };
    }

    /**
     * Check if this status represents a final state (no further changes expected).
     */
    public function isFinal(): bool
    {
        return match ($this) {
            self::Draft => false,
            self::Approved, self::Rejected, self::Sent => true,
        };
    }
}
