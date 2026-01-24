<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgentChain extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'name',
        'description',
        'chain_definition',
        'is_template',
        'enabled',
        'agent_chain_template_id',
    ];

    protected $casts = [
        'chain_definition' => 'array',
        'is_template' => 'boolean',
        'enabled' => 'boolean',
    ];

    /**
     * Get the team that owns this chain.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the template this chain was created from.
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(AgentChainTemplate::class, 'agent_chain_template_id');
    }

    /**
     * Get the executions of this chain.
     */
    public function executions(): HasMany
    {
        return $this->hasMany(AgentChainExecution::class, 'agent_chain_id');
    }

    /**
     * Get the triggers that activate this chain.
     */
    public function triggers(): HasMany
    {
        return $this->hasMany(AgentTrigger::class, 'agent_chain_id');
    }

    /**
     * Scope to filter by team.
     */
    public function scopeForTeam($query, int $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    /**
     * Scope to filter enabled chains.
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    /**
     * Scope to filter template chains.
     */
    public function scopeTemplates($query)
    {
        return $query->where('is_template', true);
    }

    /**
     * Get the steps from the chain definition.
     */
    public function getSteps(): array
    {
        return $this->chain_definition['steps'] ?? [];
    }

    /**
     * Get the step count.
     */
    public function getStepCount(): int
    {
        return count($this->getSteps());
    }
}
