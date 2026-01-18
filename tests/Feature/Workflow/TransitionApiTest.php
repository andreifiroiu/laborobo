<?php

declare(strict_types=1);

use App\Enums\TaskStatus;
use App\Enums\WorkOrderStatus;
use App\Models\AIAgent;
use App\Models\Party;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
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
        'status' => WorkOrderStatus::Draft,
    ]);
});

test('POST /tasks/{id}/transition validates allowed transition', function () {
    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
        'status' => TaskStatus::Todo,
    ]);

    $response = $this->actingAs($this->user)
        ->postJson(route('tasks.transition', $task), [
            'status' => 'in_progress',
        ]);

    $response->assertStatus(200);
    $response->assertJsonPath('task.status', 'in_progress');

    $task->refresh();
    expect($task->status)->toBe(TaskStatus::InProgress);
});

test('POST /tasks/{id}/transition rejects invalid transition', function () {
    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
        'status' => TaskStatus::Todo,
    ]);

    $response = $this->actingAs($this->user)
        ->postJson(route('tasks.transition', $task), [
            'status' => 'approved',
        ]);

    $response->assertStatus(422);
    $response->assertJsonPath('reason', 'invalid_transition');
});

test('POST /work-orders/{id}/transition validates allowed transition', function () {
    $workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
        'accountable_id' => $this->user->id,
        'status' => WorkOrderStatus::Draft,
    ]);

    $response = $this->actingAs($this->user)
        ->postJson(route('work-orders.transition', $workOrder), [
            'status' => 'active',
        ]);

    $response->assertStatus(200);
    $response->assertJsonPath('workOrder.status', 'active');

    $workOrder->refresh();
    expect($workOrder->status)->toBe(WorkOrderStatus::Active);
});

test('rejection requires comment in request body', function () {
    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
        'status' => TaskStatus::InReview,
    ]);

    // Without comment
    $response = $this->actingAs($this->user)
        ->postJson(route('tasks.transition', $task), [
            'status' => 'revision_requested',
        ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['comment']);
});

test('rejection with comment succeeds', function () {
    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
        'status' => TaskStatus::InReview,
    ]);

    $response = $this->actingAs($this->user)
        ->postJson(route('tasks.transition', $task), [
            'status' => 'revision_requested',
            'comment' => 'Please fix the formatting issues.',
        ]);

    $response->assertStatus(200);
    // After revision_requested, it auto-transitions to in_progress
    $response->assertJsonPath('task.status', 'in_progress');

    $task->refresh();
    expect($task->status)->toBe(TaskStatus::InProgress);
    expect($task->statusTransitions)->toHaveCount(2);
});

test('successful transition returns updated model with history', function () {
    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
        'status' => TaskStatus::Todo,
    ]);

    $response = $this->actingAs($this->user)
        ->postJson(route('tasks.transition', $task), [
            'status' => 'in_progress',
        ]);

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'task' => [
            'id',
            'status',
            'statusTransitions' => [
                '*' => [
                    'id',
                    'from_status',
                    'to_status',
                    'comment',
                    'created_at',
                ],
            ],
        ],
    ]);

    $response->assertJsonPath('task.statusTransitions.0.from_status', 'todo');
    $response->assertJsonPath('task.statusTransitions.0.to_status', 'in_progress');
});
