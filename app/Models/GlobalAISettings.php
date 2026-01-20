<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'retention_days',
        'require_approval_external_sends',
        'require_approval_financial',
        'require_approval_contracts',
        'require_approval_scope_changes',
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
        'retention_days' => 'integer',
        'require_approval_external_sends' => 'boolean',
        'require_approval_financial' => 'boolean',
        'require_approval_contracts' => 'boolean',
        'require_approval_scope_changes' => 'boolean',
    ];

    /**
     * Get the team that owns these settings.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get or create settings for a team.
     */
    public static function forTeam(Team $team): static
    {
        return static::firstOrCreate(['team_id' => $team->id]);
    }

    /**
     * Check if human approval is required for a specific action type.
     */
    public function requiresApprovalFor(string $actionType): bool
    {
        return match ($actionType) {
            'external_sends' => $this->require_approval_external_sends,
            'financial' => $this->require_approval_financial,
            'contracts' => $this->require_approval_contracts,
            'scope_changes' => $this->require_approval_scope_changes,
            'client_facing_content' => $this->approval_client_facing_content,
            'financial_data' => $this->approval_financial_data,
            'contractual_changes' => $this->approval_contractual_changes,
            'work_order_creation' => $this->approval_work_order_creation,
            'task_assignment' => $this->approval_task_assignment,
            default => true,
        };
    }

    /**
     * Check if there is budget remaining for the month.
     */
    public function hasBudgetRemaining(): bool
    {
        return (float) $this->current_month_spend < (float) $this->total_monthly_budget;
    }

    /**
     * Get the remaining monthly budget.
     */
    public function getRemainingBudgetAttribute(): float
    {
        return max(0, (float) $this->total_monthly_budget - (float) $this->current_month_spend);
    }
}
