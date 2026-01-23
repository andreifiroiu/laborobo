<?php

declare(strict_types=1);

use App\Models\Project;
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

    // Set up user rate for automatic cost calculation
    UserRate::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'internal_rate' => 50.00,
        'billing_rate' => 100.00,
        'effective_date' => now()->subMonth(),
    ]);

    $this->project = Project::factory()->create([
        'team_id' => $this->team->id,
        'owner_id' => $this->user->id,
        'actual_cost' => 0,
        'actual_revenue' => 0,
    ]);

    $this->workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
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

test('task recalculateActualCost sums calculated_cost and calculated_revenue from time entries', function () {
    // Create time entries with costs
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
        'task_id' => $this->task->id,
        'hours' => 3.0,
        'date' => now(),
        'is_billable' => true,
    ]);

    // Recalculate task cost
    $this->task->recalculateActualCost();
    $this->task->refresh();

    // 2.0 * 50 + 3.0 * 50 = 100 + 150 = 250 cost
    // 2.0 * 100 + 3.0 * 100 = 200 + 300 = 500 revenue
    expect($this->task->actual_cost)->toBe('250.00');
    expect($this->task->actual_revenue)->toBe('500.00');
});

test('work order recalculateActualCost sums from tasks and bubbles up to project', function () {
    // Create a second task in the same work order
    $task2 = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
        'actual_cost' => 0,
        'actual_revenue' => 0,
    ]);

    // Create time entries for both tasks
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

    // Recalculate task costs first
    $this->task->recalculateActualCost();
    $task2->recalculateActualCost();

    // Refresh models
    $this->workOrder->refresh();
    $this->project->refresh();

    // Work order should have summed costs from both tasks
    // Task 1: 2.0 * 50 = 100 cost, 2.0 * 100 = 200 revenue
    // Task 2: 3.0 * 50 = 150 cost, 3.0 * 100 = 300 revenue
    // Total: 250 cost, 500 revenue
    expect($this->workOrder->actual_cost)->toBe('250.00');
    expect($this->workOrder->actual_revenue)->toBe('500.00');
});

test('project recalculateActualCost sums from work orders', function () {
    // Create a second work order with its own task
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

    // Create time entries
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
        'hours' => 4.0,
        'date' => now(),
        'is_billable' => true,
    ]);

    // Recalculate costs (this triggers bubble up)
    $this->task->recalculateActualCost();
    $task2->recalculateActualCost();

    // Refresh project
    $this->project->refresh();

    // Work order 1: 2.0 * 50 = 100 cost, 2.0 * 100 = 200 revenue
    // Work order 2: 4.0 * 50 = 200 cost, 4.0 * 100 = 400 revenue
    // Total: 300 cost, 600 revenue
    expect($this->project->actual_cost)->toBe('300.00');
    expect($this->project->actual_revenue)->toBe('600.00');
});

test('cost aggregation is triggered alongside hour aggregation via recalculateActualHours', function () {
    // Create time entries
    TimeEntry::factory()->manual()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'task_id' => $this->task->id,
        'hours' => 5.0,
        'date' => now(),
        'is_billable' => true,
    ]);

    // Call recalculateActualHours - this should also trigger cost recalculation
    $this->task->recalculateActualHours();

    // Refresh all models
    $this->task->refresh();
    $this->workOrder->refresh();
    $this->project->refresh();

    // Verify hours are updated
    expect($this->task->actual_hours)->toBe('5.00');

    // Verify costs are also updated
    // 5.0 * 50 = 250 cost, 5.0 * 100 = 500 revenue
    expect($this->task->actual_cost)->toBe('250.00');
    expect($this->task->actual_revenue)->toBe('500.00');

    // Check work order and project are also updated
    expect($this->workOrder->actual_cost)->toBe('250.00');
    expect($this->workOrder->actual_revenue)->toBe('500.00');
    expect($this->project->actual_cost)->toBe('250.00');
    expect($this->project->actual_revenue)->toBe('500.00');
});

test('non-billable time entries contribute to cost but not revenue in aggregation', function () {
    // Create billable time entry
    TimeEntry::factory()->manual()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'task_id' => $this->task->id,
        'hours' => 2.0,
        'date' => now(),
        'is_billable' => true,
    ]);

    // Create non-billable time entry
    TimeEntry::factory()->manual()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'task_id' => $this->task->id,
        'hours' => 3.0,
        'date' => now(),
        'is_billable' => false,
    ]);

    // Recalculate task cost
    $this->task->recalculateActualCost();
    $this->task->refresh();

    // Cost: (2.0 * 50) + (3.0 * 50) = 100 + 150 = 250
    // Revenue: (2.0 * 100) + 0 = 200 (non-billable entry has 0 revenue)
    expect($this->task->actual_cost)->toBe('250.00');
    expect($this->task->actual_revenue)->toBe('200.00');
});
