<?php

namespace App\Traits;

use App\Models\Team;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTeam
{
    /**
     * Boot the trait.
     */
    protected static function bootBelongsToTeam(): void
    {
        static::creating(function ($model) {
            if (!$model->team_id && auth()->check()) {
                $model->team_id = auth()->user()->current_team_id;
            }
        });
    }

    /**
     * Get the team that the model belongs to.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Scope a query to only include models for the current team.
     */
    public function scopeForCurrentTeam($query)
    {
        return $query->where('team_id', auth()->user()->current_team_id);
    }

    /**
     * Scope a query to only include models for a specific team.
     */
    public function scopeForTeam($query, $teamId)
    {
        return $query->where('team_id', $teamId);
    }
}
