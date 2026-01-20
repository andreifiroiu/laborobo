<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\WorkOrderList;

class WorkOrderListPolicy
{
    public function view(User $user, WorkOrderList $workOrderList): bool
    {
        return $user->currentTeam?->id === $workOrderList->team_id;
    }

    public function create(User $user): bool
    {
        return $user->currentTeam !== null;
    }

    public function update(User $user, WorkOrderList $workOrderList): bool
    {
        return $user->currentTeam?->id === $workOrderList->team_id;
    }

    public function delete(User $user, WorkOrderList $workOrderList): bool
    {
        return $user->currentTeam?->id === $workOrderList->team_id;
    }
}
