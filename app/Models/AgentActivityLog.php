<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ApprovalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentActivityLog extends Model
{
    protected $fillable = [
        'team_id',
        'ai_agent_id',
        'run_type',
        'input',
        'output',
        'tokens_used',
        'cost',
        'approval_status',
        'approved_by',
        'approved_at',
        'error',
        'tool_calls',
        'context_accessed',
        'workflow_state_id',
        'duration_ms',
    ];

    protected $casts = [
        'cost' => 'decimal:4',
        'approved_at' => 'datetime',
        'approval_status' => ApprovalStatus::class,
        'tool_calls' => 'array',
        'context_accessed' => 'array',
        'duration_ms' => 'integer',
    ];

    /**
     * Get the team that owns this activity log.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the agent that generated this activity.
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(AIAgent::class, 'ai_agent_id');
    }

    /**
     * Get the user who approved this activity.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the workflow state this activity is related to.
     */
    public function workflowState(): BelongsTo
    {
        return $this->belongsTo(AgentWorkflowState::class, 'workflow_state_id');
    }

    /**
     * Scope to filter pending activities.
     */
    public function scopePending($query)
    {
        return $query->where('approval_status', ApprovalStatus::Pending);
    }

    /**
     * Scope to filter by team.
     */
    public function scopeForTeam($query, int $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeDateRange($query, $start, $end)
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }

    /**
     * Get the count of tool calls for this activity.
     */
    public function getToolCallCountAttribute(): int
    {
        return is_array($this->tool_calls) ? count($this->tool_calls) : 0;
    }
}
