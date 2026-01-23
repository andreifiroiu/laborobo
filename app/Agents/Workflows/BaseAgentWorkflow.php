<?php

declare(strict_types=1);

namespace App\Agents\Workflows;

use App\Models\AgentWorkflowState;
use App\Models\AIAgent;
use App\Models\Team;
use App\Models\WorkflowCustomization;
use App\Services\AgentApprovalService;
use App\Services\AgentOrchestrator;

/**
 * Abstract base class for agent workflows.
 *
 * Provides common functionality for workflow execution including:
 * - State management
 * - Pause/resume integration
 * - Customization loading
 * - Human checkpoint integration
 */
abstract class BaseAgentWorkflow
{
    protected AgentWorkflowState $state;

    protected ?WorkflowCustomization $customization = null;

    protected AgentOrchestrator $orchestrator;

    protected AgentApprovalService $approvalService;

    /**
     * Get the workflow identifier.
     */
    abstract public function getIdentifier(): string;

    /**
     * Get the workflow description.
     */
    abstract public function getDescription(): string;

    /**
     * Define the workflow steps.
     *
     * @return array<string, callable> Map of step names to step handlers
     */
    abstract protected function defineSteps(): array;

    /**
     * Initialize the workflow with dependencies.
     */
    public function __construct(
        AgentOrchestrator $orchestrator,
        AgentApprovalService $approvalService,
    ) {
        $this->orchestrator = $orchestrator;
        $this->approvalService = $approvalService;
    }

    /**
     * Start a new workflow execution.
     *
     * @param  array<string, mixed>  $input  The input data for the workflow
     * @param  Team  $team  The team executing the workflow
     * @param  AIAgent|null  $agent  The agent running the workflow
     * @return AgentWorkflowState The created workflow state
     */
    public function start(array $input, Team $team, ?AIAgent $agent = null): AgentWorkflowState
    {
        $this->state = $this->orchestrator->execute(
            static::class,
            $input,
            $team,
            $agent
        );

        $this->loadCustomization();
        $this->onStart($input);

        return $this->state;
    }

    /**
     * Resume a paused workflow.
     *
     * @param  AgentWorkflowState  $state  The workflow state to resume
     * @param  array<string, mixed>  $approvalData  Data from the approval
     */
    public function resume(AgentWorkflowState $state, array $approvalData = []): void
    {
        $this->state = $state;
        $this->loadCustomization();

        $this->orchestrator->resume($state, $approvalData);
        $this->onResume($approvalData);
    }

    /**
     * Set the current workflow state.
     *
     * Used when continuing a workflow that was previously started,
     * allowing the workflow to run from an existing state.
     *
     * @param  AgentWorkflowState  $state  The workflow state to set
     */
    public function setCurrentState(AgentWorkflowState $state): void
    {
        $this->state = $state;
        $this->loadCustomization();
    }

    /**
     * Execute the next step in the workflow.
     *
     * @return bool True if the workflow should continue, false if paused or completed
     */
    public function executeNextStep(): bool
    {
        $steps = $this->defineSteps();
        $currentNode = $this->state->current_node;

        if ($currentNode === 'completed' || $this->state->isCompleted()) {
            return false;
        }

        if ($this->state->isPaused()) {
            return false;
        }

        // Get the next step to execute
        $stepNames = array_keys($steps);
        $currentIndex = array_search($currentNode, $stepNames, true);

        if ($currentIndex === false) {
            // Start from the first step
            $currentIndex = -1;
        }

        $nextIndex = $currentIndex + 1;

        if ($nextIndex >= count($stepNames)) {
            $this->complete();

            return false;
        }

        $nextStepName = $stepNames[$nextIndex];

        // Check if step should be skipped due to customization
        if ($this->orchestrator->shouldSkipStep($this->state, $nextStepName)) {
            $this->orchestrator->updateNode($this->state, $nextStepName);

            return $this->executeNextStep(); // Recursively skip to next
        }

        // Execute the step
        $this->orchestrator->updateNode($this->state, $nextStepName);
        $this->beforeStep($nextStepName);

        $stepHandler = $steps[$nextStepName];
        $result = $stepHandler($this->state);

        $this->afterStep($nextStepName, $result);

        // Check if step paused the workflow
        $this->state->refresh();

        return ! $this->state->isPaused() && ! $this->state->isCompleted();
    }

    /**
     * Run the entire workflow to completion or pause.
     */
    public function run(): void
    {
        while ($this->executeNextStep()) {
            // Continue executing steps
        }
    }

    /**
     * Pause the workflow for human approval.
     *
     * @param  string  $reason  The reason for pausing
     * @param  string  $actionDescription  Description of the action requiring approval
     * @return \App\Models\InboxItem The created inbox item
     */
    protected function pauseForApproval(string $_reason, string $actionDescription): \App\Models\InboxItem
    {
        return $this->approvalService->requestApproval($this->state, $actionDescription);
    }

    /**
     * Complete the workflow.
     *
     * @param  array<string, mixed>  $result  The result data
     */
    protected function complete(array $result = []): void
    {
        $this->orchestrator->complete($this->state, $result);
        $this->onComplete($result);
    }

    /**
     * Get a parameter value from customization or default.
     */
    protected function getParameter(string $key, mixed $default = null): mixed
    {
        return $this->orchestrator->getParameter($this->state, $key, $default);
    }

    /**
     * Get the current workflow state.
     */
    public function getState(): AgentWorkflowState
    {
        return $this->state;
    }

    /**
     * Get the workflow customization.
     */
    public function getCustomization(): ?WorkflowCustomization
    {
        return $this->customization;
    }

    /**
     * Load the customization for this workflow.
     */
    protected function loadCustomization(): void
    {
        $customizationId = $this->state->state_data['customization_id'] ?? null;

        if ($customizationId !== null) {
            $this->customization = WorkflowCustomization::find($customizationId);
        }
    }

    /**
     * Hook called when the workflow starts.
     *
     * @param  array<string, mixed>  $input  The input data
     */
    protected function onStart(array $_input): void
    {
        // Override in subclass if needed
    }

    /**
     * Hook called when the workflow is resumed.
     *
     * @param  array<string, mixed>  $approvalData  Data from the approval
     */
    protected function onResume(array $approvalData): void
    {
        // Override in subclass if needed
    }

    /**
     * Hook called before each step.
     *
     * @param  string  $stepName  The name of the step about to execute
     */
    protected function beforeStep(string $stepName): void
    {
        // Override in subclass if needed
    }

    /**
     * Hook called after each step.
     *
     * @param  string  $stepName  The name of the step that executed
     * @param  mixed  $result  The result from the step
     */
    protected function afterStep(string $stepName, mixed $result): void
    {
        // Override in subclass if needed
    }

    /**
     * Hook called when the workflow completes.
     *
     * @param  array<string, mixed>  $result  The final result
     */
    protected function onComplete(array $result): void
    {
        // Override in subclass if needed
    }
}
