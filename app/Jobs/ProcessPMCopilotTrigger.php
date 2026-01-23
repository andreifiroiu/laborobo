<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\AgentType;
use App\Models\AgentConfiguration;
use App\Models\AIAgent;
use App\Models\WorkOrder;
use App\Services\AgentOrchestrator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Queue job for processing PM Copilot agent triggers.
 *
 * Loads the work order context and executes the PMCopilotWorkflow
 * for auto-generating deliverables and task breakdowns.
 */
class ProcessPMCopilotTrigger implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    public function __construct(
        public readonly WorkOrder $workOrder,
    ) {}

    /**
     * Execute the job.
     *
     * Processes the PM Copilot trigger by finding the PM Copilot agent,
     * verifying configuration, and executing the workflow.
     */
    public function handle(AgentOrchestrator $orchestrator): void
    {
        Log::info('Processing PM Copilot trigger', [
            'work_order_id' => $this->workOrder->id,
            'team_id' => $this->workOrder->team_id,
        ]);

        // Find the PM Copilot agent
        $agent = $this->getPMCopilotAgent();

        if ($agent === null) {
            Log::warning('PM Copilot agent not found', [
                'work_order_id' => $this->workOrder->id,
            ]);

            return;
        }

        // Get agent configuration for the team
        $configuration = $this->getAgentConfiguration($agent);

        if ($configuration === null || ! $configuration->enabled) {
            Log::info('PM Copilot agent not configured or disabled for team', [
                'team_id' => $this->workOrder->team_id,
                'agent_id' => $agent->id,
            ]);

            return;
        }

        // Execute the PM Copilot workflow
        $workflowState = $orchestrator->invokePMCopilot($this->workOrder, $agent);

        Log::info('PM Copilot workflow started', [
            'workflow_state_id' => $workflowState->id,
            'work_order_id' => $this->workOrder->id,
        ]);
    }

    /**
     * Get the PM Copilot agent.
     */
    private function getPMCopilotAgent(): ?AIAgent
    {
        return AIAgent::query()
            ->where('type', AgentType::ProjectManagement)
            ->first();
    }

    /**
     * Get the agent configuration for the team.
     */
    private function getAgentConfiguration(AIAgent $agent): ?AgentConfiguration
    {
        return AgentConfiguration::query()
            ->where('team_id', $this->workOrder->team_id)
            ->where('ai_agent_id', $agent->id)
            ->first();
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessPMCopilotTrigger job failed', [
            'work_order_id' => $this->workOrder->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
