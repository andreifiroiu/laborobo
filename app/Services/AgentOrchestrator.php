<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AgentWorkflowState;
use App\Models\AIAgent;
use App\Models\Team;
use App\Models\WorkflowCustomization;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for coordinating multi-agent workflows.
 *
 * Handles workflow execution, pause/resume capability,
 * and state persistence for durable workflow execution.
 */
class AgentOrchestrator
{
    /**
     * Execute a workflow for a team.
     *
     * Creates an AgentWorkflowState record and begins workflow execution.
     * The workflow can be paused and resumed using the returned state object.
     *
     * @param  string  $workflowClass  The fully qualified class name of the workflow
     * @param  array<string, mixed>  $input  Input data for the workflow
     * @param  Team  $team  The team executing the workflow
     * @param  AIAgent|null  $agent  The agent running the workflow (optional)
     * @return AgentWorkflowState The created workflow state
     */
    public function execute(
        string $workflowClass,
        array $input,
        Team $team,
        ?AIAgent $agent = null,
    ): AgentWorkflowState {
        return DB::transaction(function () use ($workflowClass, $input, $team, $agent) {
            // Load any customizations for this workflow
            $customization = $this->loadCustomization($team, $workflowClass);

            // Create the workflow state
            $state = AgentWorkflowState::create([
                'team_id' => $team->id,
                'ai_agent_id' => $agent?->id,
                'workflow_class' => $workflowClass,
                'current_node' => 'start',
                'state_data' => [
                    'input' => $input,
                    'customization_id' => $customization?->id,
                    'started_at' => now()->toIso8601String(),
                ],
                'approval_required' => false,
            ]);

            Log::info('Workflow started', [
                'workflow_state_id' => $state->id,
                'workflow_class' => $workflowClass,
                'team_id' => $team->id,
                'agent_id' => $agent?->id,
            ]);

            return $state;
        });
    }

    /**
     * Pause a running workflow.
     *
     * Sets the workflow state to paused and records the reason.
     * The workflow can later be resumed using the resume() method.
     *
     * @param  AgentWorkflowState  $state  The workflow state to pause
     * @param  string  $reason  The reason for pausing
     */
    public function pause(AgentWorkflowState $state, string $reason): void
    {
        DB::transaction(function () use ($state, $reason) {
            $state->update([
                'paused_at' => now(),
                'pause_reason' => $reason,
                'approval_required' => true,
            ]);

            Log::info('Workflow paused', [
                'workflow_state_id' => $state->id,
                'reason' => $reason,
            ]);
        });
    }

    /**
     * Resume a paused workflow.
     *
     * Clears the paused state and merges approval data into the state.
     * The workflow will continue from where it was paused.
     *
     * @param  AgentWorkflowState  $state  The workflow state to resume
     * @param  array<string, mixed>  $approvalData  Data from the approval (e.g., approver info)
     */
    public function resume(AgentWorkflowState $state, array $approvalData = []): void
    {
        DB::transaction(function () use ($state, $approvalData) {
            $stateData = $state->state_data ?? [];
            $stateData['approval_data'] = $approvalData;
            $stateData['resumed_at'] = now()->toIso8601String();

            $state->update([
                'paused_at' => null,
                'resumed_at' => now(),
                'approval_required' => false,
                'state_data' => $stateData,
            ]);

            Log::info('Workflow resumed', [
                'workflow_state_id' => $state->id,
                'approval_data' => $approvalData,
            ]);
        });
    }

    /**
     * Mark a workflow as completed.
     *
     * @param  AgentWorkflowState  $state  The workflow state to complete
     * @param  array<string, mixed>  $result  The result data from the workflow
     */
    public function complete(AgentWorkflowState $state, array $result = []): void
    {
        DB::transaction(function () use ($state, $result) {
            $stateData = $state->state_data ?? [];
            $stateData['result'] = $result;
            $stateData['completed_at'] = now()->toIso8601String();

            $state->update([
                'current_node' => 'completed',
                'completed_at' => now(),
                'state_data' => $stateData,
            ]);

            Log::info('Workflow completed', [
                'workflow_state_id' => $state->id,
            ]);
        });
    }

    /**
     * Update the current node of a workflow.
     *
     * @param  AgentWorkflowState  $state  The workflow state to update
     * @param  string  $nodeName  The name of the current node
     * @param  array<string, mixed>  $additionalData  Additional data to merge into state
     */
    public function updateNode(
        AgentWorkflowState $state,
        string $nodeName,
        array $additionalData = [],
    ): void {
        $stateData = $state->state_data ?? [];
        $stateData = array_merge($stateData, $additionalData);

        $state->update([
            'current_node' => $nodeName,
            'state_data' => $stateData,
        ]);
    }

    /**
     * Load workflow customization for a team and workflow class.
     *
     * @param  Team  $team  The team
     * @param  string  $workflowClass  The workflow class name
     * @return WorkflowCustomization|null The customization if found and enabled
     */
    public function loadCustomization(Team $team, string $workflowClass): ?WorkflowCustomization
    {
        return WorkflowCustomization::query()
            ->forTeam($team->id)
            ->where('workflow_class', $workflowClass)
            ->enabled()
            ->first();
    }

    /**
     * Get all paused workflows for a team requiring approval.
     *
     * @param  Team  $team  The team to get workflows for
     * @return \Illuminate\Database\Eloquent\Collection<int, AgentWorkflowState>
     */
    public function getPendingApprovals(Team $team)
    {
        return AgentWorkflowState::query()
            ->forTeam($team->id)
            ->requiringApproval()
            ->with('agent')
            ->get();
    }

    /**
     * Check if a step should be skipped based on customization.
     *
     * @param  AgentWorkflowState  $state  The workflow state
     * @param  string  $stepName  The step name to check
     * @return bool True if the step should be skipped
     */
    public function shouldSkipStep(AgentWorkflowState $state, string $stepName): bool
    {
        $customizationId = $state->state_data['customization_id'] ?? null;

        if ($customizationId === null) {
            return false;
        }

        $customization = WorkflowCustomization::find($customizationId);

        if ($customization === null || !$customization->enabled) {
            return false;
        }

        return $customization->isStepDisabled($stepName);
    }

    /**
     * Get a parameter value from workflow customization.
     *
     * @param  AgentWorkflowState  $state  The workflow state
     * @param  string  $key  The parameter key
     * @param  mixed  $default  Default value if not found
     * @return mixed The parameter value
     */
    public function getParameter(AgentWorkflowState $state, string $key, mixed $default = null): mixed
    {
        $customizationId = $state->state_data['customization_id'] ?? null;

        if ($customizationId === null) {
            return $default;
        }

        $customization = WorkflowCustomization::find($customizationId);

        if ($customization === null || !$customization->enabled) {
            return $default;
        }

        return $customization->getParameter($key, $default);
    }
}
