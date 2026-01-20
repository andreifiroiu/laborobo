<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AgentConfiguration;
use Illuminate\Support\Facades\DB;

/**
 * Service for managing agent budget and capacity.
 *
 * Handles budget validation and cost tracking for agent runs,
 * enforcing both daily and monthly spending caps.
 */
class AgentBudgetService
{
    /**
     * Check if an agent can run based on budget constraints.
     *
     * Validates both daily and monthly budget caps against the estimated cost.
     */
    public function canRun(AgentConfiguration $config, float $estimatedCost): bool
    {
        $dailyRemaining = $this->getDailyRemaining($config);
        $monthlyRemaining = $this->getMonthlyRemaining($config);

        // Check both daily and monthly limits
        return $estimatedCost <= $dailyRemaining && $estimatedCost <= $monthlyRemaining;
    }

    /**
     * Deduct cost from the agent's budget after a run.
     *
     * Updates both daily_spend and current_month_spend atomically.
     */
    public function deductCost(AgentConfiguration $config, float $cost): void
    {
        DB::transaction(function () use ($config, $cost) {
            $config->increment('daily_spend', $cost);
            $config->increment('current_month_spend', $cost);
        });
    }

    /**
     * Get the remaining daily budget for an agent.
     */
    public function getDailyRemaining(AgentConfiguration $config): float
    {
        $cap = (float) $config->monthly_budget_cap;
        $spent = (float) $config->daily_spend;

        return max(0, $cap - $spent);
    }

    /**
     * Get the remaining monthly budget for an agent.
     */
    public function getMonthlyRemaining(AgentConfiguration $config): float
    {
        $cap = (float) $config->monthly_budget_cap;
        $spent = (float) $config->current_month_spend;

        return max(0, $cap - $spent);
    }

    /**
     * Check if the agent has any remaining daily budget.
     */
    public function hasDailyBudgetRemaining(AgentConfiguration $config): bool
    {
        return $this->getDailyRemaining($config) > 0;
    }

    /**
     * Check if the agent has any remaining monthly budget.
     */
    public function hasMonthlyBudgetRemaining(AgentConfiguration $config): bool
    {
        return $this->getMonthlyRemaining($config) > 0;
    }

    /**
     * Get the budget status for an agent.
     *
     * @return array{daily_cap: float, daily_spent: float, daily_remaining: float, monthly_cap: float, monthly_spent: float, monthly_remaining: float}
     */
    public function getBudgetStatus(AgentConfiguration $config): array
    {
        $cap = (float) $config->monthly_budget_cap;

        return [
            'daily_cap' => $cap,
            'daily_spent' => (float) $config->daily_spend,
            'daily_remaining' => $this->getDailyRemaining($config),
            'monthly_cap' => $cap,
            'monthly_spent' => (float) $config->current_month_spend,
            'monthly_remaining' => $this->getMonthlyRemaining($config),
        ];
    }

    /**
     * Get the percentage of daily budget used.
     */
    public function getDailyUsagePercentage(AgentConfiguration $config): float
    {
        $cap = (float) $config->monthly_budget_cap;

        if ($cap <= 0) {
            return 0;
        }

        return min(100, ((float) $config->daily_spend / $cap) * 100);
    }

    /**
     * Get the percentage of monthly budget used.
     */
    public function getMonthlyUsagePercentage(AgentConfiguration $config): float
    {
        $cap = (float) $config->monthly_budget_cap;

        if ($cap <= 0) {
            return 0;
        }

        return min(100, ((float) $config->current_month_spend / $cap) * 100);
    }
}
