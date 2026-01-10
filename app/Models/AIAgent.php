<?php

namespace App\Models;

use App\Enums\AgentType;
use Illuminate\Database\Eloquent\Model;

class AIAgent extends Model
{
    protected $table = 'ai_agents';

    protected $fillable = [
        'code',
        'name',
        'type',
        'description',
        'capabilities',
    ];

    protected $casts = [
        'capabilities' => 'array',
        'type' => AgentType::class,
    ];

    public function configurations()
    {
        return $this->hasMany(AgentConfiguration::class, 'ai_agent_id');
    }

    public function activityLogs()
    {
        return $this->hasMany(AgentActivityLog::class, 'ai_agent_id');
    }
}
