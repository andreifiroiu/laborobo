<?php

declare(strict_types=1);

use App\Enums\TaskStatus;
use App\Models\Party;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\TimeEntry;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\TimerTransitionService;

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

    $this->service = new TimerTransitionService();
});

test('starting timer on Todo task transitions to InProgress', function () {
    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
        'status' => TaskStatus::Todo,
    ]);

    $result = $this->service->checkAndStartTimer($task, $this->user);

    expect($result['status'])->toBe('started');
    expect($result['time_entry'])->toBeInstanceOf(TimeEntry::class);

    $task->refresh();
    expect($task->status)->toBe(TaskStatus::InProgress);
});

test('starting timer on Cancelled task is blocked', function () {
    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
        'status' => TaskStatus::Cancelled,
    ]);

    $result = $this->service->checkAndStartTimer($task, $this->user);

    expect($result['status'])->toBe('blocked');
    expect($result['reason'])->toBe('Task is cancelled and cannot have a timer started.');

    // No time entry should be created
    expect(TimeEntry::where('task_id', $task->id)->count())->toBe(0);
});

test('starting timer returns confirmation_required flag for Done task', function () {
    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
        'status' => TaskStatus::Done,
    ]);

    $result = $this->service->checkAndStartTimer($task, $this->user);

    expect($result['status'])->toBe('confirmation_required');
    expect($result['reason'])->toContain('Done');
    expect($result['current_status'])->toBe('done');

    // Task status should not change yet
    $task->refresh();
    expect($task->status)->toBe(TaskStatus::Done);
});

test('starting timer returns confirmation_required flag for InReview and Approved tasks', function () {
    // Test InReview status
    $taskInReview = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
        'status' => TaskStatus::InReview,
    ]);

    $resultInReview = $this->service->checkAndStartTimer($taskInReview, $this->user);
    expect($resultInReview['status'])->toBe('confirmation_required');
    expect($resultInReview['current_status'])->toBe('in_review');

    // Test Approved status
    $taskApproved = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
        'status' => TaskStatus::Approved,
    ]);

    $resultApproved = $this->service->checkAndStartTimer($taskApproved, $this->user);
    expect($resultApproved['status'])->toBe('confirmation_required');
    expect($resultApproved['current_status'])->toBe('approved');
});

test('confirmAndStartTimer transitions task and starts timer', function () {
    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
        'status' => TaskStatus::Done,
    ]);

    $timeEntry = $this->service->confirmAndStartTimer($task, $this->user);

    expect($timeEntry)->toBeInstanceOf(TimeEntry::class);
    expect($timeEntry->task_id)->toBe($task->id);
    expect($timeEntry->user_id)->toBe($this->user->id);
    expect($timeEntry->started_at)->not->toBeNull();

    $task->refresh();
    expect($task->status)->toBe(TaskStatus::InProgress);

    // Verify status transition was recorded
    expect($task->statusTransitions)->toHaveCount(1);
    $transition = $task->statusTransitions->first();
    expect($transition->from_status)->toBe('done');
    expect($transition->to_status)->toBe('in_progress');
});
