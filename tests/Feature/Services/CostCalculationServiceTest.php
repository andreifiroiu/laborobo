<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\ProjectUserRate;
use App\Models\Task;
use App\Models\Team;
use App\Models\TimeEntry;
use App\Models\User;
use App\Models\UserRate;
use App\Models\WorkOrder;
use App\Services\CostCalculationService;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->team = $this->user->createTeam(['name' => 'Test Team']);
    $this->user->current_team_id = $this->team->id;
    $this->user->save();

    $this->project = Project::factory()->create([
        'team_id' => $this->team->id,
        'owner_id' => $this->user->id,
    ]);

    $this->workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
    ]);

    $this->task = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
    ]);

    $this->service = app(CostCalculationService::class);
});

test('rate lookup uses project override first when available', function () {
    // Create team default rate
    UserRate::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'internal_rate' => 50.00,
        'billing_rate' => 100.00,
        'effective_date' => now()->subMonth(),
    ]);

    // Create project-specific override with higher rates
    ProjectUserRate::factory()->create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'internal_rate' => 75.00,
        'billing_rate' => 150.00,
        'effective_date' => now()->subMonth(),
    ]);

    $rates = $this->service->getRateForUser($this->user, $this->project, now());

    expect($rates['internal_rate'])->toBe('75.00');
    expect($rates['billing_rate'])->toBe('150.00');
});

test('rate lookup falls back to team default when no project override exists', function () {
    // Create team default rate only
    UserRate::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'internal_rate' => 50.00,
        'billing_rate' => 100.00,
        'effective_date' => now()->subMonth(),
    ]);

    $rates = $this->service->getRateForUser($this->user, $this->project, now());

    expect($rates['internal_rate'])->toBe('50.00');
    expect($rates['billing_rate'])->toBe('100.00');
});

test('cost calculation for billable time entry calculates cost and revenue', function () {
    // Set up user rate
    UserRate::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'internal_rate' => 50.00,
        'billing_rate' => 100.00,
        'effective_date' => now()->subMonth(),
    ]);

    $timeEntry = TimeEntry::factory()->manual()->make([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'task_id' => $this->task->id,
        'hours' => 2.5,
        'date' => now(),
        'is_billable' => true,
    ]);

    $this->service->calculateCost($timeEntry);

    expect($timeEntry->cost_rate)->toBe('50.00');
    expect($timeEntry->billing_rate)->toBe('100.00');
    expect($timeEntry->calculated_cost)->toBe('125.00'); // 2.5 * 50
    expect($timeEntry->calculated_revenue)->toBe('250.00'); // 2.5 * 100
});

test('cost calculation for non-billable time entry sets revenue to zero', function () {
    // Set up user rate
    UserRate::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'internal_rate' => 50.00,
        'billing_rate' => 100.00,
        'effective_date' => now()->subMonth(),
    ]);

    $timeEntry = TimeEntry::factory()->manual()->make([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'task_id' => $this->task->id,
        'hours' => 2.5,
        'date' => now(),
        'is_billable' => false,
    ]);

    $this->service->calculateCost($timeEntry);

    expect($timeEntry->cost_rate)->toBe('50.00');
    expect($timeEntry->billing_rate)->toBe('100.00');
    expect($timeEntry->calculated_cost)->toBe('125.00'); // 2.5 * 50 - cost is always calculated
    expect($timeEntry->calculated_revenue)->toBe('0.00'); // Non-billable = 0 revenue
});

test('rate snapshot is captured on time entry creation via observer', function () {
    // Set up user rate
    UserRate::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'internal_rate' => 60.00,
        'billing_rate' => 120.00,
        'effective_date' => now()->subMonth(),
    ]);

    // Create a time entry - observer should automatically snapshot rates
    $timeEntry = TimeEntry::factory()->manual()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'task_id' => $this->task->id,
        'hours' => 3.0,
        'date' => now(),
        'is_billable' => true,
    ]);

    $timeEntry->refresh();

    expect($timeEntry->cost_rate)->toBe('60.00');
    expect($timeEntry->billing_rate)->toBe('120.00');
    expect($timeEntry->calculated_cost)->toBe('180.00'); // 3.0 * 60
    expect($timeEntry->calculated_revenue)->toBe('360.00'); // 3.0 * 120
});

test('cost recalculates when hours are updated', function () {
    // Set up user rate
    UserRate::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'internal_rate' => 50.00,
        'billing_rate' => 100.00,
        'effective_date' => now()->subMonth(),
    ]);

    // Create a time entry
    $timeEntry = TimeEntry::factory()->manual()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'task_id' => $this->task->id,
        'hours' => 2.0,
        'date' => now(),
        'is_billable' => true,
    ]);

    // Verify initial calculation
    expect($timeEntry->calculated_cost)->toBe('100.00'); // 2.0 * 50
    expect($timeEntry->calculated_revenue)->toBe('200.00'); // 2.0 * 100

    // Update hours
    $timeEntry->hours = 4.0;
    $timeEntry->save();
    $timeEntry->refresh();

    // Verify recalculation with same snapshotted rates
    expect($timeEntry->cost_rate)->toBe('50.00');
    expect($timeEntry->calculated_cost)->toBe('200.00'); // 4.0 * 50
    expect($timeEntry->calculated_revenue)->toBe('400.00'); // 4.0 * 100
});
