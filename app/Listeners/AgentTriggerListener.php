<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\TriggerEntityType;
use App\Events\DeliverableStatusChanged;
use App\Events\TaskStatusChanged;
use App\Events\WorkOrderStatusChanged;
use App\Jobs\ProcessChainTrigger;
use App\Models\AgentTrigger;
use App\Models\Deliverable;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkOrder;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Listens for entity status change events and triggers matching agent chains.
 *
 * Evaluates AgentTrigger records to find chains that should execute based on:
 * - Entity type (work_order, task, deliverable)
 * - Status transition (from and to status)
 * - Additional trigger conditions (budget thresholds, tags, field values)
 *
 * Dispatches ProcessChainTrigger jobs for each matching trigger, ordered by priority.
 */
class AgentTriggerListener
{
    /**
     * Handle the WorkOrderStatusChanged event.
     */
    public function handleWorkOrderStatusChanged(WorkOrderStatusChanged $event): void
    {
        $this->processStatusChange(
            TriggerEntityType::WorkOrder,
            $event->workOrder,
            $event->fromStatus->value,
            $event->toStatus->value,
            $event->user
        );
    }

    /**
     * Handle the DeliverableStatusChanged event.
     */
    public function handleDeliverableStatusChanged(DeliverableStatusChanged $event): void
    {
        $this->processStatusChange(
            TriggerEntityType::Deliverable,
            $event->deliverable,
            $event->fromStatus->value,
            $event->toStatus->value,
            $event->user
        );
    }

    /**
     * Handle the TaskStatusChanged event.
     */
    public function handleTaskStatusChanged(TaskStatusChanged $event): void
    {
        $this->processStatusChange(
            TriggerEntityType::Task,
            $event->task,
            $event->fromStatus->value,
            $event->toStatus->value,
            $event->user
        );
    }

    /**
     * Process a status change event for any entity type.
     */
    private function processStatusChange(
        TriggerEntityType $entityType,
        Model $entity,
        string $fromStatus,
        string $toStatus,
        ?User $user,
    ): void {
        $teamId = $this->getTeamId($entity);

        if ($teamId === null) {
            Log::warning('AgentTriggerListener: No team found for entity', [
                'entity_type' => $entityType->value,
                'entity_id' => $entity->getKey(),
            ]);

            return;
        }

        $matchingTriggers = $this->findMatchingTriggers(
            $teamId,
            $entityType,
            $fromStatus,
            $toStatus,
            $entity
        );

        if ($matchingTriggers->isEmpty()) {
            Log::debug('AgentTriggerListener: No matching triggers found', [
                'entity_type' => $entityType->value,
                'entity_id' => $entity->getKey(),
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
            ]);

            return;
        }

        Log::info('AgentTriggerListener: Found matching triggers', [
            'entity_type' => $entityType->value,
            'entity_id' => $entity->getKey(),
            'trigger_count' => $matchingTriggers->count(),
        ]);

        foreach ($matchingTriggers as $trigger) {
            $this->dispatchTrigger($trigger, $entity, $user);
        }
    }

    /**
     * Find all triggers matching the given status transition.
     *
     * Queries enabled triggers for the team that match:
     * - Entity type
     * - Status from (or null for "any source status")
     * - Status to (or null for "any target status")
     * - Additional trigger conditions
     *
     * Returns triggers ordered by priority (highest first).
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, AgentTrigger>
     */
    private function findMatchingTriggers(
        int $teamId,
        TriggerEntityType $entityType,
        string $fromStatus,
        string $toStatus,
        Model $entity,
    ) {
        return AgentTrigger::query()
            ->forTeam($teamId)
            ->enabled()
            ->forEntityType($entityType)
            ->where(function ($query) use ($fromStatus) {
                $query->whereNull('status_from')
                    ->orWhere('status_from', $fromStatus);
            })
            ->where(function ($query) use ($toStatus) {
                $query->whereNull('status_to')
                    ->orWhere('status_to', $toStatus);
            })
            ->with('chain')
            ->orderByPriority()
            ->get()
            ->filter(function (AgentTrigger $trigger) use ($entity) {
                // Filter by additional trigger conditions
                if (! $trigger->evaluateConditions($entity)) {
                    return false;
                }

                // Filter by chain enabled status
                if ($trigger->chain === null || ! $trigger->chain->enabled) {
                    return false;
                }

                // Check deduplication window
                if (! $this->passesDeduplicationCheck($trigger, $entity)) {
                    return false;
                }

                return true;
            });
    }

    /**
     * Check if the trigger passes the deduplication check.
     *
     * Prevents duplicate chain executions for the same entity + chain
     * within the configured deduplication window.
     */
    private function passesDeduplicationCheck(AgentTrigger $trigger, Model $entity): bool
    {
        $deduplicationWindow = $trigger->trigger_conditions['deduplication_window_minutes'] ?? null;

        if ($deduplicationWindow === null || $trigger->last_triggered_at === null) {
            return true;
        }

        $windowStart = Carbon::now()->subMinutes((int) $deduplicationWindow);

        return $trigger->last_triggered_at->isBefore($windowStart);
    }

    /**
     * Dispatch the ProcessChainTrigger job for a matching trigger.
     */
    private function dispatchTrigger(AgentTrigger $trigger, Model $entity, ?User $user): void
    {
        Log::info('AgentTriggerListener: Dispatching chain trigger', [
            'trigger_id' => $trigger->id,
            'trigger_name' => $trigger->name,
            'chain_id' => $trigger->agent_chain_id,
            'entity_type' => $trigger->entity_type->value,
            'entity_id' => $entity->getKey(),
            'priority' => $trigger->priority,
        ]);

        // Update last_triggered_at for deduplication tracking
        $trigger->update(['last_triggered_at' => now()]);

        ProcessChainTrigger::dispatch($trigger, $entity, $user);
    }

    /**
     * Get the team ID from the entity.
     */
    private function getTeamId(Model $entity): ?int
    {
        if ($entity instanceof WorkOrder) {
            return $entity->team_id;
        }

        if ($entity instanceof Task) {
            return $entity->team_id;
        }

        if ($entity instanceof Deliverable) {
            return $entity->team_id;
        }

        // Fallback for other models with team_id attribute
        if (isset($entity->team_id)) {
            return $entity->team_id;
        }

        return null;
    }
}
