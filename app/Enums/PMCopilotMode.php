<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * PM Copilot workflow modes for work orders.
 *
 * - Full: Generate deliverables and tasks in one continuous workflow pass
 * - Staged: Pause after deliverables for human approval before task breakdown
 */
enum PMCopilotMode: string
{
    case Full = 'full';
    case Staged = 'staged';

    /**
     * Get the human-readable label for the mode.
     */
    public function label(): string
    {
        return match ($this) {
            self::Full => 'Full Plan',
            self::Staged => 'Staged',
        };
    }

    /**
     * Get a description of what this mode does.
     */
    public function description(): string
    {
        return match ($this) {
            self::Full => 'Generate deliverables and tasks in one continuous pass',
            self::Staged => 'Pause after deliverable generation for approval before creating tasks',
        };
    }
}
