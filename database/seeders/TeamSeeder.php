<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use App\Observers\TeamObserver;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $testUser = User::where('email', 'test@example.com')->first();

        if (! $testUser) {
            return;
        }

        // Create personal team if needed
        if ($testUser->allTeams()->count() === 0) {
            $personalTeam = $testUser->createTeam([
                'name' => 'Personal Team',
            ]);

            $testUser->update(['current_team_id' => $personalTeam->id]);
        }

        // Create work team
        if ($testUser->allTeams()->count() === 1) {
            $testUser->createTeam([
                'name' => 'Work Team',
            ]);
        }

        // Create additional test users
        $alice = User::firstOrCreate(
            ['email' => 'alice@example.com'],
            [
                'name' => 'Alice Johnson',
                'password' => 'password',
                'email_verified_at' => now(),
            ]
        );

        $bob = User::firstOrCreate(
            ['email' => 'bob@example.com'],
            [
                'name' => 'Bob Smith',
                'password' => 'password',
                'email_verified_at' => now(),
            ]
        );

        // Create teams for additional users
        if ($alice->allTeams()->count() === 0) {
            $team = $alice->createTeam(['name' => 'Alice\'s Team']);
            $alice->update(['current_team_id' => $team->id]);
        }

        if ($bob->allTeams()->count() === 0) {
            $team = $bob->createTeam(['name' => 'Bob\'s Team']);
            $bob->update(['current_team_id' => $team->id]);
        }

        // Add users to work team - using addUser method from HasMembers trait
        $workTeam = $testUser->allTeams()->where('name', 'Work Team')->first();
        if ($workTeam) {
            if (! $workTeam->hasUser($alice)) {
                $workTeam->addUser($alice, 'member');
            }
            if (! $workTeam->hasUser($bob)) {
                $workTeam->addUser($bob, 'member');
            }
        }

        // Ensure all teams have default roles (for existing teams that may not have them)
        $this->ensureTeamsHaveRoles();
    }

    /**
     * Ensure all teams have default roles.
     */
    private function ensureTeamsHaveRoles(): void
    {
        $observer = new TeamObserver;

        Team::all()->each(function (Team $team) use ($observer) {
            if ($team->roles()->count() === 0) {
                $observer->createDefaultRoles($team);
            }
        });
    }
}
