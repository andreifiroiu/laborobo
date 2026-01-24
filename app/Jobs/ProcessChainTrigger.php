<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AgentChain;
use App\Models\AgentChainExecution;
use App\Models\AgentTrigger;
use App\Models\Team;
use App\Models\User;
use App\Services\ChainOrchestrator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Queue job for processing agent chain triggers.
 *
 * Executes an agent chain when triggered by an entity status change.
 * The job invokes ChainOrchestrator.executeChain() with the triggering entity context.
 */
class ProcessChainTrigger implements ShouldQueue
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
        public readonly AgentTrigger $trigger,
        public readonly Model $entity,
        public readonly ?User $user = null,
    ) {}

    /**
     * Execute the job.
     *
     * Loads the chain configuration and executes it via ChainOrchestrator
     * with the triggering entity as context.
     */
    public function handle(ChainOrchestrator $orchestrator): void
    {
        Log::info('ProcessChainTrigger: Starting chain execution', [
            'trigger_id' => $this->trigger->id,
            'trigger_name' => $this->trigger->name,
            'chain_id' => $this->trigger->agent_chain_id,
            'entity_type' => $this->trigger->entity_type->value,
            'entity_id' => $this->entity->getKey(),
        ]);

        // Load the chain with its definition
        $chain = $this->getChain();

        if ($chain === null) {
            Log::warning('ProcessChainTrigger: Chain not found or disabled', [
                'trigger_id' => $this->trigger->id,
                'chain_id' => $this->trigger->agent_chain_id,
            ]);

            return;
        }

        // Get the team for the execution
        $team = $this->getTeam();

        if ($team === null) {
            Log::warning('ProcessChainTrigger: Team not found', [
                'trigger_id' => $this->trigger->id,
                'entity_type' => $this->trigger->entity_type->value,
                'entity_id' => $this->entity->getKey(),
            ]);

            return;
        }

        // Build initial context from the triggering entity
        $initialContext = $this->buildInitialContext();

        // Execute the chain
        $execution = $orchestrator->executeChain(
            $chain,
            $team,
            $this->entity,
            $initialContext
        );

        Log::info('ProcessChainTrigger: Chain execution started', [
            'chain_execution_id' => $execution->id,
            'trigger_id' => $this->trigger->id,
            'chain_id' => $chain->id,
            'chain_name' => $chain->name,
        ]);

        // Continue executing steps if the chain is in auto-execute mode
        $this->executeChainSteps($orchestrator, $execution);
    }

    /**
     * Get the chain to execute.
     */
    private function getChain(): ?AgentChain
    {
        $chain = AgentChain::find($this->trigger->agent_chain_id);

        if ($chain === null || ! $chain->enabled) {
            return null;
        }

        return $chain;
    }

    /**
     * Get the team for the execution.
     */
    private function getTeam(): ?Team
    {
        // Get team from trigger
        if ($this->trigger->team_id !== null) {
            return Team::find($this->trigger->team_id);
        }

        // Fallback to entity's team
        if (isset($this->entity->team_id)) {
            return Team::find($this->entity->team_id);
        }

        return null;
    }

    /**
     * Build initial context from the triggering entity.
     */
    private function buildInitialContext(): array
    {
        $context = [
            'trigger' => [
                'id' => $this->trigger->id,
                'name' => $this->trigger->name,
                'entity_type' => $this->trigger->entity_type->value,
                'status_from' => $this->trigger->status_from,
                'status_to' => $this->trigger->status_to,
            ],
            'entity' => [
                'type' => class_basename($this->entity),
                'id' => $this->entity->getKey(),
            ],
        ];

        // Add entity attributes for context
        if (method_exists($this->entity, 'toArray')) {
            $context['entity']['attributes'] = collect($this->entity->toArray())
                ->except(['created_at', 'updated_at', 'deleted_at'])
                ->toArray();
        }

        // Add user context if available
        if ($this->user !== null) {
            $context['triggered_by'] = [
                'user_id' => $this->user->id,
                'user_name' => $this->user->name,
                'user_email' => $this->user->email,
            ];
        }

        return $context;
    }

    /**
     * Execute chain steps automatically.
     *
     * Continues executing steps while the chain is in a running state.
     * This allows the chain to complete automatically without manual intervention.
     */
    private function executeChainSteps(ChainOrchestrator $orchestrator, AgentChainExecution $execution): void
    {
        // Limit iterations to prevent infinite loops
        $maxIterations = 100;
        $iteration = 0;

        while ($iteration < $maxIterations) {
            $execution->refresh();

            // Stop if chain is not running
            if (! $execution->isRunning()) {
                break;
            }

            // Execute the next step
            $step = $orchestrator->executeStep($execution);

            // If no step was returned, the chain is complete
            if ($step === null) {
                break;
            }

            $iteration++;
        }

        if ($iteration >= $maxIterations) {
            Log::warning('ProcessChainTrigger: Max iterations reached', [
                'chain_execution_id' => $execution->id,
                'iterations' => $iteration,
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessChainTrigger job failed', [
            'trigger_id' => $this->trigger->id,
            'trigger_name' => $this->trigger->name,
            'chain_id' => $this->trigger->agent_chain_id,
            'entity_type' => $this->trigger->entity_type->value,
            'entity_id' => $this->entity->getKey(),
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
