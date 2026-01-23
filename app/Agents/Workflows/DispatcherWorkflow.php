<?php

declare(strict_types=1);

namespace App\Agents\Workflows;

use App\Models\AgentWorkflowState;

/**
 * Workflow for the Dispatcher Agent.
 *
 * Defines the step-by-step process for analyzing message threads,
 * extracting work requirements, routing work to team members,
 * and creating draft work orders.
 */
class DispatcherWorkflow extends BaseAgentWorkflow
{
    /**
     * Get the workflow identifier.
     */
    public function getIdentifier(): string
    {
        return 'dispatcher-workflow';
    }

    /**
     * Get the workflow description.
     */
    public function getDescription(): string
    {
        return 'Analyzes message threads to extract work requirements and routes work to appropriate team members based on skills and capacity, creating draft work orders for human review.';
    }

    /**
     * Define the workflow steps.
     *
     * @return array<string, callable> Map of step names to step handlers
     */
    protected function defineSteps(): array
    {
        return [
            'analyze_thread' => fn (AgentWorkflowState $state) => $this->analyzeThread($state),
            'extract_requirements' => fn (AgentWorkflowState $state) => $this->extractRequirements($state),
            'route_work' => fn (AgentWorkflowState $state) => $this->routeWork($state),
            'create_draft' => fn (AgentWorkflowState $state) => $this->createDraft($state),
        ];
    }

    /**
     * Step 1: Analyze the message thread context.
     *
     * Loads and processes the full message thread to understand the context
     * and identify the work request being made.
     *
     * @return array{status: string, thread_analysis: array<string, mixed>}
     */
    protected function analyzeThread(AgentWorkflowState $state): array
    {
        $input = $state->state_data['input'] ?? [];
        $threadId = $input['thread_id'] ?? null;
        $workOrderId = $input['work_order_id'] ?? null;

        // Store thread context in state for subsequent steps
        $threadAnalysis = [
            'message_count' => $input['message_count'] ?? 0,
            'analyzed_at' => now()->toIso8601String(),
        ];

        $this->mergeStateData($state, [
            'thread_id' => $threadId,
            'work_order_id' => $workOrderId,
            'thread_analysis' => $threadAnalysis,
        ]);

        return [
            'status' => 'completed',
            'thread_analysis' => $threadAnalysis,
        ];
    }

    /**
     * Step 2: Extract work requirements from the thread.
     *
     * Parses the message content to identify structured work requirements
     * including title, description, scope, and estimated effort.
     *
     * @return array{status: string, requirements: array<string, mixed>}
     */
    protected function extractRequirements(AgentWorkflowState $state): array
    {
        // Extract requirements structure (actual extraction delegated to service)
        $requirements = [
            'title' => null,
            'description' => null,
            'scope' => null,
            'success_criteria' => [],
            'estimated_hours' => null,
            'priority' => null,
            'deadline' => null,
            'extracted_at' => now()->toIso8601String(),
        ];

        $this->mergeStateData($state, [
            'extracted_requirements' => $requirements,
        ]);

        return [
            'status' => 'completed',
            'requirements' => $requirements,
        ];
    }

    /**
     * Step 3: Route work to appropriate team members.
     *
     * Queries team skills and capacity, calculates routing scores,
     * and identifies the best candidates for the work.
     *
     * @return array{status: string, routing_candidates: array<int, array<string, mixed>>}
     */
    protected function routeWork(AgentWorkflowState $state): array
    {
        // Routing candidates structure (actual calculation delegated to services)
        $routingCandidates = [];

        $this->mergeStateData($state, [
            'routing_candidates' => $routingCandidates,
            'routed_at' => now()->toIso8601String(),
        ]);

        // Check if approval is required for routing decision
        if ($this->getParameter('require_approval_for_routing', false)) {
            $this->pauseForApproval(
                'Routing decision requires approval',
                'Review and approve work routing recommendations'
            );

            return [
                'status' => 'paused',
                'routing_candidates' => $routingCandidates,
            ];
        }

        return [
            'status' => 'completed',
            'routing_candidates' => $routingCandidates,
        ];
    }

    /**
     * Step 4: Create draft work order with routing decision.
     *
     * Creates a draft work order populated with extracted requirements
     * and assigned to the top-ranked candidate.
     *
     * @return array{status: string, draft_work_order_id: int|null}
     */
    protected function createDraft(AgentWorkflowState $state): array
    {
        $requirements = $state->state_data['extracted_requirements'] ?? [];
        $candidates = $state->state_data['routing_candidates'] ?? [];

        // Draft creation structure (actual creation delegated to tool)
        $draftWorkOrderId = null;

        $this->mergeStateData($state, [
            'draft_work_order_id' => $draftWorkOrderId,
            'completed_at' => now()->toIso8601String(),
        ]);

        $this->complete([
            'draft_work_order_id' => $draftWorkOrderId,
            'requirements' => $requirements,
            'routing_candidates' => $candidates,
        ]);

        return [
            'status' => 'completed',
            'draft_work_order_id' => $draftWorkOrderId,
        ];
    }

    /**
     * Hook called when the workflow starts.
     *
     * @param  array<string, mixed>  $input  The input data
     */
    protected function onStart(array $input): void
    {
        // Log workflow start
        $this->mergeStateData($this->state, [
            'started_at' => now()->toIso8601String(),
            'input' => $input,
        ]);
    }

    /**
     * Hook called when the workflow is resumed after approval.
     *
     * @param  array<string, mixed>  $approvalData  Data from the approval
     */
    protected function onResume(array $approvalData): void
    {
        // Update state with approval decision
        $this->mergeStateData($this->state, [
            'approval_data' => $approvalData,
            'resumed_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Hook called when the workflow completes.
     *
     * @param  array<string, mixed>  $result  The final result
     */
    protected function onComplete(array $result): void
    {
        // Log completion
        $this->mergeStateData($this->state, [
            'final_result' => $result,
            'completed_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Merge additional data into the workflow state.
     *
     * @param  AgentWorkflowState  $state  The workflow state to update
     * @param  array<string, mixed>  $data  The data to merge into state_data
     */
    protected function mergeStateData(AgentWorkflowState $state, array $data): void
    {
        $stateData = $state->state_data ?? [];
        $stateData = array_merge($stateData, $data);
        $state->update(['state_data' => $stateData]);
    }
}
