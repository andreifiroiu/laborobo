<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function view(User $user, Project $project): bool
    {
        if ($user->currentTeam?->id !== $project->team_id) {
            return false;
        }

        if (! $project->is_private) {
            return true;
        }

        return $project->isVisibleTo($user->id);
    }

    public function update(User $user, Project $project): bool
    {
        return $user->currentTeam?->id === $project->team_id;
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->currentTeam?->id === $project->team_id;
    }

    public function togglePrivacy(User $user, Project $project): bool
    {
        return $user->currentTeam?->id === $project->team_id
            && $user->id === $project->owner_id;
    }
}
