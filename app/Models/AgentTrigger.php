<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TriggerEntityType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentTrigger extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'name',
        'entity_type',
        'status_from',
        'status_to',
        'agent_chain_id',
        'trigger_conditions',
        'enabled',
        'priority',
        'last_triggered_at',
    ];

    protected $casts = [
        'entity_type' => TriggerEntityType::class,
        'trigger_conditions' => 'array',
        'enabled' => 'boolean',
        'priority' => 'integer',
        'last_triggered_at' => 'datetime',
    ];

    /**
     * Get the team that owns this trigger.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the chain this trigger activates.
     */
    public function chain(): BelongsTo
    {
        return $this->belongsTo(AgentChain::class, 'agent_chain_id');
    }

    /**
     * Scope to filter by team.
     */
    public function scopeForTeam($query, int $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    /**
     * Scope to filter enabled triggers.
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    /**
     * Scope to filter by entity type.
     */
    public function scopeForEntityType($query, TriggerEntityType $entityType)
    {
        return $query->where('entity_type', $entityType);
    }

    /**
     * Scope to filter by status transition.
     */
    public function scopeForStatusTransition($query, ?string $from, ?string $to)
    {
        return $query
            ->where(function ($q) use ($from) {
                if ($from === null) {
                    $q->whereNull('status_from');
                } else {
                    $q->where('status_from', $from);
                }
            })
            ->where(function ($q) use ($to) {
                if ($to === null) {
                    $q->whereNull('status_to');
                } else {
                    $q->where('status_to', $to);
                }
            });
    }

    /**
     * Scope to order by priority (highest first).
     */
    public function scopeOrderByPriority($query)
    {
        return $query->orderByDesc('priority');
    }

    /**
     * Check if the trigger matches a status transition.
     */
    public function matchesStatusTransition(?string $from, ?string $to): bool
    {
        $fromMatches = $this->status_from === null || $this->status_from === $from;
        $toMatches = $this->status_to === null || $this->status_to === $to;

        return $fromMatches && $toMatches;
    }

    /**
     * Evaluate trigger conditions against an entity.
     */
    public function evaluateConditions(Model $entity): bool
    {
        if (empty($this->trigger_conditions)) {
            return true;
        }

        foreach ($this->trigger_conditions as $condition => $value) {
            // Skip internal configuration options that are not conditions
            if ($condition === 'deduplication_window_minutes') {
                continue;
            }

            if (! $this->evaluateSingleCondition($entity, $condition, $value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Evaluate a single condition against an entity.
     */
    protected function evaluateSingleCondition(Model $entity, string $condition, mixed $value): bool
    {
        return match ($condition) {
            'budget_greater_than' => $this->getEntityBudget($entity) > $value,
            'budget_less_than' => $this->getEntityBudget($entity) < $value,
            'has_tags' => $this->entityHasTags($entity, (array) $value),
            'entity_field_equals' => $this->entityFieldEquals($entity, $value),
            default => true,
        };
    }

    /**
     * Get the budget value from an entity.
     *
     * Handles different entity types that may have different budget field names.
     */
    protected function getEntityBudget(Model $entity): float
    {
        // WorkOrder uses budget_cost
        if ($entity instanceof WorkOrder) {
            return (float) ($entity->budget_cost ?? 0);
        }

        // Generic fallback for entities with a budget field
        return (float) ($entity->budget ?? $entity->budget_cost ?? 0);
    }

    /**
     * Check if entity has all specified tags.
     */
    protected function entityHasTags(Model $entity, array $tags): bool
    {
        if (! method_exists($entity, 'tags')) {
            return false;
        }

        $entityTags = $entity->tags->pluck('name')->toArray();

        return empty(array_diff($tags, $entityTags));
    }

    /**
     * Check if entity field equals specified value.
     */
    protected function entityFieldEquals(Model $entity, array $fieldValue): bool
    {
        foreach ($fieldValue as $field => $expectedValue) {
            if (($entity->{$field} ?? null) !== $expectedValue) {
                return false;
            }
        }

        return true;
    }
}
