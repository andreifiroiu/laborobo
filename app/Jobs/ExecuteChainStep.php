<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AgentChainExecution;
use App\Models\AgentChainExecutionStep;
use App\Models\AIAgent;
use App\Services\AgentOrchestrator;
use App\ValueObjects\ChainContext;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Queue job for executing a single step within a chain.
 *
 * Used for parallel step execution where multiple steps run concurrently.
 */
class ExecuteChainStep implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    public function __construct(
        public readonly int $chainExecutionId,
        public readonly int $stepIndex,
    ) {}

    /**
     * Execute the job.
     *
     * Executes a single step within a chain execution.
     */
    public function handle(AgentOrchestrator $orchestrator): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $execution = AgentChainExecution::find($this->chainExecutionId);

        if ($execution === null) {
            Log::warning('Chain execution not found for step execution', [
                'chain_execution_id' => $this->chainExecutionId,
                'step_index' => $this->stepIndex,
            ]);

            return;
        }

        if ($execution->isTerminal()) {
            Log::info('Chain execution already terminal, skipping step', [
                'chain_execution_id' => $this->chainExecutionId,
                'step_index' => $this->stepIndex,
                'status' => $execution->execution_status->value,
            ]);

            return;
        }

        $chain = $execution->chain;
        $steps = $chain->getSteps();

        if ($this->stepIndex >= count($steps)) {
            Log::warning('Step index out of bounds', [
                'chain_execution_id' => $this->chainExecutionId,
                'step_index' => $this->stepIndex,
                'total_steps' => count($steps),
            ]);

            return;
        }

        $stepConfig = $steps[$this->stepIndex];

        Log::info('Executing chain step via job', [
            'chain_execution_id' => $this->chainExecutionId,
            'step_index' => $this->stepIndex,
            'agent_id' => $stepConfig['agent_id'] ?? null,
        ]);

        try {
            DB::transaction(function () use ($execution, $stepConfig, $orchestrator) {
                // Find or create the step record
                $executionStep = AgentChainExecutionStep::where('agent_chain_execution_id', $this->chainExecutionId)
                    ->where('step_index', $this->stepIndex)
                    ->first();

                if ($executionStep === null) {
                    $executionStep = AgentChainExecutionStep::create([
                        'agent_chain_execution_id' => $this->chainExecutionId,
                        'step_index' => $this->stepIndex,
                        'status' => 'running',
                        'started_at' => now(),
                        'output_data' => [],
                    ]);
                } else {
                    $executionStep->update([
                        'status' => 'running',
                        'started_at' => now(),
                    ]);
                }

                // Execute the agent workflow
                $workflowState = null;
                if (isset($stepConfig['workflow_class']) && isset($stepConfig['agent_id'])) {
                    $agent = AIAgent::find($stepConfig['agent_id']);
                    if ($agent !== null) {
                        $chainContext = ChainContext::fromArray($execution->chain_context ?? []);

                        $input = [
                            'chain_execution_id' => $execution->id,
                            'step_index' => $this->stepIndex,
                            'previous_outputs' => $chainContext->getAllOutputs(),
                            'triggerable_type' => $execution->triggerable_type,
                            'triggerable_id' => $execution->triggerable_id,
                        ];

                        $workflowState = $orchestrator->execute(
                            $stepConfig['workflow_class'],
                            $input,
                            $execution->team,
                            $agent,
                        );

                        $executionStep->update([
                            'agent_workflow_state_id' => $workflowState->id,
                        ]);
                    }
                }

                // Mark step as completed
                $executionStep->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);

                Log::info('Chain step completed via job', [
                    'chain_execution_id' => $this->chainExecutionId,
                    'step_index' => $this->stepIndex,
                    'workflow_state_id' => $workflowState?->id,
                ]);
            });
        } catch (\Throwable $e) {
            $this->markStepAsFailed($e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $this->markStepAsFailed($exception->getMessage());

        Log::error('ExecuteChainStep job failed', [
            'chain_execution_id' => $this->chainExecutionId,
            'step_index' => $this->stepIndex,
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Mark the step as failed in the database.
     */
    private function markStepAsFailed(string $errorMessage): void
    {
        $executionStep = AgentChainExecutionStep::where('agent_chain_execution_id', $this->chainExecutionId)
            ->where('step_index', $this->stepIndex)
            ->first();

        if ($executionStep !== null) {
            $executionStep->update([
                'status' => 'failed',
                'completed_at' => now(),
                'output_data' => array_merge(
                    $executionStep->output_data ?? [],
                    ['error' => $errorMessage]
                ),
            ]);
        }
    }
}
