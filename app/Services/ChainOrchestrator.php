<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ChainExecutionStatus;
use App\Jobs\ExecuteChainStep;
use App\Models\AgentChain;
use App\Models\AgentChainExecution;
use App\Models\AgentChainExecutionStep;
use App\Models\AgentWorkflowState;
use App\Models\AIAgent;
use App\Models\Team;
use App\ValueObjects\AgentContext;
use App\ValueObjects\ChainContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for orchestrating multi-agent chain execution.
 *
 * Handles chain execution, pause/resume capability, conditional branching,
 * context accumulation across chain steps, and chain-scoped memory management.
 */
class ChainOrchestrator
{
    public function __construct(
        private readonly AgentOrchestrator $agentOrchestrator,
        private readonly ContextBuilder $contextBuilder,
        private readonly AgentApprovalService $approvalService,
        private readonly AgentMemoryService $memoryService,
    ) {}

    /**
     * Execute a chain for a team.
     *
     * Creates an AgentChainExecution record and begins chain execution.
     * The chain can be paused and resumed using the returned execution object.
     *
     * @param  AgentChain  $chain  The chain to execute
     * @param  Team  $team  The team executing the chain
     * @param  Model|null  $triggerableEntity  The entity that triggered this chain (optional)
     * @param  array<string, mixed>  $initialContext  Initial context data (optional)
     * @return AgentChainExecution The created chain execution
     */
    public function executeChain(
        AgentChain $chain,
        Team $team,
        ?Model $triggerableEntity = null,
        array $initialContext = [],
    ): AgentChainExecution {
        return DB::transaction(function () use ($chain, $team, $triggerableEntity, $initialContext) {
            $execution = AgentChainExecution::create([
                'team_id' => $team->id,
                'agent_chain_id' => $chain->id,
                'current_step_index' => 0,
                'execution_status' => ChainExecutionStatus::Running,
                'chain_context' => [
                    'steps' => [],
                    'accumulated_context' => $initialContext,
                    'metadata' => [
                        'chain_name' => $chain->name,
                        'started_at' => now()->toIso8601String(),
                    ],
                ],
                'started_at' => now(),
                'triggerable_type' => $triggerableEntity ? get_class($triggerableEntity) : null,
                'triggerable_id' => $triggerableEntity?->getKey(),
            ]);

            Log::info('Chain execution started', [
                'chain_execution_id' => $execution->id,
                'chain_id' => $chain->id,
                'chain_name' => $chain->name,
                'team_id' => $team->id,
                'triggerable_type' => $triggerableEntity ? class_basename($triggerableEntity) : null,
                'triggerable_id' => $triggerableEntity?->getKey(),
            ]);

            return $execution;
        });
    }

    /**
     * Execute the current step in the chain.
     *
     * Invokes the agent's workflow via AgentOrchestrator and updates the chain state.
     *
     * @param  AgentChainExecution  $execution  The chain execution
     * @param  array<string, mixed>  $stepOutput  The output from the step (for testing or manual completion)
     * @return AgentChainExecutionStep|null The created step record or null if chain is completed
     */
    public function executeStep(
        AgentChainExecution $execution,
        array $stepOutput = [],
    ): ?AgentChainExecutionStep {
        if ($execution->isTerminal()) {
            Log::warning('Attempted to execute step on terminal chain execution', [
                'chain_execution_id' => $execution->id,
                'status' => $execution->execution_status->value,
            ]);

            return null;
        }

        if ($execution->isPaused()) {
            Log::warning('Attempted to execute step on paused chain execution', [
                'chain_execution_id' => $execution->id,
            ]);

            return null;
        }

        $chain = $execution->chain;
        $steps = $chain->getSteps();
        $currentIndex = $execution->current_step_index;

        if ($currentIndex >= count($steps)) {
            $this->complete($execution);

            return null;
        }

        $stepConfig = $steps[$currentIndex];

        return DB::transaction(function () use ($execution, $stepConfig, $currentIndex, $stepOutput, $steps) {
            // Create the execution step record
            $executionStep = AgentChainExecutionStep::create([
                'agent_chain_execution_id' => $execution->id,
                'agent_workflow_state_id' => null, // Will be set when workflow is created
                'step_index' => $currentIndex,
                'status' => 'running',
                'started_at' => now(),
                'output_data' => [],
            ]);

            Log::info('Chain step execution started', [
                'chain_execution_id' => $execution->id,
                'step_index' => $currentIndex,
                'agent_id' => $stepConfig['agent_id'] ?? null,
            ]);

            // Execute the agent workflow if we have a workflow class
            $workflowState = null;
            if (isset($stepConfig['workflow_class']) && isset($stepConfig['agent_id'])) {
                $agent = AIAgent::find($stepConfig['agent_id']);
                if ($agent !== null) {
                    $workflowState = $this->executeAgentWorkflow(
                        $execution,
                        $agent,
                        $stepConfig['workflow_class'],
                        $currentIndex,
                    );

                    $executionStep->update([
                        'agent_workflow_state_id' => $workflowState->id,
                    ]);
                }
            }

            // Complete the step with output
            $this->completeStep($execution, $executionStep, $stepOutput, $stepConfig, count($steps));

            return $executionStep;
        });
    }

    /**
     * Execute parallel steps within the same step group.
     *
     * Dispatches all steps in the parallel group as separate queue jobs.
     */
    public function executeParallelStepGroup(AgentChainExecution $execution): void
    {
        if ($execution->isTerminal() || $execution->isPaused()) {
            return;
        }

        $chain = $execution->chain;
        $steps = $chain->getSteps();
        $currentIndex = $execution->current_step_index;

        if ($currentIndex >= count($steps)) {
            $this->complete($execution);

            return;
        }

        $currentStep = $steps[$currentIndex];
        $stepGroup = $currentStep['step_group'] ?? null;

        if ($stepGroup === null || ($currentStep['execution_mode'] ?? 'sequential') !== 'parallel') {
            // Not a parallel step, execute normally
            $this->executeStep($execution);

            return;
        }

        // Find all steps in the same parallel group
        $parallelStepIndices = [];
        foreach ($steps as $index => $step) {
            if (($step['step_group'] ?? null) === $stepGroup) {
                $parallelStepIndices[] = $index;
            }
        }

        DB::transaction(function () use ($execution, $steps, $parallelStepIndices) {
            $jobs = [];

            foreach ($parallelStepIndices as $stepIndex) {
                $stepConfig = $steps[$stepIndex];

                // Create execution step record
                $executionStep = AgentChainExecutionStep::create([
                    'agent_chain_execution_id' => $execution->id,
                    'agent_workflow_state_id' => null,
                    'step_index' => $stepIndex,
                    'status' => 'pending',
                    'started_at' => now(),
                    'output_data' => [],
                ]);

                // Dispatch job for parallel execution
                $jobs[] = new ExecuteChainStep($execution->id, $stepIndex);
            }

            // Dispatch all jobs as a batch
            if (! empty($jobs)) {
                Bus::batch($jobs)
                    ->name("chain-execution-{$execution->id}-parallel-group")
                    ->dispatch();
            }
        });

        Log::info('Parallel step group dispatched', [
            'chain_execution_id' => $execution->id,
            'step_group' => $stepGroup,
            'step_count' => count($parallelStepIndices),
        ]);
    }

    /**
     * Complete a parallel step group and move to the next step.
     *
     * @param  AgentChainExecution  $execution  The chain execution
     * @param  string  $stepGroup  The step group that completed
     */
    public function completeParallelGroup(AgentChainExecution $execution, string $stepGroup): void
    {
        $chain = $execution->chain;
        $steps = $chain->getSteps();

        // Find the next step after this parallel group
        $maxParallelIndex = 0;
        foreach ($steps as $index => $step) {
            if (($step['step_group'] ?? null) === $stepGroup) {
                $maxParallelIndex = max($maxParallelIndex, $index);
            }
        }

        $nextIndex = $maxParallelIndex + 1;

        // Aggregate outputs from all parallel steps
        $parallelOutputs = [];
        $parallelSteps = $execution->steps()
            ->whereIn('step_index', array_keys(array_filter($steps, fn ($s) => ($s['step_group'] ?? null) === $stepGroup)))
            ->get();

        foreach ($parallelSteps as $step) {
            $parallelOutputs[$step->step_index] = $step->output_data;
        }

        // Update chain context with aggregated parallel outputs
        $chainContext = ChainContext::fromArray($execution->chain_context ?? []);
        $chainContext = $chainContext->withMetadata([
            'parallel_group_'.$stepGroup => [
                'outputs' => $parallelOutputs,
                'completed_at' => now()->toIso8601String(),
            ],
        ]);

        DB::transaction(function () use ($execution, $nextIndex, $chainContext, $steps) {
            if ($nextIndex >= count($steps)) {
                $this->complete($execution);
            } else {
                $execution->update([
                    'current_step_index' => $nextIndex,
                    'chain_context' => $chainContext->toArray(),
                ]);
            }
        });

        Log::info('Parallel group completed', [
            'chain_execution_id' => $execution->id,
            'step_group' => $stepGroup,
            'next_step_index' => $nextIndex,
        ]);
    }

    /**
     * Pause the chain execution.
     *
     * @param  AgentChainExecution  $execution  The chain execution to pause
     * @param  string  $reason  The reason for pausing
     */
    public function pause(AgentChainExecution $execution, string $reason): void
    {
        if ($execution->isTerminal()) {
            return;
        }

        DB::transaction(function () use ($execution, $reason) {
            $chainContext = ChainContext::fromArray($execution->chain_context ?? []);
            $chainContext = $chainContext->withPauseReason($reason);

            $execution->update([
                'execution_status' => ChainExecutionStatus::Paused,
                'paused_at' => now(),
                'chain_context' => $chainContext->toArray(),
            ]);

            Log::info('Chain execution paused', [
                'chain_execution_id' => $execution->id,
                'reason' => $reason,
            ]);
        });
    }

    /**
     * Resume a paused chain execution.
     *
     * @param  AgentChainExecution  $execution  The chain execution to resume
     * @param  array<string, mixed>  $resumeData  Data from the approval or resume action
     */
    public function resume(AgentChainExecution $execution, array $resumeData = []): void
    {
        if (! $execution->isPaused()) {
            return;
        }

        DB::transaction(function () use ($execution, $resumeData) {
            $chainContext = ChainContext::fromArray($execution->chain_context ?? []);
            $chainContext = $chainContext->withResumeData($resumeData);

            $execution->update([
                'execution_status' => ChainExecutionStatus::Running,
                'resumed_at' => now(),
                'chain_context' => $chainContext->toArray(),
            ]);

            Log::info('Chain execution resumed', [
                'chain_execution_id' => $execution->id,
                'resume_data' => $resumeData,
            ]);
        });
    }

    /**
     * Mark the chain execution as completed.
     *
     * Cleans up chain-scoped memory after completion.
     *
     * @param  AgentChainExecution  $execution  The chain execution to complete
     * @param  array<string, mixed>  $result  Optional final result data
     */
    public function complete(AgentChainExecution $execution, array $result = []): void
    {
        if ($execution->isTerminal()) {
            return;
        }

        DB::transaction(function () use ($execution, $result) {
            $chainContext = ChainContext::fromArray($execution->chain_context ?? []);
            $chainContext = $chainContext->withMetadata([
                'completed_at' => now()->toIso8601String(),
                'result' => $result,
            ]);

            $execution->update([
                'execution_status' => ChainExecutionStatus::Completed,
                'completed_at' => now(),
                'chain_context' => $chainContext->toArray(),
            ]);

            // Clean up chain-scoped memory
            $this->cleanupChainMemory($execution);

            Log::info('Chain execution completed', [
                'chain_execution_id' => $execution->id,
                'total_steps' => $execution->steps()->count(),
            ]);
        });
    }

    /**
     * Mark the chain execution as failed.
     *
     * Cleans up chain-scoped memory after failure.
     *
     * @param  AgentChainExecution  $execution  The chain execution that failed
     * @param  string  $errorMessage  The error message
     */
    public function fail(AgentChainExecution $execution, string $errorMessage): void
    {
        if ($execution->isTerminal()) {
            return;
        }

        DB::transaction(function () use ($execution, $errorMessage) {
            // Mark the current step as failed if it exists
            $currentStep = $execution->steps()
                ->where('step_index', $execution->current_step_index)
                ->where('status', 'running')
                ->first();

            if ($currentStep !== null) {
                $currentStep->update([
                    'status' => 'failed',
                    'completed_at' => now(),
                    'output_data' => array_merge(
                        $currentStep->output_data ?? [],
                        ['error' => $errorMessage]
                    ),
                ]);
            } else {
                // Create a failed step record if one doesn't exist
                AgentChainExecutionStep::create([
                    'agent_chain_execution_id' => $execution->id,
                    'step_index' => $execution->current_step_index,
                    'status' => 'failed',
                    'started_at' => now(),
                    'completed_at' => now(),
                    'output_data' => ['error' => $errorMessage],
                ]);
            }

            $chainContext = ChainContext::fromArray($execution->chain_context ?? []);
            $chainContext = $chainContext->withMetadata([
                'failed_at' => now()->toIso8601String(),
                'error' => $errorMessage,
            ]);

            $execution->update([
                'execution_status' => ChainExecutionStatus::Failed,
                'failed_at' => now(),
                'error_message' => $errorMessage,
                'chain_context' => $chainContext->toArray(),
            ]);

            // Clean up chain-scoped memory
            $this->cleanupChainMemory($execution);

            Log::error('Chain execution failed', [
                'chain_execution_id' => $execution->id,
                'step_index' => $execution->current_step_index,
                'error' => $errorMessage,
            ]);
        });
    }

    /**
     * Request approval at chain level.
     *
     * Pauses the chain and creates an approval request.
     *
     * @param  AgentChainExecution  $execution  The chain execution
     * @param  string  $reason  The reason for requesting approval
     */
    public function requestApproval(AgentChainExecution $execution, string $reason): void
    {
        $this->pause($execution, $reason);

        // Get the current step's workflow state if available
        $currentStep = $execution->steps()
            ->where('step_index', $execution->current_step_index)
            ->first();

        if ($currentStep?->workflowState !== null) {
            $this->approvalService->requestApproval(
                $currentStep->workflowState,
                "Chain approval required: {$reason}"
            );
        }

        Log::info('Chain approval requested', [
            'chain_execution_id' => $execution->id,
            'reason' => $reason,
        ]);
    }

    /**
     * Get pending approvals for chain executions.
     *
     * @param  Team  $team  The team to get approvals for
     * @return \Illuminate\Database\Eloquent\Collection<int, AgentChainExecution>
     */
    public function getPendingApprovals(Team $team)
    {
        return AgentChainExecution::query()
            ->forTeam($team->id)
            ->paused()
            ->with('chain')
            ->get();
    }

    /**
     * Build context for a step in the chain.
     *
     * Builds an AgentContext from the chain context for use by agents.
     *
     * @param  AgentChainExecution  $execution  The chain execution
     * @param  AIAgent  $agent  The agent that will use this context
     * @param  int  $maxTokens  Maximum tokens for the context
     * @return AgentContext The assembled context
     */
    public function buildStepContext(
        AgentChainExecution $execution,
        AIAgent $agent,
        int $maxTokens = 4000,
    ): AgentContext {
        $chainContext = ChainContext::fromArray($execution->chain_context ?? []);

        return $this->contextBuilder->buildFromChainContext(
            $chainContext,
            $execution,
            $agent,
            $maxTokens
        );
    }

    /**
     * Execute an individual agent workflow within the chain.
     *
     * @param  AgentChainExecution  $execution  The chain execution
     * @param  AIAgent  $agent  The agent to execute
     * @param  string  $workflowClass  The workflow class to run
     * @param  int  $stepIndex  The current step index
     * @return AgentWorkflowState The created workflow state
     */
    private function executeAgentWorkflow(
        AgentChainExecution $execution,
        AIAgent $agent,
        string $workflowClass,
        int $stepIndex,
    ): AgentWorkflowState {
        // Build context for the workflow
        $agentContext = $this->buildStepContext($execution, $agent);

        // Build input for the workflow including chain context
        $chainContext = ChainContext::fromArray($execution->chain_context ?? []);
        $input = [
            'chain_execution_id' => $execution->id,
            'step_index' => $stepIndex,
            'previous_outputs' => $chainContext->getAllOutputs(),
            'triggerable_type' => $execution->triggerable_type,
            'triggerable_id' => $execution->triggerable_id,
            'context' => $agentContext->toArray(),
        ];

        return $this->agentOrchestrator->execute(
            $workflowClass,
            $input,
            $execution->team,
            $agent,
        );
    }

    /**
     * Complete a step and update the chain context.
     *
     * @param  AgentChainExecution  $execution  The chain execution
     * @param  AgentChainExecutionStep  $step  The step to complete
     * @param  array<string, mixed>  $output  The output from the step
     * @param  array<string, mixed>  $stepConfig  The step configuration
     * @param  int  $totalSteps  The total number of steps in the chain
     */
    private function completeStep(
        AgentChainExecution $execution,
        AgentChainExecutionStep $step,
        array $output,
        array $stepConfig,
        int $totalSteps,
    ): void {
        $stepIndex = $step->step_index;

        // Update the step record
        $step->update([
            'status' => 'completed',
            'completed_at' => now(),
            'output_data' => $output,
        ]);

        // Update chain context with step output
        $chainContext = ChainContext::fromArray($execution->chain_context ?? []);
        $chainContext = $chainContext->withStepOutput(
            $stepIndex,
            $output,
            $stepConfig['agent_id'] ?? null
        );

        // Evaluate conditional branching
        $nextStepIndex = $this->evaluateNextStep($execution, $chainContext, $stepConfig, $stepIndex, $totalSteps);

        // Check if chain should terminate or complete
        if ($nextStepIndex === -1 || $nextStepIndex >= $totalSteps) {
            $execution->update([
                'chain_context' => $chainContext->toArray(),
            ]);
            $this->complete($execution);

            return;
        }

        // Update execution state
        $execution->update([
            'current_step_index' => $nextStepIndex,
            'chain_context' => $chainContext->toArray(),
        ]);

        Log::info('Chain step completed', [
            'chain_execution_id' => $execution->id,
            'completed_step_index' => $stepIndex,
            'next_step_index' => $nextStepIndex,
        ]);
    }

    /**
     * Evaluate the next step based on conditional branching rules.
     *
     * @param  AgentChainExecution  $execution  The chain execution
     * @param  ChainContext  $chainContext  The current chain context
     * @param  array<string, mixed>  $stepConfig  The current step configuration
     * @param  int  $currentIndex  The current step index
     * @param  int  $totalSteps  The total number of steps
     * @return int The next step index (-1 for terminate)
     */
    private function evaluateNextStep(
        AgentChainExecution $execution,
        ChainContext $chainContext,
        array $stepConfig,
        int $currentIndex,
        int $totalSteps,
    ): int {
        $defaultNextIndex = $currentIndex + 1;

        // Check if this is the last step
        if ($defaultNextIndex >= $totalSteps) {
            return $defaultNextIndex; // Will trigger completion
        }

        // Evaluate next_step_conditions if present
        $conditions = $stepConfig['next_step_conditions'] ?? [];

        if (empty($conditions)) {
            return $defaultNextIndex;
        }

        foreach ($conditions as $conditionConfig) {
            $condition = $conditionConfig['condition'] ?? null;
            $action = $conditionConfig['action'] ?? 'goto';

            // If no condition, this is the default action
            if ($condition === null || $chainContext->evaluateCondition($condition)) {
                Log::info('Chain branching decision', [
                    'chain_execution_id' => $execution->id,
                    'condition' => $condition,
                    'action' => $action,
                    'target_step' => $conditionConfig['target_step'] ?? null,
                ]);

                return match ($action) {
                    'skip' => $defaultNextIndex + 1,
                    'goto' => $conditionConfig['target_step'] ?? $defaultNextIndex,
                    'terminate' => -1,
                    default => $defaultNextIndex,
                };
            }
        }

        return $defaultNextIndex;
    }

    /**
     * Clean up chain-scoped memory when chain execution is terminal.
     *
     * @param  AgentChainExecution  $execution  The chain execution
     */
    private function cleanupChainMemory(AgentChainExecution $execution): void
    {
        $deletedCount = $this->memoryService->clearChainMemory(
            $execution->team,
            $execution->id
        );

        if ($deletedCount > 0) {
            Log::info('Chain memory cleaned up', [
                'chain_execution_id' => $execution->id,
                'deleted_count' => $deletedCount,
            ]);
        }
    }
}
