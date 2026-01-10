<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlobalAISettings extends Model
{
    protected $table = 'global_ai_settings';

    protected $fillable = [
        'team_id',
        'total_monthly_budget',
        'current_month_spend',
        'per_project_budget_cap',
        'approval_client_facing_content',
        'approval_financial_data',
        'approval_contractual_changes',
        'approval_work_order_creation',
        'approval_task_assignment',
    ];

    protected $casts = [
        'total_monthly_budget' => 'decimal:2',
        'current_month_spend' => 'decimal:2',
        'per_project_budget_cap' => 'decimal:2',
        'approval_client_facing_content' => 'boolean',
        'approval_financial_data' => 'boolean',
        'approval_contractual_changes' => 'boolean',
        'approval_work_order_creation' => 'boolean',
        'approval_task_assignment' => 'boolean',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public static function forTeam(Team $team)
    {
        return static::firstOrCreate(['team_id' => $team->id]);
    }
}
