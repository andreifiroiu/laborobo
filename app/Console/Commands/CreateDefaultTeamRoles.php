<?php

namespace App\Console\Commands;

use App\Models\Team;
use App\Observers\TeamObserver;
use Illuminate\Console\Command;

class CreateDefaultTeamRoles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'teams:create-default-roles {--dry-run : Show what would be done without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create default roles (admin, member, viewer) for all teams that do not have roles';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $teams = Team::all();
        $teamsWithoutRoles = $teams->filter(fn (Team $team) => $team->roles()->count() === 0);

        if ($teamsWithoutRoles->isEmpty()) {
            $this->info('All teams already have roles. Nothing to do.');

            return self::SUCCESS;
        }

        $this->info(sprintf(
            'Found %d team(s) without roles out of %d total.',
            $teamsWithoutRoles->count(),
            $teams->count()
        ));

        if ($dryRun) {
            $this->warn('Dry run mode - no changes will be made.');
            $this->newLine();
            $this->table(
                ['Team ID', 'Team Name', 'Owner'],
                $teamsWithoutRoles->map(fn (Team $team) => [
                    $team->id,
                    $team->name,
                    $team->owner?->name ?? 'N/A',
                ])
            );

            return self::SUCCESS;
        }

        $observer = new TeamObserver;
        $progressBar = $this->output->createProgressBar($teamsWithoutRoles->count());
        $progressBar->start();

        foreach ($teamsWithoutRoles as $team) {
            $observer->createDefaultRoles($team);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info(sprintf(
            'Successfully created default roles for %d team(s).',
            $teamsWithoutRoles->count()
        ));

        return self::SUCCESS;
    }
}
