<?php

declare(strict_types=1);

use App\Enums\TaskStatus;
use App\Models\Party;
use App\Models\Project;
use App\Models\StatusTransition;
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
        'accountable_id' => $this->user->id,
    ]);
    $this->workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
        'accountable_id' => $this->user->id,
    ]);
});

test('Task created_by_id field stores the creator and relationship works', function () {
    $creator = User::factory()->create();

    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'project_id' => $this->project->id,
        'created_by_id' => $creator->id,
    ]);

    expect($task->created_by_id)->toBe($creator->id);
    expect($task->createdBy)->toBeInstanceOf(User::class);
    expect($task->createdBy->id)->toBe($creator->id);
});

test('Task reviewer_id relationship resolves to User model', function () {
    $reviewer = User::factory()->create();

    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
        'reviewer_id' => $reviewer->id,
    ]);

    expect($task->reviewer_id)->toBe($reviewer->id);
    expect($task->reviewer)->toBeInstanceOf(User::class);
    expect($task->reviewer->id)->toBe($reviewer->id);
});

test('Task reviewer_id can be null', function () {
    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
        'reviewer_id' => null,
    ]);

    expect($task->reviewer_id)->toBeNull();
    expect($task->reviewer)->toBeNull();
});

test('Task statusTransitions relationship returns correct records in descending order', function () {
    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
        'status' => TaskStatus::InProgress,
    ]);

    $firstTransition = StatusTransition::create([
        'transitionable_type' => Task::class,
        'transitionable_id' => $task->id,
        'user_id' => $this->user->id,
        'from_status' => TaskStatus::Todo->value,
        'to_status' => TaskStatus::InProgress->value,
        'comment' => 'Started working',
        'created_at' => now()->subMinutes(10),
    ]);

    $secondTransition = StatusTransition::create([
        'transitionable_type' => Task::class,
        'transitionable_id' => $task->id,
        'user_id' => $this->user->id,
        'from_status' => TaskStatus::InProgress->value,
        'to_status' => TaskStatus::InReview->value,
        'comment' => 'Submitted for review',
        'created_at' => now(),
    ]);

    $task->refresh();

    expect($task->statusTransitions)->toHaveCount(2);
    expect($task->statusTransitions->first()->id)->toBe($secondTransition->id);
    expect($task->statusTransitions->last()->id)->toBe($firstTransition->id);
});
