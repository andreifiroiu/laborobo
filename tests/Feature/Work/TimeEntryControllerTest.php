<?php

use App\Models\Party;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
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

test('user can log time manually', function () {
    $response = $this->actingAs($this->user)->post('/work/time-entries', [
        'taskId' => $this->task->id,
        'hours' => 2.5,
        'date' => '2026-01-10',
        'note' => 'Worked on feature implementation',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('time_entries', [
        'task_id' => $this->task->id,
        'user_id' => $this->user->id,
        'hours' => 2.5,
        'mode' => 'manual',
        'note' => 'Worked on feature implementation',
    ]);
});

test('user can start a timer', function () {
    $response = $this->actingAs($this->user)->post("/work/tasks/{$this->task->id}/timer/start");

    $response->assertRedirect();

    $this->assertDatabaseHas('time_entries', [
        'task_id' => $this->task->id,
        'user_id' => $this->user->id,
        'mode' => 'timer',
    ]);

    $timeEntry = TimeEntry::where('task_id', $this->task->id)->first();
    expect($timeEntry->started_at)->not->toBeNull();
    expect($timeEntry->stopped_at)->toBeNull();
});

test('user can stop a timer', function () {
    // Start a timer first
    $this->actingAs($this->user)->post("/work/tasks/{$this->task->id}/timer/start");

    // Stop the timer
    $response = $this->actingAs($this->user)->post("/work/tasks/{$this->task->id}/timer/stop");

    $response->assertRedirect();

    $timeEntry = TimeEntry::where('task_id', $this->task->id)->first();
    expect($timeEntry->stopped_at)->not->toBeNull();
    expect($timeEntry->hours)->toBeGreaterThanOrEqual(0);
});

test('starting a new timer stops any active timer', function () {
    // Start first timer
    $this->actingAs($this->user)->post("/work/tasks/{$this->task->id}/timer/start");
    $firstEntry = TimeEntry::where('task_id', $this->task->id)->first();

    // Create another task
    $anotherTask = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
    ]);

    // Start timer on another task
    $this->actingAs($this->user)->post("/work/tasks/{$anotherTask->id}/timer/start");

    // First timer should be stopped
    $firstEntry->refresh();
    expect($firstEntry->stopped_at)->not->toBeNull();
});

test('time entry updates task actual hours', function () {
    // Set initial hours to 0 to have a clean starting point
    $this->task->update(['actual_hours' => 0]);

    $this->actingAs($this->user)->post('/work/time-entries', [
        'taskId' => $this->task->id,
        'hours' => 3.0,
        'date' => '2026-01-10',
    ]);

    $this->task->refresh();
    // recalculateActualHours sums all time entries, which is 3.0
    expect((float) $this->task->actual_hours)->toBe(3.0);
});

test('logged time validation requires positive hours', function () {
    $response = $this->actingAs($this->user)->post('/work/time-entries', [
        'taskId' => $this->task->id,
        'hours' => -1,
        'date' => '2026-01-10',
    ]);

    $response->assertSessionHasErrors('hours');
});

test('logged time validation requires valid date', function () {
    $response = $this->actingAs($this->user)->post('/work/time-entries', [
        'taskId' => $this->task->id,
        'hours' => 1,
        'date' => 'invalid-date',
    ]);

    $response->assertSessionHasErrors('date');
});
