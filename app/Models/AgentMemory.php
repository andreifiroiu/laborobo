<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AgentMemoryScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AgentMemory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'team_id',
        'ai_agent_id',
        'scope',
        'scope_type',
        'scope_id',
        'key',
        'value',
        'expires_at',
    ];

    protected $casts = [
        'scope' => AgentMemoryScope::class,
        'value' => 'array',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the team that owns this memory.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the agent this memory belongs to.
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(AIAgent::class, 'ai_agent_id');
    }

    /**
     * Get the scoped model (Project, Party, or Team).
     */
    public function scopeable(): MorphTo
    {
        return $this->morphTo('scope', 'scope_type', 'scope_id');
    }

    /**
     * Scope to filter by memory scope level.
     */
    public function scopeOfScope($query, AgentMemoryScope $scope)
    {
        return $query->where('scope', $scope);
    }

    /**
     * Scope to filter by team.
     */
    public function scopeForTeam($query, int $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    /**
     * Scope to filter non-expired memories.
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Check if this memory has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
