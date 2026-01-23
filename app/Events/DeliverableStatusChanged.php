<?php

declare(strict_types=1);

namespace App\Events;

use App\Enums\DeliverableStatus;
use App\Models\Deliverable;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a deliverable's status changes.
 *
 * Used to trigger automated workflows such as client communication
 * draft suggestions when deliverables are ready or delivered.
 */
class DeliverableStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Deliverable $deliverable,
        public readonly DeliverableStatus $fromStatus,
        public readonly DeliverableStatus $toStatus,
        public readonly ?User $user = null,
    ) {}
}
