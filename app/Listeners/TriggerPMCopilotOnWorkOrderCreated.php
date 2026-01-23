<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\WorkOrderCreated;
use App\Jobs\ProcessPMCopilotTrigger;
use App\Models\GlobalAISettings;
use Illuminate\Support\Facades\Log;

/**
 * Listens for WorkOrderCreated events and triggers the PM Copilot Agent
 * for auto-suggestion when enabled in team settings.
 *
 * Only triggers if:
 * - Team has pm_copilot_auto_suggest enabled in GlobalAISettings
 * - Work order does not already have deliverables or tasks
 */
class TriggerPMCopilotOnWorkOrderCreated
{
    /**
     * Handle the WorkOrderCreated event.
     *
     * Checks if PM Copilot auto-suggest is enabled for the team
     * and dispatches the ProcessPMCopilotTrigger job if conditions are met.
     */
    public function handle(WorkOrderCreated $event): void
    {
        $workOrder = $event->workOrder;
        $team = $workOrder->team;

        if ($team === null) {
            Log::warning('WorkOrderCreated: No team found for work order', [
                'work_order_id' => $workOrder->id,
            ]);

            return;
        }

        // Get or create GlobalAISettings for the team
        $settings = GlobalAISettings::forTeam($team);

        // Check if PM Copilot auto-suggest is enabled
        if (! $settings->isPMCopilotAutoSuggestEnabled()) {
            Log::debug('PM Copilot auto-suggest is disabled for team', [
                'team_id' => $team->id,
                'work_order_id' => $workOrder->id,
            ]);

            return;
        }

        // Skip if work order already has deliverables
        if ($workOrder->deliverables()->exists()) {
            Log::debug('Skipping PM Copilot trigger: work order already has deliverables', [
                'work_order_id' => $workOrder->id,
            ]);

            return;
        }

        // Skip if work order already has tasks
        if ($workOrder->tasks()->exists()) {
            Log::debug('Skipping PM Copilot trigger: work order already has tasks', [
                'work_order_id' => $workOrder->id,
            ]);

            return;
        }

        Log::info('Triggering PM Copilot for new work order', [
            'work_order_id' => $workOrder->id,
            'team_id' => $team->id,
        ]);

        // Dispatch the job for async processing
        ProcessPMCopilotTrigger::dispatch($workOrder);
    }
}
