<?php

namespace App\Policies;

use App\Models\Playbook;
use App\Models\User;

class PlaybookPolicy
{
    /**
     * Determine whether the user can view any playbooks.
     */
    public function viewAny(User $user): bool
    {
        return true; // All team members can view playbooks
    }

    /**
     * Determine whether the user can view the playbook.
     */
    public function view(User $user, Playbook $playbook): bool
    {
        return $user->currentTeam->id === $playbook->team_id;
    }

    /**
     * Determine whether the user can create playbooks.
     */
    public function create(User $user): bool
    {
        return true; // All team members can create playbooks
    }

    /**
     * Determine whether the user can update the playbook.
     */
    public function update(User $user, Playbook $playbook): bool
    {
        return $user->currentTeam->id === $playbook->team_id;
    }

    /**
     * Determine whether the user can delete the playbook.
     */
    public function delete(User $user, Playbook $playbook): bool
    {
        return $user->currentTeam->id === $playbook->team_id
            && ($user->id === $playbook->created_by || $user->isAdmin());
    }
}
