<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AgentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
        'capabilities',
    ];

    protected $casts = [
        'capabilities' => 'array',
        'type' => AgentType::class,
    ];

    public function configurations(): HasMany
    {
        return $this->hasMany(AgentConfiguration::class, 'ai_agent_id');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(AgentActivityLog::class, 'ai_agent_id');
    }
}
