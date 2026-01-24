<?php

declare(strict_types=1);

namespace App\Enums;

enum ChainExecutionStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Paused = 'paused';
    case Completed = 'completed';
    case Failed = 'failed';

    /**
     * Get the human-readable label for the execution status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Running => 'Running',
            self::Paused => 'Paused',
            self::Completed => 'Completed',
            self::Failed => 'Failed',
        };
    }

    /**
     * Check if the status represents an active execution.
     */
    public function isActive(): bool
    {
        return in_array($this, [self::Pending, self::Running, self::Paused], true);
    }

    /**
     * Check if the status represents a terminal state.
     */
    public function isTerminal(): bool
    {
        return in_array($this, [self::Completed, self::Failed], true);
    }
}
