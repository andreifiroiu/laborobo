<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Task;
use App\Models\User;
use App\Models\WorkOrder;

class ReviewerResolver
{
    /**
     * Resolve the reviewer for a Task or WorkOrder using priority order.
     *
     * Priority 1: Explicit reviewer_id field
     * Priority 2: Accountable (A) person from RACI
     * Priority 3: Work Order assigned_to_id
     * Priority 4: Project owner_id
     */
    public function resolve(Task|WorkOrder $item): ?User
    {
        // Priority 1: Check explicit reviewer_id field
        $reviewer = $this->getExplicitReviewer($item);
        if ($reviewer !== null) {
            return $reviewer;
        }

        // Priority 2: Check Accountable (A) person from RACI
        $reviewer = $this->getAccountablePerson($item);
        if ($reviewer !== null) {
            return $reviewer;
        }

        // Priority 3: Check Work Order assigned_to_id
        $reviewer = $this->getWorkOrderAssignee($item);
        if ($reviewer !== null) {
            return $reviewer;
        }

        // Priority 4: Check Project owner_id
        return $this->getProjectOwner($item);
    }

    /**
     * Get the explicit reviewer assigned to the item.
     */
    private function getExplicitReviewer(Task|WorkOrder $item): ?User
    {
        if ($item->reviewer_id === null) {
            return null;
        }

        return $item->reviewer;
    }

    /**
     * Get the Accountable person from RACI.
     * For Tasks, this comes from the parent WorkOrder.
     * For WorkOrders, this is directly on the model.
     */
    private function getAccountablePerson(Task|WorkOrder $item): ?User
    {
        if ($item instanceof Task) {
            $workOrder = $item->workOrder;
            if ($workOrder === null || $workOrder->accountable_id === null) {
                return null;
            }

            return $workOrder->accountable;
        }

        if ($item->accountable_id === null) {
            return null;
        }

        return $item->accountable;
    }

    /**
     * Get the Work Order assignee.
     * For Tasks, this comes from the parent WorkOrder.
     * For WorkOrders, this is directly on the model.
     */
    private function getWorkOrderAssignee(Task|WorkOrder $item): ?User
    {
        if ($item instanceof Task) {
            $workOrder = $item->workOrder;
            if ($workOrder === null || $workOrder->assigned_to_id === null) {
                return null;
            }

            return $workOrder->assignedTo;
        }

        if ($item->assigned_to_id === null) {
            return null;
        }

        return $item->assignedTo;
    }

    /**
     * Get the Project owner as final fallback.
     */
    private function getProjectOwner(Task|WorkOrder $item): ?User
    {
        $project = $item->project;
        if ($project === null || $project->owner_id === null) {
            return null;
        }

        return $project->owner;
    }
}
