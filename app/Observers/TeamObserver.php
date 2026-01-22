<?php

namespace App\Observers;

use App\Models\Team;

class TeamObserver
{
    /**
     * Handle the Team "created" event.
     *
     * Creates default roles for the team.
     */
    public function created(Team $team): void
    {
        $this->createDefaultRoles($team);
    }

    /**
     * Create default roles for a team.
     */
    public function createDefaultRoles(Team $team): void
    {
        $defaultRoles = [
            [
                'code' => 'admin',
                'permissions' => ['*'],
                'name' => 'Admin',
                'description' => 'Full access to team settings and management',
            ],
            [
                'code' => 'member',
                'permissions' => [
                    'projects.view',
                    'projects.create',
                    'projects.edit',
                    'tasks.view',
                    'tasks.create',
                    'tasks.edit',
                    'work-orders.view',
                    'work-orders.create',
                    'work-orders.edit',
                ],
                'name' => 'Member',
                'description' => 'Standard team member with project access',
            ],
            [
                'code' => 'viewer',
                'permissions' => [
                    'projects.view',
                    'tasks.view',
                    'work-orders.view',
                ],
                'name' => 'Viewer',
                'description' => 'Read-only access to team content',
            ],
        ];

        foreach ($defaultRoles as $role) {
            if (! $team->hasRole($role['code'])) {
                $team->addRole(
                    $role['code'],
                    $role['permissions'],
                    $role['name'],
                    $role['description']
                );
            }
        }
    }
}
