<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\CommunicationType;
use App\Enums\ProjectStatus;
use App\Models\GlobalAISettings;
use App\Models\Project;
use App\Models\Team;
use App\Services\ClientCommsDraftService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Command to generate weekly status summary drafts for active projects.
 *
 * Queries all active projects with recent activity and generates
 * status update drafts for client communication.
 */
class GenerateWeeklySummariesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'client-comms:weekly-summaries
        {--team= : Generate summaries only for a specific team}
        {--dry-run : Preview which projects would get summaries without creating them}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate weekly status summary drafts for active projects with recent activity';

    public function __construct(
        private readonly ClientCommsDraftService $draftService,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $teamId = $this->option('team');
        $dryRun = $this->option('dry-run');

        $this->info('Starting weekly summary generation...');

        // Get teams to process
        $teams = $this->getTeamsToProcess($teamId);

        $totalDrafts = 0;
        $skippedTeams = 0;

        foreach ($teams as $team) {
            // Check if weekly summaries are enabled for this team
            $settings = GlobalAISettings::forTeam($team);

            if (! $this->isWeeklySummaryEnabled($settings)) {
                $this->line("Skipping team '{$team->name}' - weekly summaries disabled");
                $skippedTeams++;

                continue;
            }

            $draftsGenerated = $this->generateSummariesForTeam($team, $dryRun);
            $totalDrafts += $draftsGenerated;
        }

        if ($dryRun) {
            $this->info("Dry run complete. Would generate {$totalDrafts} draft(s).");
        } else {
            $this->info("Generated {$totalDrafts} weekly summary draft(s).");
        }

        if ($skippedTeams > 0) {
            $this->line("Skipped {$skippedTeams} team(s) with weekly summaries disabled.");
        }

        Log::info('Weekly summary generation completed', [
            'total_drafts' => $totalDrafts,
            'skipped_teams' => $skippedTeams,
            'dry_run' => $dryRun,
        ]);

        return self::SUCCESS;
    }

    /**
     * Get teams to process based on command options.
     *
     * @return \Illuminate\Support\Collection<int, Team>
     */
    private function getTeamsToProcess(?string $teamId): \Illuminate\Support\Collection
    {
        if ($teamId !== null) {
            $team = Team::find($teamId);

            return $team !== null ? collect([$team]) : collect();
        }

        return Team::all();
    }

    /**
     * Check if weekly summary generation is enabled for the team.
     */
    private function isWeeklySummaryEnabled(GlobalAISettings $settings): bool
    {
        // Check both auto-draft setting and specific weekly summary toggle
        return (bool) ($settings->client_comms_auto_draft ?? false);
    }

    /**
     * Generate summaries for all active projects in a team.
     */
    private function generateSummariesForTeam(Team $team, bool $dryRun): int
    {
        // Get active projects with recent activity
        $projects = Project::query()
            ->forTeam($team->id)
            ->where('status', ProjectStatus::Active)
            ->whereHas('workOrders', function ($query) {
                // Only projects with work orders updated in the last week
                $query->where('updated_at', '>=', now()->subWeek());
            })
            ->get();

        $draftsGenerated = 0;

        foreach ($projects as $project) {
            if ($dryRun) {
                $this->line("Would generate summary for project: {$project->name}");
                $draftsGenerated++;

                continue;
            }

            try {
                $this->generateSummaryForProject($project);
                $draftsGenerated++;
                $this->line("Generated summary for project: {$project->name}");
            } catch (\Throwable $e) {
                Log::error('Failed to generate weekly summary for project', [
                    'project_id' => $project->id,
                    'project_name' => $project->name,
                    'error' => $e->getMessage(),
                ]);
                $this->error("Failed to generate summary for project: {$project->name}");
            }
        }

        return $draftsGenerated;
    }

    /**
     * Generate a weekly summary draft for a specific project.
     */
    private function generateSummaryForProject(Project $project): void
    {
        // Create the draft with scheduled source type
        $draft = $this->draftService->createDraft(
            $project,
            CommunicationType::StatusUpdate,
            'Scheduled weekly status update'
        );

        // Add scheduled source metadata
        $draft->update([
            'draft_metadata' => array_merge($draft->draft_metadata ?? [], [
                'source_type' => 'scheduled',
                'schedule_context' => [
                    'command' => 'client-comms:weekly-summaries',
                    'generated_at' => now()->toIso8601String(),
                ],
            ]),
        ]);

        // Create approval inbox item
        $this->draftService->createApprovalItem($draft, $project);
    }
}
