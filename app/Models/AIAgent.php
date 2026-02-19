<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AgentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AIAgent extends Model
{
    use HasFactory;

    protected $table = 'ai_agents';

    protected $fillable = [
        'code',
        'name',
        'type',
        'description',
        'instructions',
        'tools',
        'template_id',
        'is_custom',
    ];

    protected $casts = [
        'tools' => 'array',
        'type' => AgentType::class,
        'is_custom' => 'boolean',
    ];

    /**
     * Get the template this agent was created from.
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(AgentTemplate::class, 'template_id');
    }

    /**
     * Get the configurations for this agent.
     */
    public function configurations(): HasMany
    {
        return $this->hasMany(AgentConfiguration::class, 'ai_agent_id');
    }

    /**
     * Get the activity logs for this agent.
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(AgentActivityLog::class, 'ai_agent_id');
    }

    /**
     * Get the workflow states for this agent.
     */
    public function workflowStates(): HasMany
    {
        return $this->hasMany(AgentWorkflowState::class, 'ai_agent_id');
    }

    /**
     * Get the memories associated with this agent.
     */
    public function memories(): HasMany
    {
        return $this->hasMany(AgentMemory::class, 'ai_agent_id');
    }
}
