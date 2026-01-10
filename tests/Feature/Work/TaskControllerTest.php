<?php

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
    ]);
});

test('user can create a task', function () {
    $response = $this->actingAs($this->user)->post('/work/tasks', [
        'title' => 'Test Task',
        'workOrderId' => $this->workOrder->id,
        'description' => 'A test task description',
        'dueDate' => '2026-01-15',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('tasks', [
        'title' => 'Test Task',
        'work_order_id' => $this->workOrder->id,
        'team_id' => $this->team->id,
        'status' => 'todo',
    ]);
});

test('user can view a task', function () {
    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
    ]);

    $response = $this->actingAs($this->user)->get("/work/tasks/{$task->id}");

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) =>
        $page->component('work/tasks/[id]')
            ->has('task')
            ->where('task.id', (string) $task->id)
    );
});

test('user can update a task', function () {
    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
    ]);

    $response = $this->actingAs($this->user)->patch("/work/tasks/{$task->id}", [
        'title' => 'Updated Task Title',
        'description' => 'Updated description',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'title' => 'Updated Task Title',
        'description' => 'Updated description',
    ]);
});

test('user can update task status', function () {
    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'status' => 'todo',
    ]);

    $response = $this->actingAs($this->user)->patch("/work/tasks/{$task->id}/status", [
        'status' => 'in_progress',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'status' => 'in_progress',
    ]);
});

test('task status transitions work correctly', function () {
    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'status' => 'todo',
    ]);

    // Todo -> In Progress
    $this->actingAs($this->user)->patch("/work/tasks/{$task->id}/status", ['status' => 'in_progress']);
    expect($task->fresh()->status->value)->toBe('in_progress');

    // In Progress -> Done
    $this->actingAs($this->user)->patch("/work/tasks/{$task->id}/status", ['status' => 'done']);
    expect($task->fresh()->status->value)->toBe('done');

    // Done -> In Progress (reopen)
    $this->actingAs($this->user)->patch("/work/tasks/{$task->id}/status", ['status' => 'in_progress']);
    expect($task->fresh()->status->value)->toBe('in_progress');
});

test('user can toggle checklist item', function () {
    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'checklist_items' => [
            ['id' => 'item-1', 'text' => 'First item', 'completed' => false],
            ['id' => 'item-2', 'text' => 'Second item', 'completed' => false],
        ],
    ]);

    $response = $this->actingAs($this->user)->patch("/work/tasks/{$task->id}/checklist/item-1", [
        'completed' => true,
    ]);

    $response->assertRedirect();

    $task->refresh();
    expect($task->checklist_items[0]['completed'])->toBeTrue();
    expect($task->checklist_items[1]['completed'])->toBeFalse();
});

test('user can delete a task', function () {
    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
    ]);

    $response = $this->actingAs($this->user)->delete("/work/tasks/{$task->id}");

    $response->assertRedirect();

    $this->assertSoftDeleted('tasks', [
        'id' => $task->id,
    ]);
});

test('user cannot access tasks from another team', function () {
    $otherUser = User::factory()->create();
    $otherTeam = $otherUser->createTeam(['name' => 'Other Team']);
    $otherParty = Party::factory()->create(['team_id' => $otherTeam->id]);
    $otherProject = Project::factory()->create([
        'team_id' => $otherTeam->id,
        'party_id' => $otherParty->id,
        'owner_id' => $otherUser->id,
    ]);
    $otherWorkOrder = WorkOrder::factory()->create([
        'team_id' => $otherTeam->id,
        'project_id' => $otherProject->id,
        'created_by_id' => $otherUser->id,
    ]);
    $otherTask = Task::factory()->create([
        'team_id' => $otherTeam->id,
        'work_order_id' => $otherWorkOrder->id,
    ]);

    $response = $this->actingAs($this->user)->get("/work/tasks/{$otherTask->id}");

    $response->assertStatus(403);
});
