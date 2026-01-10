<?php

namespace App\Policies;

use App\Models\Party;
use App\Models\User;

class PartyPolicy
{
    public function view(User $user, Party $party): bool
    {
        return $user->currentTeam?->id === $party->team_id;
    }

    public function update(User $user, Party $party): bool
    {
        return $user->currentTeam?->id === $party->team_id;
    }

    public function delete(User $user, Party $party): bool
    {
        return $user->currentTeam?->id === $party->team_id;
    }
}
