<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AgentMemoryScope;
use App\Models\AgentChainExecution;
use App\Models\AgentMemory;
use App\Models\Party;
use App\Models\Project;
use App\Models\Team;
use Illuminate\Support\Collection;

/**
 * Service for managing agent memory storage and retrieval.
 *
 * Provides persistent storage for agent context across sessions,
 * supporting four memory scopes: project, client, org, and chain.
 */
class AgentMemoryService
{
    /**
     * Store a value in agent memory.
     *
     * @param  Team  $team  The team context for this memory
     * @param  string  $scope  The memory scope (project, client, org, chain)
     * @param  int  $scopeId  The ID of the scoped entity
     * @param  string  $key  The memory key
     * @param  mixed  $value  The value to store (will be JSON encoded)
     * @param  int|null  $ttlMinutes  Optional TTL in minutes; null for no expiration
     * @param  int|null  $agentId  Optional agent ID to scope memory to a specific agent
     */
    public function store(
        Team $team,
        string $scope,
        int $scopeId,
        string $key,
        mixed $value,
        ?int $ttlMinutes = null,
        ?int $agentId = null,
    ): void {
        $scopeEnum = AgentMemoryScope::from($scope);
        $scopeType = $this->getScopeType($scopeEnum);

        $expiresAt = $ttlMinutes !== null
            ? now()->addMinutes($ttlMinutes)
            : null;

        AgentMemory::updateOrCreate(
            [
                'team_id' => $team->id,
                'scope' => $scopeEnum,
                'scope_id' => $scopeId,
                'key' => $key,
            ],
            [
                'ai_agent_id' => $agentId,
                'scope_type' => $scopeType,
                'value' => $value,
                'expires_at' => $expiresAt,
            ]
        );
    }

    /**
     * Retrieve a value from agent memory.
     *
     * Returns null if the key doesn't exist or has expired.
     *
     * @param  Team  $team  The team context
     * @param  string  $scope  The memory scope (project, client, org, chain)
     * @param  int  $scopeId  The ID of the scoped entity
     * @param  string  $key  The memory key
     * @return mixed The stored value, or null if not found/expired
     */
    public function retrieve(
        Team $team,
        string $scope,
        int $scopeId,
        string $key,
    ): mixed {
        $scopeEnum = AgentMemoryScope::from($scope);

        $memory = AgentMemory::where('team_id', $team->id)
            ->where('scope', $scopeEnum)
            ->where('scope_id', $scopeId)
            ->where('key', $key)
            ->notExpired()
            ->first();

        return $memory?->value;
    }

    /**
     * Forget (delete) a value from agent memory.
     *
     * Uses soft delete for audit trail.
     *
     * @param  Team  $team  The team context
     * @param  string  $scope  The memory scope (project, client, org, chain)
     * @param  int  $scopeId  The ID of the scoped entity
     * @param  string  $key  The memory key
     */
    public function forget(
        Team $team,
        string $scope,
        int $scopeId,
        string $key,
    ): void {
        $scopeEnum = AgentMemoryScope::from($scope);

        AgentMemory::where('team_id', $team->id)
            ->where('scope', $scopeEnum)
            ->where('scope_id', $scopeId)
            ->where('key', $key)
            ->delete();
    }

    /**
     * Get all memory entries for a specific scope.
     *
     * Returns only non-expired entries.
     *
     * @param  Team  $team  The team context
     * @param  string  $scope  The memory scope (project, client, org, chain)
     * @param  int  $scopeId  The ID of the scoped entity
     * @return Collection<int, AgentMemory> Collection of memory entries
     */
    public function getForScope(
        Team $team,
        string $scope,
        int $scopeId,
    ): Collection {
        $scopeEnum = AgentMemoryScope::from($scope);

        return AgentMemory::where('team_id', $team->id)
            ->where('scope', $scopeEnum)
            ->where('scope_id', $scopeId)
            ->notExpired()
            ->get();
    }

    /**
     * Get all memory entries for a team within a scope level.
     *
     * Useful for retrieving all project memories or all client memories.
     *
     * @param  Team  $team  The team context
     * @param  string  $scope  The memory scope (project, client, org, chain)
     * @return Collection<int, AgentMemory> Collection of memory entries
     */
    public function getAllForScopeLevel(Team $team, string $scope): Collection
    {
        $scopeEnum = AgentMemoryScope::from($scope);

        return AgentMemory::where('team_id', $team->id)
            ->where('scope', $scopeEnum)
            ->notExpired()
            ->get();
    }

    /**
     * Clear all expired memory entries for a team.
     *
     * This permanently deletes expired entries (bypassing soft delete).
     *
     * @param  Team  $team  The team context
     * @return int Number of entries deleted
     */
    public function clearExpired(Team $team): int
    {
        return AgentMemory::where('team_id', $team->id)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->forceDelete();
    }

    /**
     * Check if a memory key exists and is not expired.
     *
     * @param  Team  $team  The team context
     * @param  string  $scope  The memory scope
     * @param  int  $scopeId  The ID of the scoped entity
     * @param  string  $key  The memory key
     */
    public function has(
        Team $team,
        string $scope,
        int $scopeId,
        string $key,
    ): bool {
        $scopeEnum = AgentMemoryScope::from($scope);

        return AgentMemory::where('team_id', $team->id)
            ->where('scope', $scopeEnum)
            ->where('scope_id', $scopeId)
            ->where('key', $key)
            ->notExpired()
            ->exists();
    }

    /**
     * Store multiple key-value pairs at once.
     *
     * @param  Team  $team  The team context
     * @param  string  $scope  The memory scope
     * @param  int  $scopeId  The ID of the scoped entity
     * @param  array<string, mixed>  $data  Array of key-value pairs to store
     * @param  int|null  $ttlMinutes  Optional TTL for all entries
     * @param  int|null  $agentId  Optional agent ID
     */
    public function storeMany(
        Team $team,
        string $scope,
        int $scopeId,
        array $data,
        ?int $ttlMinutes = null,
        ?int $agentId = null,
    ): void {
        foreach ($data as $key => $value) {
            $this->store($team, $scope, $scopeId, $key, $value, $ttlMinutes, $agentId);
        }
    }

    /**
     * Store a value in chain-scoped memory.
     *
     * Chain memory is scoped to a specific chain execution and persists
     * for the duration of the chain execution. It allows agents within
     * a chain to share context and state.
     *
     * @param  Team  $team  The team context
     * @param  int  $chainExecutionId  The chain execution ID to scope memory to
     * @param  string  $key  The memory key
     * @param  mixed  $value  The value to store
     * @param  int|null  $ttlMinutes  Optional TTL in minutes
     * @param  int|null  $agentId  Optional agent ID
     */
    public function storeChainMemory(
        Team $team,
        int $chainExecutionId,
        string $key,
        mixed $value,
        ?int $ttlMinutes = null,
        ?int $agentId = null,
    ): void {
        $this->store(
            $team,
            AgentMemoryScope::Chain->value,
            $chainExecutionId,
            $key,
            $value,
            $ttlMinutes,
            $agentId
        );
    }

    /**
     * Retrieve a value from chain-scoped memory.
     *
     * @param  Team  $team  The team context
     * @param  int  $chainExecutionId  The chain execution ID
     * @param  string  $key  The memory key
     * @return mixed The stored value, or null if not found
     */
    public function getChainMemory(
        Team $team,
        int $chainExecutionId,
        string $key,
    ): mixed {
        return $this->retrieve(
            $team,
            AgentMemoryScope::Chain->value,
            $chainExecutionId,
            $key
        );
    }

    /**
     * Get all memory entries for a chain execution.
     *
     * @param  Team  $team  The team context
     * @param  int  $chainExecutionId  The chain execution ID
     * @return Collection<int, AgentMemory> Collection of memory entries
     */
    public function getAllChainMemories(Team $team, int $chainExecutionId): Collection
    {
        return $this->getForScope($team, AgentMemoryScope::Chain->value, $chainExecutionId);
    }

    /**
     * Clear all memory entries for a chain execution.
     *
     * This should be called when a chain execution completes or fails
     * to clean up temporary chain-scoped memory.
     *
     * @param  Team  $team  The team context
     * @param  int  $chainExecutionId  The chain execution ID
     * @return int Number of entries deleted
     */
    public function clearChainMemory(Team $team, int $chainExecutionId): int
    {
        return AgentMemory::where('team_id', $team->id)
            ->where('scope', AgentMemoryScope::Chain)
            ->where('scope_id', $chainExecutionId)
            ->delete();
    }

    /**
     * Check if a chain memory key exists.
     *
     * @param  Team  $team  The team context
     * @param  int  $chainExecutionId  The chain execution ID
     * @param  string  $key  The memory key
     */
    public function hasChainMemory(
        Team $team,
        int $chainExecutionId,
        string $key,
    ): bool {
        return $this->has($team, AgentMemoryScope::Chain->value, $chainExecutionId, $key);
    }

    /**
     * Store multiple chain memory entries at once.
     *
     * @param  Team  $team  The team context
     * @param  int  $chainExecutionId  The chain execution ID
     * @param  array<string, mixed>  $data  Array of key-value pairs to store
     * @param  int|null  $ttlMinutes  Optional TTL for all entries
     * @param  int|null  $agentId  Optional agent ID
     */
    public function storeChainMemoryMany(
        Team $team,
        int $chainExecutionId,
        array $data,
        ?int $ttlMinutes = null,
        ?int $agentId = null,
    ): void {
        $this->storeMany(
            $team,
            AgentMemoryScope::Chain->value,
            $chainExecutionId,
            $data,
            $ttlMinutes,
            $agentId
        );
    }

    /**
     * Get the model class for a given scope.
     *
     * @return class-string
     */
    private function getScopeType(AgentMemoryScope $scope): string
    {
        return match ($scope) {
            AgentMemoryScope::Project => Project::class,
            AgentMemoryScope::Client => Party::class,
            AgentMemoryScope::Org => Team::class,
            AgentMemoryScope::Chain => AgentChainExecution::class,
        };
    }
}
