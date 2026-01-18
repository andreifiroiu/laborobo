<?php

declare(strict_types=1);

use App\Enums\TaskStatus;
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
        'accountable_id' => $this->user->id,
    ]);
});

test('POST /tasks/{id}/timer/start returns confirmation_required response for Done task', function () {
    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
        'status' => TaskStatus::Done,
    ]);

    $response = $this->actingAs($this->user)
        ->postJson(route('tasks.timer.start', $task));

    $response->assertStatus(200);
    $response->assertJson([
        'confirmation_required' => true,
        'current_status' => 'done',
    ]);
    $response->assertJsonStructure([
        'confirmation_required',
        'current_status',
        'message',
    ]);

    // Timer should not be started yet
    expect(TimeEntry::where('task_id', $task->id)->count())->toBe(0);

    // Task status should remain unchanged
    $task->refresh();
    expect($task->status)->toBe(TaskStatus::Done);
});

test('POST /tasks/{id}/timer/start with confirmed=true transitions and starts timer', function () {
    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
        'status' => TaskStatus::Done,
    ]);

    $response = $this->actingAs($this->user)
        ->postJson(route('tasks.timer.start', $task) . '?confirmed=true');

    $response->assertStatus(200);
    $response->assertJson([
        'started' => true,
    ]);
    $response->assertJsonStructure([
        'started',
        'time_entry' => [
            'id',
            'task_id',
            'user_id',
            'started_at',
        ],
    ]);

    // Timer should be started
    $timeEntry = TimeEntry::where('task_id', $task->id)->first();
    expect($timeEntry)->not->toBeNull();
    expect($timeEntry->started_at)->not->toBeNull();
    expect($timeEntry->stopped_at)->toBeNull();

    // Task status should be transitioned to InProgress
    $task->refresh();
    expect($task->status)->toBe(TaskStatus::InProgress);
});

test('timer blocked for cancelled task returns 422', function () {
    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
        'status' => TaskStatus::Cancelled,
    ]);

    $response = $this->actingAs($this->user)
        ->postJson(route('tasks.timer.start', $task));

    $response->assertStatus(422);
    $response->assertJson([
        'blocked' => true,
        'current_status' => 'cancelled',
    ]);
    $response->assertJsonStructure([
        'blocked',
        'current_status',
        'message',
    ]);

    // No timer should be created
    expect(TimeEntry::where('task_id', $task->id)->count())->toBe(0);
});

test('POST /tasks/{id}/timer/start on Todo task auto-transitions and starts timer', function () {
    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
        'status' => TaskStatus::Todo,
    ]);

    $response = $this->actingAs($this->user)
        ->postJson(route('tasks.timer.start', $task));

    $response->assertStatus(200);
    $response->assertJson([
        'started' => true,
    ]);

    // Timer should be started
    $timeEntry = TimeEntry::where('task_id', $task->id)->first();
    expect($timeEntry)->not->toBeNull();
    expect($timeEntry->started_at)->not->toBeNull();

    // Task status should be auto-transitioned to InProgress
    $task->refresh();
    expect($task->status)->toBe(TaskStatus::InProgress);
});
