<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Agents\Workflows\DispatcherWorkflow;
use App\Models\AgentConfiguration;
use App\Models\AIAgent;
use App\Models\WorkOrder;
use App\Services\AgentOrchestrator;
use App\Services\ContextBuilder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Queue job for processing dispatcher routing recommendations.
 *
 * Triggered when a work order is created with dispatcher_enabled = true.
 * Analyzes work order details and provides routing recommendations.
 */
class ProcessDispatcherRouting implements ShouldQueue
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
        public readonly AIAgent $agent,
    ) {}

    /**
     * Execute the job.
     *
     * Processes the work order by building agent context,
     * executing the routing workflow, and storing results in metadata.
     */
    public function handle(
        ContextBuilder $contextBuilder,
        AgentOrchestrator $orchestrator,
    ): void {
        Log::info('Processing dispatcher routing', [
            'work_order_id' => $this->workOrder->id,
            'agent_id' => $this->agent->id,
        ]);

        // Get agent configuration for the team
        $configuration = $this->getAgentConfiguration($this->workOrder->team_id);

        if ($configuration === null || ! $configuration->enabled) {
            Log::info('Dispatcher agent not configured or disabled for team', [
                'team_id' => $this->workOrder->team_id,
            ]);

            return;
        }

        // Build agent context with work order data
        $agentContext = $contextBuilder->build($this->workOrder, $this->agent);

        // Add routing metadata to context
        $agentContext = $agentContext->withMetadata([
            'work_order_id' => $this->workOrder->id,
            'trigger_source' => 'work_order_creation',
            'estimated_hours' => $this->workOrder->estimated_hours,
        ]);

        // Execute the workflow
        $workflowState = $orchestrator->execute(
            DispatcherWorkflow::class,
            [
                'work_order_id' => $this->workOrder->id,
                'title' => $this->workOrder->title,
                'description' => $this->workOrder->description,
                'estimated_hours' => $this->workOrder->estimated_hours,
                'priority' => $this->workOrder->priority->value,
                'agent_context' => $agentContext->toArray(),
            ],
            $this->workOrder->team,
            $this->agent,
        );

        Log::info('Dispatcher workflow completed for work order routing', [
            'workflow_state_id' => $workflowState->id,
            'work_order_id' => $this->workOrder->id,
        ]);

        // Store routing recommendations in work order metadata
        $this->storeRoutingRecommendations($workflowState->state_data);
    }

    /**
     * Get the agent configuration for a team.
     */
    private function getAgentConfiguration(int $teamId): ?AgentConfiguration
    {
        return AgentConfiguration::query()
            ->where('team_id', $teamId)
            ->where('ai_agent_id', $this->agent->id)
            ->first();
    }

    /**
     * Store routing recommendations in work order metadata.
     *
     * @param  array<string, mixed>  $stateData
     */
    private function storeRoutingRecommendations(array $stateData): void
    {
        $metadata = $this->workOrder->metadata ?? [];

        // Add routing recommendations to metadata
        $metadata['routing_recommendations'] = [
            'generated_at' => now()->toIso8601String(),
            'candidates' => $stateData['routing_candidates'] ?? [],
            'top_candidate_id' => $stateData['top_candidate_id'] ?? null,
            'confidence' => $stateData['routing_confidence'] ?? 'low',
        ];

        // Store extracted requirements if available
        if (isset($stateData['extracted_requirements'])) {
            $metadata['extracted_requirements'] = $stateData['extracted_requirements'];
        }

        $this->workOrder->update(['metadata' => $metadata]);

        Log::info('Routing recommendations stored in work order metadata', [
            'work_order_id' => $this->workOrder->id,
            'candidates_count' => count($stateData['routing_candidates'] ?? []),
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessDispatcherRouting job failed', [
            'work_order_id' => $this->workOrder->id,
            'agent_id' => $this->agent->id,
            'error' => $exception->getMessage(),
        ]);

        // Update metadata to indicate routing failed
        $metadata = $this->workOrder->metadata ?? [];
        $metadata['routing_recommendations'] = [
            'error' => true,
            'error_message' => 'Failed to generate routing recommendations',
            'failed_at' => now()->toIso8601String(),
        ];

        $this->workOrder->update(['metadata' => $metadata]);
    }
}
