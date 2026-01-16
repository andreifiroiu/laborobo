<?php

declare(strict_types=1);

use App\Models\Party;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use App\Models\WorkOrder;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->team = $this->user->createTeam(['name' => 'Test Team']);
    $this->user->current_team_id = $this->team->id;
    $this->user->save();

    $this->party = Party::factory()->create(['team_id' => $this->team->id]);
    $this->project = Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $this->party->id,
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
    ]);
});

test('time entry can be created with is_billable set to true', function () {
    $timeEntry = TimeEntry::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'task_id' => $this->task->id,
        'is_billable' => true,
    ]);

    expect($timeEntry->is_billable)->toBeTrue();

    $this->assertDatabaseHas('time_entries', [
        'id' => $timeEntry->id,
        'is_billable' => true,
    ]);
});

test('time entry can be created with is_billable set to false', function () {
    $timeEntry = TimeEntry::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'task_id' => $this->task->id,
        'is_billable' => false,
    ]);

    expect($timeEntry->is_billable)->toBeFalse();

    $this->assertDatabaseHas('time_entries', [
        'id' => $timeEntry->id,
        'is_billable' => false,
    ]);
});

test('time entry defaults is_billable to true when not specified', function () {
    $timeEntry = TimeEntry::factory()->manual()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'task_id' => $this->task->id,
    ]);

    expect($timeEntry->is_billable)->toBeTrue();
});

test('startTimer accepts optional is_billable parameter', function () {
    $billableEntry = TimeEntry::startTimer($this->task, $this->user, true);
    expect($billableEntry->is_billable)->toBeTrue();

    $billableEntry->stopTimer();

    $nonBillableEntry = TimeEntry::startTimer($this->task, $this->user, false);
    expect($nonBillableEntry->is_billable)->toBeFalse();
});
