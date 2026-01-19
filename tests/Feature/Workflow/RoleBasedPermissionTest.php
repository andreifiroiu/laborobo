<?php

declare(strict_types=1);

use App\Enums\TaskStatus;
use App\Exceptions\InvalidTransitionException;
use App\Models\Party;
use App\Models\Project;
use App\Models\StatusTransition;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\WorkflowTransitionService;

beforeEach(function () {
    // Create team owner
    $this->owner = User::factory()->create();
    $this->team = $this->owner->createTeam(['name' => 'Test Team']);
    $this->owner->current_team_id = $this->team->id;
    $this->owner->save();

    // Create a regular team member (different user)
    $this->otherUser = User::factory()->create();
    $this->otherUser->current_team_id = $this->team->id;
    $this->otherUser->save();

    $this->party = Party::factory()->create(['team_id' => $this->team->id]);
    $this->project = Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $this->party->id,
        'owner_id' => $this->owner->id,
    ]);
    $this->workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->owner->id,
        'accountable_id' => $this->owner->id,
    ]);

    $this->service = new WorkflowTransitionService();
});

test('team owner can always approve work', function () {
    // Create task already in review
    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->otherUser->id,
        'assigned_to_id' => $this->otherUser->id,
        'status' => TaskStatus::InReview,
    ]);

    // Create a transition record showing someone else submitted for review
    StatusTransition::create([
        'transitionable_type' => Task::class,
        'transitionable_id' => $task->id,
        'user_id' => $this->otherUser->id,
        'from_status' => 'in_progress',
        'to_status' => 'in_review',
        'created_at' => now(),
    ]);

    // Owner approves - should succeed
    $transition = $this->service->transition($task, $this->owner, TaskStatus::Approved);

    expect($transition)->toBeInstanceOf(StatusTransition::class);
    $task->refresh();
    expect($task->status)->toBe(TaskStatus::Approved);
});

test('user cannot self-approve their own submission', function () {
    // Create task already in review, submitted by otherUser
    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->otherUser->id,
        'assigned_to_id' => $this->otherUser->id,
        'status' => TaskStatus::InReview,
    ]);

    // Create a transition record showing otherUser submitted for review
    StatusTransition::create([
        'transitionable_type' => Task::class,
        'transitionable_id' => $task->id,
        'user_id' => $this->otherUser->id,
        'from_status' => 'in_progress',
        'to_status' => 'in_review',
        'created_at' => now(),
    ]);

    // otherUser tries to approve their own work - should fail
    $this->service->transition($task, $this->otherUser, TaskStatus::Approved);
})->throws(InvalidTransitionException::class, 'permission');

test('designated reviewer can approve work when not self-approving', function () {
    // Another user designated as reviewer
    $thirdUser = User::factory()->create();
    $thirdUser->current_team_id = $this->team->id;
    $thirdUser->save();

    // Create task already in review, submitted by otherUser, with thirdUser as designated reviewer
    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->otherUser->id,
        'assigned_to_id' => $this->otherUser->id,
        'reviewer_id' => $thirdUser->id, // Designate thirdUser as reviewer
        'status' => TaskStatus::InReview,
    ]);

    // Create a transition record showing otherUser submitted for review
    StatusTransition::create([
        'transitionable_type' => Task::class,
        'transitionable_id' => $task->id,
        'user_id' => $this->otherUser->id,
        'from_status' => 'in_progress',
        'to_status' => 'in_review',
        'created_at' => now(),
    ]);

    // thirdUser (designated reviewer) approves - should succeed
    $transition = $this->service->transition($task, $thirdUser, TaskStatus::Approved);

    expect($transition)->toBeInstanceOf(StatusTransition::class);
    $task->refresh();
    expect($task->status)->toBe(TaskStatus::Approved);
});

test('assigned user can mark approved task as done', function () {
    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->owner->id,
        'assigned_to_id' => $this->otherUser->id,
        'status' => TaskStatus::Approved,
    ]);

    // Assigned user marks as done - should succeed
    $transition = $this->service->transition($task, $this->otherUser, TaskStatus::Done);

    expect($transition)->toBeInstanceOf(StatusTransition::class);
    $task->refresh();
    expect($task->status)->toBe(TaskStatus::Done);
});

test('non-assigned user cannot mark approved task as done', function () {
    $thirdUser = User::factory()->create();
    $thirdUser->current_team_id = $this->team->id;
    $thirdUser->save();

    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->owner->id,
        'assigned_to_id' => $this->otherUser->id,
        'status' => TaskStatus::Approved,
    ]);

    // thirdUser (not assigned) tries to mark as done - should fail
    $this->service->transition($task, $thirdUser, TaskStatus::Done);
})->throws(InvalidTransitionException::class, 'permission');

test('team owner can always mark work as done', function () {
    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->otherUser->id,
        'assigned_to_id' => $this->otherUser->id,
        'status' => TaskStatus::Approved,
    ]);

    // Owner marks as done - should succeed regardless of assignment
    $transition = $this->service->transition($task, $this->owner, TaskStatus::Done);

    expect($transition)->toBeInstanceOf(StatusTransition::class);
    $task->refresh();
    expect($task->status)->toBe(TaskStatus::Done);
});
