<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\WorkOrder;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a new work order is created.
 *
 * Used to trigger automated workflows such as the PM Copilot
 * auto-suggest feature for deliverable and task generation.
 */
class WorkOrderCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly WorkOrder $workOrder,
    ) {}
}
