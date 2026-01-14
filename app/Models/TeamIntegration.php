<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamIntegration extends Model
{
    protected $fillable = [
        'team_id',
        'available_integration_id',
        'connected',
        'connected_at',
        'connected_by',
        'config',
        'last_sync_at',
        'sync_status',
        'error_message',
    ];

    protected $casts = [
        'connected' => 'boolean',
        'connected_at' => 'datetime',
        'last_sync_at' => 'datetime',
        'config' => 'array',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function availableIntegration()
    {
        return $this->belongsTo(AvailableIntegration::class);
    }

    public static function forTeam(Team $team, AvailableIntegration $integration)
    {
        return static::firstOrCreate([
            'team_id' => $team->id,
            'available_integration_id' => $integration->id,
        ]);
    }
}
