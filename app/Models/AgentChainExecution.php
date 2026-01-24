<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ChainExecutionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AgentChainExecution extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'agent_chain_id',
        'current_step_index',
        'execution_status',
        'chain_context',
        'paused_at',
        'resumed_at',
        'completed_at',
        'failed_at',
        'error_message',
        'started_at',
        'triggerable_type',
        'triggerable_id',
    ];

    protected $casts = [
        'chain_context' => 'array',
        'execution_status' => ChainExecutionStatus::class,
        'paused_at' => 'datetime',
        'resumed_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'started_at' => 'datetime',
        'current_step_index' => 'integer',
    ];

    /**
     * Get the team that owns this execution.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the chain being executed.
     */
    public function chain(): BelongsTo
    {
        return $this->belongsTo(AgentChain::class, 'agent_chain_id');
    }

    /**
     * Get the execution steps.
     */
    public function steps(): HasMany
    {
        return $this->hasMany(AgentChainExecutionStep::class, 'agent_chain_execution_id');
    }

    /**
     * Get the triggering entity (work order, task, or deliverable).
     */
    public function triggerable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Check if the execution is pending.
     */
    public function isPending(): bool
    {
        return $this->execution_status === ChainExecutionStatus::Pending;
    }

    /**
     * Check if the execution is running.
     */
    public function isRunning(): bool
    {
        return $this->execution_status === ChainExecutionStatus::Running;
    }

    /**
     * Check if the execution is paused.
     */
    public function isPaused(): bool
    {
        return $this->execution_status === ChainExecutionStatus::Paused;
    }

    /**
     * Check if the execution is completed.
     */
    public function isCompleted(): bool
    {
        return $this->execution_status === ChainExecutionStatus::Completed;
    }

    /**
     * Check if the execution has failed.
     */
    public function isFailed(): bool
    {
        return $this->execution_status === ChainExecutionStatus::Failed;
    }

    /**
     * Check if the execution is in a terminal state.
     */
    public function isTerminal(): bool
    {
        return $this->execution_status->isTerminal();
    }

    /**
     * Scope to filter by team.
     */
    public function scopeForTeam($query, int $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    /**
     * Scope to filter pending executions.
     */
    public function scopePending($query)
    {
        return $query->where('execution_status', ChainExecutionStatus::Pending);
    }

    /**
     * Scope to filter running executions.
     */
    public function scopeRunning($query)
    {
        return $query->where('execution_status', ChainExecutionStatus::Running);
    }

    /**
     * Scope to filter paused executions.
     */
    public function scopePaused($query)
    {
        return $query->where('execution_status', ChainExecutionStatus::Paused);
    }

    /**
     * Scope to filter completed executions.
     */
    public function scopeCompleted($query)
    {
        return $query->where('execution_status', ChainExecutionStatus::Completed);
    }

    /**
     * Scope to filter failed executions.
     */
    public function scopeFailed($query)
    {
        return $query->where('execution_status', ChainExecutionStatus::Failed);
    }

    /**
     * Scope to filter active (non-terminal) executions.
     */
    public function scopeActive($query)
    {
        return $query->whereIn('execution_status', [
            ChainExecutionStatus::Pending,
            ChainExecutionStatus::Running,
            ChainExecutionStatus::Paused,
        ]);
    }
}
