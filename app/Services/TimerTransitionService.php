<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;

class TimerTransitionService
{
    /**
     * Statuses that require confirmation before starting a timer.
     * Starting a timer on these statuses will move the task back to InProgress.
     *
     * @var array<string>
     */
    private const CONFIRMATION_REQUIRED_STATUSES = [
        'done',
        'in_review',
        'approved',
    ];

    /**
     * Statuses that block timer start entirely.
     *
     * @var array<string>
     */
    private const BLOCKED_STATUSES = [
        'cancelled',
    ];

    /**
     * Statuses that auto-transition to InProgress when a timer is started.
     *
     * @var array<string>
     */
    private const AUTO_TRANSITION_STATUSES = [
        'todo',
        'blocked',
    ];

    public function __construct(
        private readonly WorkflowTransitionService $workflowTransitionService = new WorkflowTransitionService(),
    ) {}

    /**
     * Check if a timer can be started and start it if allowed.
     *
     * @return array{status: string, reason?: string, current_status?: string, time_entry?: TimeEntry}
     */
    public function checkAndStartTimer(Task $task, User $user, bool $isBillable = true): array
    {
        $currentStatus = $task->status->value;

        // Check if timer start is blocked
        if (in_array($currentStatus, self::BLOCKED_STATUSES, true)) {
            return [
                'status' => 'blocked',
                'reason' => 'Task is cancelled and cannot have a timer started.',
                'current_status' => $currentStatus,
            ];
        }

        // Check if confirmation is required
        if (in_array($currentStatus, self::CONFIRMATION_REQUIRED_STATUSES, true)) {
            return [
                'status' => 'confirmation_required',
                'reason' => "Task is currently '{$task->status->label()}'. Starting a timer will move it back to In Progress.",
                'current_status' => $currentStatus,
            ];
        }

        // Auto-transition if needed and start timer
        if (in_array($currentStatus, self::AUTO_TRANSITION_STATUSES, true)) {
            $this->workflowTransitionService->transition(
                item: $task,
                actor: $user,
                toStatus: TaskStatus::InProgress,
            );
        }

        // Start the timer
        $timeEntry = TimeEntry::startTimer($task, $user, $isBillable);

        return [
            'status' => 'started',
            'time_entry' => $timeEntry,
        ];
    }

    /**
     * Confirm and start timer, transitioning the task to InProgress.
     * Used after user confirms they want to start a timer on a completed/review task.
     */
    public function confirmAndStartTimer(Task $task, User $user, bool $isBillable = true): TimeEntry
    {
        $currentStatus = $task->status->value;

        // Blocked statuses cannot be confirmed
        if (in_array($currentStatus, self::BLOCKED_STATUSES, true)) {
            throw new \InvalidArgumentException('Cannot start timer on a cancelled task.');
        }

        // Transition to InProgress if not already
        if ($currentStatus !== 'in_progress') {
            // Use timerTransition for states that require it (done, in_review, approved)
            if (in_array($currentStatus, self::CONFIRMATION_REQUIRED_STATUSES, true)) {
                $this->workflowTransitionService->timerTransition($task, $user);
            } else {
                // For todo and blocked, use normal transition
                $this->workflowTransitionService->transition(
                    item: $task,
                    actor: $user,
                    toStatus: TaskStatus::InProgress,
                );
            }
        }

        // Start the timer
        return TimeEntry::startTimer($task, $user, $isBillable);
    }
}
