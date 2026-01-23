<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\TimeEntry;
use App\Services\CostCalculationService;

class TimeEntryObserver
{
    public function __construct(
        private readonly CostCalculationService $costCalculationService
    ) {}

    /**
     * Handle the TimeEntry "creating" event.
     *
     * Snapshots rates and calculates cost/revenue before the entry is saved.
     */
    public function creating(TimeEntry $timeEntry): void
    {
        $this->costCalculationService->calculateCost($timeEntry);
    }

    /**
     * Handle the TimeEntry "updating" event.
     *
     * Recalculates cost/revenue if hours have changed.
     * Uses the already-snapshotted rates rather than looking up new rates.
     */
    public function updating(TimeEntry $timeEntry): void
    {
        // Only recalculate if hours or billable status changed
        if ($timeEntry->isDirty(['hours', 'is_billable'])) {
            $this->recalculateCostWithExistingRates($timeEntry);
        }
    }

    /**
     * Recalculate cost and revenue using the already-snapshotted rates.
     *
     * This preserves the historical rate snapshot while updating
     * the calculated values when hours change.
     */
    private function recalculateCostWithExistingRates(TimeEntry $timeEntry): void
    {
        $hours = (float) ($timeEntry->hours ?? 0);
        $costRate = (float) ($timeEntry->cost_rate ?? 0);
        $billingRate = (float) ($timeEntry->billing_rate ?? 0);

        $timeEntry->calculated_cost = number_format($hours * $costRate, 2, '.', '');

        if ($timeEntry->is_billable) {
            $timeEntry->calculated_revenue = number_format($hours * $billingRate, 2, '.', '');
        } else {
            $timeEntry->calculated_revenue = '0.00';
        }
    }
}
