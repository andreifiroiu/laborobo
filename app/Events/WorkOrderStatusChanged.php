<?php

declare(strict_types=1);

namespace App\Events;

use App\Enums\WorkOrderStatus;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a work order's status changes.
 *
 * Used to trigger automated workflows such as client communication
 * draft suggestions when work orders transition to specific states.
 */
class WorkOrderStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly WorkOrder $workOrder,
        public readonly WorkOrderStatus $fromStatus,
        public readonly WorkOrderStatus $toStatus,
        public readonly ?User $user = null,
    ) {}
}
