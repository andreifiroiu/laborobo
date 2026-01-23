<?php

declare(strict_types=1);

use App\Enums\BudgetType;
use App\Models\Party;
use App\Models\Project;
use App\Models\ProjectUserRate;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use App\Models\UserRate;
use App\Models\WorkOrder;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->team = $this->user->createTeam(['name' => 'Test Team']);
    $this->user->current_team_id = $this->team->id;
    $this->user->save();

    // Set up user rate
    UserRate::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'internal_rate' => 50.00,
        'billing_rate' => 100.00,
        'effective_date' => now()->subMonth(),
    ]);

    $this->party = Party::factory()->create(['team_id' => $this->team->id]);
    $this->project = Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $this->party->id,
        'owner_id' => $this->user->id,
        'budget_type' => BudgetType::FixedPrice,
        'budget_cost' => 1000.00,
        'actual_cost' => 0,
        'actual_revenue' => 0,
    ]);

    $this->workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
        'budget_type' => BudgetType::FixedPrice,
        'budget_cost' => 500.00,
        'actual_cost' => 0,
        'actual_revenue' => 0,
    ]);

    $this->task = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
        'actual_cost' => 0,
        'actual_revenue' => 0,
    ]);
});

test('time entry creation triggers complete cost calculation and aggregation chain', function () {
    // Create a time entry - this should trigger:
    // 1. TimeEntryObserver calculates cost/revenue
    // 2. Task recalculateActualCost bubbles up
    // 3. WorkOrder recalculateActualCost bubbles up
    // 4. Project recalculateActualCost is updated

    $timeEntry = TimeEntry::factory()->manual()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'task_id' => $this->task->id,
        'hours' => 4.0,
        'date' => now(),
        'is_billable' => true,
    ]);

    $timeEntry->refresh();

    // Verify time entry cost calculation
    expect($timeEntry->cost_rate)->toBe('50.00');
    expect($timeEntry->billing_rate)->toBe('100.00');
    expect($timeEntry->calculated_cost)->toBe('200.00'); // 4 * 50
    expect($timeEntry->calculated_revenue)->toBe('400.00'); // 4 * 100

    // Trigger aggregation via task recalculation
    $this->task->recalculateActualCost();

    // Refresh all models to get updated values
    $this->task->refresh();
    $this->workOrder->refresh();
    $this->project->refresh();

    // Verify aggregation bubbled up correctly
    expect($this->task->actual_cost)->toBe('200.00');
    expect($this->task->actual_revenue)->toBe('400.00');
    expect($this->workOrder->actual_cost)->toBe('200.00');
    expect($this->workOrder->actual_revenue)->toBe('400.00');
    expect($this->project->actual_cost)->toBe('200.00');
    expect($this->project->actual_revenue)->toBe('400.00');
});

test('rate snapshot on time entry preserves historical rate regardless of current rates', function () {
    // Create a time entry with the original rate ($50/$100)
    $timeEntry1 = TimeEntry::factory()->manual()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'task_id' => $this->task->id,
        'hours' => 2.0,
        'date' => now()->subDays(5),
        'is_billable' => true,
    ]);

    $timeEntry1->refresh();

    // Verify original rates were snapshotted
    expect($timeEntry1->cost_rate)->toBe('50.00');
    expect($timeEntry1->billing_rate)->toBe('100.00');
    expect($timeEntry1->calculated_cost)->toBe('100.00'); // 2 * 50
    expect($timeEntry1->calculated_revenue)->toBe('200.00'); // 2 * 100

    // Create a new rate with effective date 2 days ago (clearly in the middle)
    UserRate::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'internal_rate' => 75.00,
        'billing_rate' => 150.00,
        'effective_date' => now()->subDays(2),
    ]);

    // Verify the old entry was NOT affected by the new rate
    // (the rates are snapshotted on the time entry, not looked up dynamically)
    $timeEntry1->refresh();
    expect($timeEntry1->cost_rate)->toBe('50.00');
    expect($timeEntry1->billing_rate)->toBe('100.00');
    expect($timeEntry1->calculated_cost)->toBe('100.00');
    expect($timeEntry1->calculated_revenue)->toBe('200.00');

    // Create a new time entry after the new rate's effective date
    // This should use the new rate ($75/$150) because the entry date
    // is today and the new rate is effective from 2 days ago
    $timeEntry2 = TimeEntry::factory()->manual()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'task_id' => $this->task->id,
        'hours' => 2.0,
        'date' => now(),
        'is_billable' => true,
    ]);

    $timeEntry2->refresh();

    // Verify new entry uses the new rate
    expect($timeEntry2->cost_rate)->toBe('75.00');
    expect($timeEntry2->billing_rate)->toBe('150.00');
    expect($timeEntry2->calculated_cost)->toBe('150.00'); // 2 * 75
    expect($timeEntry2->calculated_revenue)->toBe('300.00'); // 2 * 150
});

test('profitability report correctly aggregates mixed billable and non-billable entries', function () {
    // Create billable time entries
    TimeEntry::factory()->manual()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'task_id' => $this->task->id,
        'hours' => 5.0,
        'date' => now()->toDateString(),
        'is_billable' => true,
    ]);

    // Create non-billable time entries
    TimeEntry::factory()->manual()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'task_id' => $this->task->id,
        'hours' => 3.0,
        'date' => now()->toDateString(),
        'is_billable' => false,
    ]);

    // Recalculate costs
    $this->task->recalculateActualCost();
    $this->workOrder->refresh();
    $this->project->refresh();

    // Expected calculations:
    // Billable: 5h * $50 cost = $250 cost, 5h * $100 billing = $500 revenue
    // Non-billable: 3h * $50 cost = $150 cost, $0 revenue
    // Total: $400 cost, $500 revenue, $100 margin

    $response = $this->actingAs($this->user)->get('/reports/profitability/by-project');

    $response->assertStatus(200);
    $response->assertJson([
        'data' => [
            [
                'id' => $this->project->id,
                'actual_cost' => 400.00,
                'revenue' => 500.00,
                'margin' => 100.00,
            ],
        ],
    ]);
});

test('budget vs actuals comparison shows accurate budget variance', function () {
    // Create time entries that result in specific actual costs
    TimeEntry::factory()->manual()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'task_id' => $this->task->id,
        'hours' => 6.0,
        'date' => now()->toDateString(),
        'is_billable' => true,
    ]);

    // Recalculate costs
    $this->task->recalculateActualCost();
    $this->workOrder->refresh();
    $this->project->refresh();

    // Expected:
    // Budget: $1000 (project budget_cost)
    // Actual Cost: 6h * $50 = $300
    // Budget Variance: $1000 - $300 = $700 (under budget)

    $response = $this->actingAs($this->user)->get('/reports/profitability/by-project');

    $response->assertStatus(200);

    $responseData = $response->json('data.0');

    // Use toEqual for loose type comparison (1000 vs 1000.0)
    expect((float) $responseData['budget_cost'])->toEqual(1000.0);
    expect((float) $responseData['actual_cost'])->toEqual(300.0);

    // Calculate budget variance: budget_cost - actual_cost
    $budgetVariance = (float) $responseData['budget_cost'] - (float) $responseData['actual_cost'];
    expect($budgetVariance)->toEqual(700.0); // Under budget
});

test('project-specific rate override correctly applies to time entries', function () {
    // Create a project-specific rate override
    ProjectUserRate::factory()->create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'internal_rate' => 80.00,
        'billing_rate' => 160.00,
        'effective_date' => now()->subMonth(),
    ]);

    // Create a time entry - should use project override rates
    $timeEntry = TimeEntry::factory()->manual()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'task_id' => $this->task->id,
        'hours' => 3.0,
        'date' => now(),
        'is_billable' => true,
    ]);

    $timeEntry->refresh();

    // Verify project override rate was used instead of team default
    expect($timeEntry->cost_rate)->toBe('80.00'); // Project override
    expect($timeEntry->billing_rate)->toBe('160.00'); // Project override
    expect($timeEntry->calculated_cost)->toBe('240.00'); // 3 * 80
    expect($timeEntry->calculated_revenue)->toBe('480.00'); // 3 * 160
});

test('profitability by team member shows correct utilization calculation', function () {
    // Create billable entries (6 hours)
    TimeEntry::factory()->manual()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'task_id' => $this->task->id,
        'hours' => 6.0,
        'date' => now()->toDateString(),
        'is_billable' => true,
    ]);

    // Create non-billable entries (2 hours)
    TimeEntry::factory()->manual()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'task_id' => $this->task->id,
        'hours' => 2.0,
        'date' => now()->toDateString(),
        'is_billable' => false,
    ]);

    // Total: 8 hours, Billable: 6 hours
    // Utilization: 6 / 8 = 75%

    $response = $this->actingAs($this->user)->get('/reports/profitability/by-team-member');

    $response->assertStatus(200);
    $response->assertJson([
        'data' => [
            [
                'user_id' => $this->user->id,
                'total_hours' => 8.0,
                'billable_hours' => 6.0,
                'utilization' => 75.00,
            ],
        ],
    ]);
});

test('multiple time entries aggregate correctly across work orders', function () {
    // Create a second work order in the same project
    $workOrder2 = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
        'actual_cost' => 0,
        'actual_revenue' => 0,
    ]);

    $task2 = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $workOrder2->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
        'actual_cost' => 0,
        'actual_revenue' => 0,
    ]);

    // Create time entries in both tasks
    TimeEntry::factory()->manual()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'task_id' => $this->task->id,
        'hours' => 2.0,
        'date' => now(),
        'is_billable' => true,
    ]);

    TimeEntry::factory()->manual()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'task_id' => $task2->id,
        'hours' => 3.0,
        'date' => now(),
        'is_billable' => true,
    ]);

    // Recalculate costs
    $this->task->recalculateActualCost();
    $task2->recalculateActualCost();

    // Refresh project
    $this->project->refresh();

    // Expected:
    // Task 1: 2h * $50 = $100 cost, 2h * $100 = $200 revenue
    // Task 2: 3h * $50 = $150 cost, 3h * $100 = $300 revenue
    // Project total: $250 cost, $500 revenue

    expect($this->project->actual_cost)->toBe('250.00');
    expect($this->project->actual_revenue)->toBe('500.00');
});

test('zero rate handling does not break cost calculation', function () {
    // Create a user with zero rates
    $zeroRateUser = User::factory()->create();
    $this->team->addUser($zeroRateUser, 'member');
    $zeroRateUser->current_team_id = $this->team->id;
    $zeroRateUser->save();

    UserRate::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $zeroRateUser->id,
        'internal_rate' => 0.00,
        'billing_rate' => 0.00,
        'effective_date' => now()->subMonth(),
    ]);

    // Create a time entry for the zero-rate user
    $timeEntry = TimeEntry::factory()->manual()->create([
        'team_id' => $this->team->id,
        'user_id' => $zeroRateUser->id,
        'task_id' => $this->task->id,
        'hours' => 5.0,
        'date' => now(),
        'is_billable' => true,
    ]);

    $timeEntry->refresh();

    // Verify zero costs are calculated correctly
    expect($timeEntry->cost_rate)->toBe('0.00');
    expect($timeEntry->billing_rate)->toBe('0.00');
    expect($timeEntry->calculated_cost)->toBe('0.00');
    expect($timeEntry->calculated_revenue)->toBe('0.00');
});
