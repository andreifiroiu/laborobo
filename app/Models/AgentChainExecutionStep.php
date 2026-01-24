<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentChainExecutionStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_chain_execution_id',
        'agent_workflow_state_id',
        'step_index',
        'status',
        'started_at',
        'completed_at',
        'output_data',
    ];

    protected $casts = [
        'output_data' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'step_index' => 'integer',
    ];

    /**
     * Get the chain execution this step belongs to.
     */
    public function chainExecution(): BelongsTo
    {
        return $this->belongsTo(AgentChainExecution::class, 'agent_chain_execution_id');
    }

    /**
     * Get the workflow state for this step.
     */
    public function workflowState(): BelongsTo
    {
        return $this->belongsTo(AgentWorkflowState::class, 'agent_workflow_state_id');
    }

    /**
     * Check if the step is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the step is running.
     */
    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    /**
     * Check if the step is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if the step has failed.
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Get the duration in milliseconds.
     */
    public function getDurationMs(): ?int
    {
        if ($this->started_at === null || $this->completed_at === null) {
            return null;
        }

        return (int) $this->started_at->diffInMilliseconds($this->completed_at);
    }

    /**
     * Scope to filter by chain execution.
     */
    public function scopeForExecution($query, int $executionId)
    {
        return $query->where('agent_chain_execution_id', $executionId);
    }

    /**
     * Scope to filter completed steps.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}
