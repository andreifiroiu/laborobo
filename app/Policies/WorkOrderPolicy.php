<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkOrder;

class WorkOrderPolicy
{
    public function view(User $user, WorkOrder $workOrder): bool
    {
        return $user->currentTeam?->id === $workOrder->team_id;
    }

    public function update(User $user, WorkOrder $workOrder): bool
    {
        return $user->currentTeam?->id === $workOrder->team_id;
    }

    public function delete(User $user, WorkOrder $workOrder): bool
    {
        return $user->currentTeam?->id === $workOrder->team_id;
    }
}
