<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'team_id',
        'timestamp',
        'actor',
        'actor_name',
        'actor_type',
        'action',
        'target',
        'target_id',
        'details',
        'ip_address',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public static function log(
        Team $team,
        string $actorType,
        string $actorId,
        string $actorName,
        string $action,
        string $details,
        ?string $target = null,
        ?string $targetId = null,
        ?string $ipAddress = null
    ) {
        return static::create([
            'team_id' => $team->id,
            'actor' => "$actorType:$actorId",
            'actor_name' => $actorName,
            'actor_type' => $actorType,
            'action' => $action,
            'target' => $target,
            'target_id' => $targetId,
            'details' => $details,
            'ip_address' => $ipAddress,
        ]);
    }
}
