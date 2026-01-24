<?php

declare(strict_types=1);

namespace App\Events;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a task's status changes.
 *
 * Used to trigger automated workflows such as agent chain execution
 * when tasks transition to specific states.
 */
class TaskStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Task $task,
        public readonly TaskStatus $fromStatus,
        public readonly TaskStatus $toStatus,
        public readonly ?User $user = null,
    ) {}
}
