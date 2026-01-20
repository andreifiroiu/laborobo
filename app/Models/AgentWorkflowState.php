<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AgentWorkflowState extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'ai_agent_id',
        'workflow_class',
        'current_node',
        'state_data',
        'paused_at',
        'resumed_at',
        'completed_at',
        'pause_reason',
        'approval_required',
        'approvable_type',
        'approvable_id',
    ];

    protected $casts = [
        'state_data' => 'array',
        'paused_at' => 'datetime',
        'resumed_at' => 'datetime',
        'completed_at' => 'datetime',
        'approval_required' => 'boolean',
    ];

    /**
     * Get the team that owns this workflow state.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the agent running this workflow.
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(AIAgent::class, 'ai_agent_id');
    }

    /**
     * Get the approvable model (typically InboxItem) for approval workflow.
     */
    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the activity logs related to this workflow state.
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(AgentActivityLog::class, 'workflow_state_id');
    }

    /**
     * Check if the workflow is currently paused.
     */
    public function isPaused(): bool
    {
        return $this->paused_at !== null && $this->resumed_at === null && $this->completed_at === null;
    }

    /**
     * Check if the workflow is completed.
     */
    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    /**
     * Check if the workflow is running (not paused, not completed).
     */
    public function isRunning(): bool
    {
        return !$this->isPaused() && !$this->isCompleted();
    }

    /**
     * Scope to filter paused workflows.
     */
    public function scopePaused($query)
    {
        return $query->whereNotNull('paused_at')
            ->whereNull('resumed_at')
            ->whereNull('completed_at');
    }

    /**
     * Scope to filter completed workflows.
     */
    public function scopeCompleted($query)
    {
        return $query->whereNotNull('completed_at');
    }

    /**
     * Scope to filter running workflows.
     */
    public function scopeRunning($query)
    {
        return $query->whereNull('completed_at')
            ->where(function ($q) {
                $q->whereNull('paused_at')
                    ->orWhereNotNull('resumed_at');
            });
    }

    /**
     * Scope to filter workflows requiring approval.
     */
    public function scopeRequiringApproval($query)
    {
        return $query->where('approval_required', true)
            ->paused();
    }

    /**
     * Scope to filter by team.
     */
    public function scopeForTeam($query, int $teamId)
    {
        return $query->where('team_id', $teamId);
    }
}
