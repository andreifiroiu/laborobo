<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamApiKey extends Model
{
    protected $fillable = [
        'team_id',
        'user_id',
        'provider',
        'api_key_encrypted',
        'key_last_four',
        'label',
        'last_used_at',
    ];

    protected $hidden = [
        'api_key_encrypted',
    ];

    protected function casts(): array
    {
        return [
            'api_key_encrypted' => 'encrypted',
            'last_used_at' => 'datetime',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Whether this is a team-level (shared) key.
     */
    public function isTeamLevel(): bool
    {
        return $this->user_id === null;
    }

    /**
     * Get all keys visible to a given user within a team.
     * Returns team-shared keys + the user's own private keys.
     *
     * @return Collection<int, TeamApiKey>
     */
    public static function forTeamAndUser(int $teamId, int $userId): Collection
    {
        return static::where('team_id', $teamId)
            ->where(function ($q) use ($userId) {
                $q->whereNull('user_id')
                    ->orWhere('user_id', $userId);
            })
            ->orderBy('provider')
            ->orderByRaw('user_id IS NOT NULL')
            ->get();
    }

    /**
     * Resolve the best API key for a provider using 3-tier fallback:
     * 1. User's private key
     * 2. Team shared key
     * 3. Environment variable via config
     */
    public static function resolveKey(string $provider, int $teamId, ?int $userId): ?string
    {
        // Tier 1: User's private key
        if ($userId !== null) {
            $userKey = static::where('team_id', $teamId)
                ->where('user_id', $userId)
                ->where('provider', $provider)
                ->first();

            if ($userKey !== null) {
                $userKey->touchLastUsed();

                return $userKey->api_key_encrypted;
            }
        }

        // Tier 2: Team shared key
        $teamKey = static::where('team_id', $teamId)
            ->whereNull('user_id')
            ->where('provider', $provider)
            ->first();

        if ($teamKey !== null) {
            $teamKey->touchLastUsed();

            return $teamKey->api_key_encrypted;
        }

        // Tier 3: Environment fallback via config
        $configPath = config("ai-providers.providers.{$provider}.config_path");

        if ($configPath !== null) {
            return config($configPath);
        }

        return null;
    }

    /**
     * Update the last_used_at timestamp without changing updated_at.
     */
    public function touchLastUsed(): void
    {
        $this->timestamps = false;
        $this->update(['last_used_at' => now()]);
        $this->timestamps = true;
    }
}
