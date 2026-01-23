<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectUserRate;
use App\Models\TimeEntry;
use App\Models\User;
use App\Models\UserRate;
use Carbon\Carbon;

class CostCalculationService
{
    /**
     * Get the applicable rate for a user on a specific date.
     *
     * Rate lookup priority:
     * 1. Project-specific rate override (if project provided)
     * 2. Team default rate
     *
     * @param  User  $user  The user to get rates for
     * @param  Project|null  $project  Optional project for project-specific overrides
     * @param  Carbon  $date  The date to get the effective rate for
     * @return array{internal_rate: string|null, billing_rate: string|null}
     */
    public function getRateForUser(User $user, ?Project $project, Carbon $date): array
    {
        // First, check for project-specific rate override
        if ($project !== null) {
            $projectRate = ProjectUserRate::forProject($project->id)
                ->forUser($user->id)
                ->effectiveAt($date)
                ->first();

            if ($projectRate !== null) {
                return [
                    'internal_rate' => $projectRate->internal_rate,
                    'billing_rate' => $projectRate->billing_rate,
                ];
            }
        }

        // Fall back to team default rate
        $teamId = $project?->team_id ?? $user->current_team_id;

        if ($teamId === null) {
            return [
                'internal_rate' => null,
                'billing_rate' => null,
            ];
        }

        $teamRate = UserRate::forTeam($teamId)
            ->forUser($user->id)
            ->effectiveAt($date)
            ->first();

        if ($teamRate !== null) {
            return [
                'internal_rate' => $teamRate->internal_rate,
                'billing_rate' => $teamRate->billing_rate,
            ];
        }

        return [
            'internal_rate' => null,
            'billing_rate' => null,
        ];
    }

    /**
     * Calculate cost and revenue for a time entry.
     *
     * This method:
     * - Looks up the applicable rate for the user at the entry date
     * - Snapshots the rates onto the time entry
     * - Calculates cost: hours * cost_rate
     * - Calculates revenue: hours * billing_rate (or 0 if non-billable)
     */
    public function calculateCost(TimeEntry $entry): void
    {
        // Get the project from the task
        $project = $entry->task?->project;

        // Get the entry date, falling back to today
        $date = $entry->date ?? Carbon::today();

        // Look up the applicable rates
        $rates = $this->getRateForUser($entry->user, $project, $date);

        // Snapshot rates onto the time entry
        $entry->cost_rate = $rates['internal_rate'];
        $entry->billing_rate = $rates['billing_rate'];

        // Calculate cost (always calculated regardless of billable status)
        $hours = (float) ($entry->hours ?? 0);
        $costRate = (float) ($rates['internal_rate'] ?? 0);
        $billingRate = (float) ($rates['billing_rate'] ?? 0);

        $entry->calculated_cost = number_format($hours * $costRate, 2, '.', '');

        // Calculate revenue (0 if non-billable)
        if ($entry->is_billable) {
            $entry->calculated_revenue = number_format($hours * $billingRate, 2, '.', '');
        } else {
            $entry->calculated_revenue = '0.00';
        }
    }
}
