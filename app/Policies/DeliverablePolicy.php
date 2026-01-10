<?php

namespace App\Policies;

use App\Models\Deliverable;
use App\Models\User;

class DeliverablePolicy
{
    public function view(User $user, Deliverable $deliverable): bool
    {
        return $user->currentTeam?->id === $deliverable->team_id;
    }

    public function update(User $user, Deliverable $deliverable): bool
    {
        return $user->currentTeam?->id === $deliverable->team_id;
    }

    public function delete(User $user, Deliverable $deliverable): bool
    {
        return $user->currentTeam?->id === $deliverable->team_id;
    }
}
