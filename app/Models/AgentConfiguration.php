<?php

namespace App\Models;

use App\Enums\CreativityLevel;
use App\Enums\RiskTolerance;
use App\Enums\VerbosityLevel;
use Illuminate\Database\Eloquent\Model;

class AgentConfiguration extends Model
{
    protected $fillable = [
        'team_id',
        'ai_agent_id',
        'enabled',
        'daily_run_limit',
        'weekly_run_limit',
        'monthly_budget_cap',
        'current_month_spend',
        'can_create_work_orders',
        'can_modify_tasks',
        'can_access_client_data',
        'can_send_emails',
        'requires_approval',
        'verbosity_level',
        'creativity_level',
        'risk_tolerance',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'monthly_budget_cap' => 'decimal:2',
        'current_month_spend' => 'decimal:2',
        'can_create_work_orders' => 'boolean',
        'can_modify_tasks' => 'boolean',
        'can_access_client_data' => 'boolean',
        'can_send_emails' => 'boolean',
        'requires_approval' => 'boolean',
        'verbosity_level' => VerbosityLevel::class,
        'creativity_level' => CreativityLevel::class,
        'risk_tolerance' => RiskTolerance::class,
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function agent()
    {
        return $this->belongsTo(AIAgent::class, 'ai_agent_id');
    }
}
