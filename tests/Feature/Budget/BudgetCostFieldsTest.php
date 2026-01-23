<?php

declare(strict_types=1);

use App\Enums\BudgetType;
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
});

test('project budget_type enum values are stored correctly', function () {
    $project = Project::factory()->create([
        'team_id' => $this->team->id,
        'owner_id' => $this->user->id,
        'budget_type' => BudgetType::FixedPrice,
    ]);

    expect($project->budget_type)->toBe(BudgetType::FixedPrice);
    expect($project->budget_type->value)->toBe('fixed_price');
    expect($project->budget_type->label())->toBe('Fixed Price');

    $project->budget_type = BudgetType::TimeAndMaterials;
    $project->save();
    $project->refresh();

    expect($project->budget_type)->toBe(BudgetType::TimeAndMaterials);
    expect($project->budget_type->value)->toBe('time_and_materials');

    $project->budget_type = BudgetType::MonthlySubscription;
    $project->save();
    $project->refresh();

    expect($project->budget_type)->toBe(BudgetType::MonthlySubscription);
});

test('work order budget_type enum values are stored correctly', function () {
    $project = Project::factory()->create([
        'team_id' => $this->team->id,
        'owner_id' => $this->user->id,
    ]);

    $workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $project->id,
        'created_by_id' => $this->user->id,
        'budget_type' => BudgetType::TimeAndMaterials,
    ]);

    expect($workOrder->budget_type)->toBe(BudgetType::TimeAndMaterials);
    expect($workOrder->budget_type->label())->toBe('Time & Materials');

    $workOrder->budget_type = BudgetType::FixedPrice;
    $workOrder->save();
    $workOrder->refresh();

    expect($workOrder->budget_type)->toBe(BudgetType::FixedPrice);
});

test('project and work order budget_cost and actual_cost fields store decimal values', function () {
    $project = Project::factory()->create([
        'team_id' => $this->team->id,
        'owner_id' => $this->user->id,
        'budget_type' => BudgetType::FixedPrice,
        'budget_cost' => 15000.50,
        'actual_cost' => 8750.25,
        'actual_revenue' => 12500.00,
    ]);

    expect($project->budget_cost)->toBe('15000.50');
    expect($project->actual_cost)->toBe('8750.25');
    expect($project->actual_revenue)->toBe('12500.00');

    $workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $project->id,
        'created_by_id' => $this->user->id,
        'budget_type' => BudgetType::FixedPrice,
        'budget_cost' => 5000.00,
        'actual_cost' => 2500.75,
        'actual_revenue' => 4000.00,
    ]);

    expect($workOrder->budget_cost)->toBe('5000.00');
    expect($workOrder->actual_cost)->toBe('2500.75');
    expect($workOrder->actual_revenue)->toBe('4000.00');
});

test('time entry cost fields store rate and calculated values', function () {
    // Set up user rate for automatic cost calculation via observer
    UserRate::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'internal_rate' => 50.00,
        'billing_rate' => 100.00,
        'effective_date' => now()->subMonth(),
    ]);

    $project = Project::factory()->create([
        'team_id' => $this->team->id,
        'owner_id' => $this->user->id,
    ]);

    $workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $project->id,
        'created_by_id' => $this->user->id,
    ]);

    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $workOrder->id,
        'project_id' => $project->id,
        'created_by_id' => $this->user->id,
    ]);

    // Create time entry - observer will automatically calculate cost/revenue
    $timeEntry = TimeEntry::factory()->manual()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'task_id' => $task->id,
        'hours' => 2.5,
        'date' => now(),
        'is_billable' => true,
    ]);

    $timeEntry->refresh();

    // Verify the observer calculated and stored the correct values
    expect($timeEntry->cost_rate)->toBe('50.00');
    expect($timeEntry->billing_rate)->toBe('100.00');
    expect($timeEntry->calculated_cost)->toBe('125.00'); // 2.5 * 50
    expect($timeEntry->calculated_revenue)->toBe('250.00'); // 2.5 * 100
});

test('task actual_cost and actual_revenue fields store decimal values', function () {
    $project = Project::factory()->create([
        'team_id' => $this->team->id,
        'owner_id' => $this->user->id,
    ]);

    $workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $project->id,
        'created_by_id' => $this->user->id,
    ]);

    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $workOrder->id,
        'project_id' => $project->id,
        'created_by_id' => $this->user->id,
        'actual_cost' => 375.50,
        'actual_revenue' => 750.00,
    ]);

    expect($task->actual_cost)->toBe('375.50');
    expect($task->actual_revenue)->toBe('750.00');
});

test('budget fields can be null when not set', function () {
    $project = Project::factory()->create([
        'team_id' => $this->team->id,
        'owner_id' => $this->user->id,
        'budget_type' => null,
        'budget_cost' => null,
    ]);

    expect($project->budget_type)->toBeNull();
    expect($project->budget_cost)->toBeNull();

    $workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $project->id,
        'created_by_id' => $this->user->id,
        'budget_type' => null,
        'budget_cost' => null,
    ]);

    expect($workOrder->budget_type)->toBeNull();
    expect($workOrder->budget_cost)->toBeNull();
});
