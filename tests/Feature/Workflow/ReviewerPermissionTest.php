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

    // Create a submitter (who creates work)
    $this->submitter = User::factory()->create();
    $this->submitter->current_team_id = $this->team->id;
    $this->submitter->save();

    // Create a designated reviewer
    $this->designatedReviewer = User::factory()->create();
    $this->designatedReviewer->current_team_id = $this->team->id;
    $this->designatedReviewer->save();

    // Create another team member (not the designated reviewer)
    $this->otherTeamMember = User::factory()->create();
    $this->otherTeamMember->current_team_id = $this->team->id;
    $this->otherTeamMember->save();

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
    ]);

    $this->service = new WorkflowTransitionService();
});

test('designated reviewer can approve task', function () {
    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->submitter->id,
        'assigned_to_id' => $this->submitter->id,
        'reviewer_id' => $this->designatedReviewer->id,
        'status' => TaskStatus::InReview,
    ]);

    // Create a transition record showing submitter submitted for review
    StatusTransition::create([
        'transitionable_type' => Task::class,
        'transitionable_id' => $task->id,
        'user_id' => $this->submitter->id,
        'from_status' => 'in_progress',
        'to_status' => 'in_review',
        'created_at' => now(),
    ]);

    // Designated reviewer approves - should succeed
    $transition = $this->service->transition($task, $this->designatedReviewer, TaskStatus::Approved);

    expect($transition)->toBeInstanceOf(StatusTransition::class);
    $task->refresh();
    expect($task->status)->toBe(TaskStatus::Approved);
});

test('non-designated reviewer cannot approve when reviewer is set', function () {
    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->submitter->id,
        'assigned_to_id' => $this->submitter->id,
        'reviewer_id' => $this->designatedReviewer->id,
        'status' => TaskStatus::InReview,
    ]);

    // Create a transition record showing submitter submitted for review
    StatusTransition::create([
        'transitionable_type' => Task::class,
        'transitionable_id' => $task->id,
        'user_id' => $this->submitter->id,
        'from_status' => 'in_progress',
        'to_status' => 'in_review',
        'created_at' => now(),
    ]);

    // Other team member (not designated reviewer) tries to approve - should fail
    $this->service->transition($task, $this->otherTeamMember, TaskStatus::Approved);
})->throws(InvalidTransitionException::class, 'Only the designated reviewer');

test('team owner can approve regardless of designated reviewer', function () {
    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->submitter->id,
        'assigned_to_id' => $this->submitter->id,
        'reviewer_id' => $this->designatedReviewer->id,
        'status' => TaskStatus::InReview,
    ]);

    // Create a transition record showing submitter submitted for review
    StatusTransition::create([
        'transitionable_type' => Task::class,
        'transitionable_id' => $task->id,
        'user_id' => $this->submitter->id,
        'from_status' => 'in_progress',
        'to_status' => 'in_review',
        'created_at' => now(),
    ]);

    // Owner approves (even though not designated reviewer) - should succeed
    $transition = $this->service->transition($task, $this->owner, TaskStatus::Approved);

    expect($transition)->toBeInstanceOf(StatusTransition::class);
    $task->refresh();
    expect($task->status)->toBe(TaskStatus::Approved);
});

test('project owner can approve via fallback resolution when no explicit reviewer', function () {
    // Create a project where otherTeamMember is the owner (fallback reviewer)
    $projectWithFallbackReviewer = Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $this->party->id,
        'owner_id' => $this->otherTeamMember->id, // Fallback reviewer via project ownership
    ]);

    // Create a work order with no explicit reviewer_id (RACI accountable set to project owner)
    $workOrderNoExplicitReviewer = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $projectWithFallbackReviewer->id,
        'created_by_id' => $this->owner->id,
        'accountable_id' => $this->otherTeamMember->id, // Same as project owner for consistency
        'assigned_to_id' => null, // No assignee
        'reviewer_id' => null, // No explicit reviewer
    ]);

    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $workOrderNoExplicitReviewer->id,
        'project_id' => $projectWithFallbackReviewer->id,
        'created_by_id' => $this->submitter->id,
        'assigned_to_id' => $this->submitter->id,
        'reviewer_id' => null, // No explicit task reviewer
        'status' => TaskStatus::InReview,
    ]);

    // Create a transition record showing submitter submitted for review
    StatusTransition::create([
        'transitionable_type' => Task::class,
        'transitionable_id' => $task->id,
        'user_id' => $this->submitter->id,
        'from_status' => 'in_progress',
        'to_status' => 'in_review',
        'created_at' => now(),
    ]);

    // RACI accountable (resolved as reviewer) approves - should succeed
    $transition = $this->service->transition($task, $this->otherTeamMember, TaskStatus::Approved);

    expect($transition)->toBeInstanceOf(StatusTransition::class);
    $task->refresh();
    expect($task->status)->toBe(TaskStatus::Approved);
});

test('accountable person from RACI can approve when set as reviewer via resolution', function () {
    // Set accountable person on work order (fallback reviewer)
    $this->workOrder->update(['accountable_id' => $this->designatedReviewer->id]);

    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->submitter->id,
        'assigned_to_id' => $this->submitter->id,
        'reviewer_id' => null, // No explicit reviewer, will fallback to RACI accountable
        'status' => TaskStatus::InReview,
    ]);

    // Create a transition record showing submitter submitted for review
    StatusTransition::create([
        'transitionable_type' => Task::class,
        'transitionable_id' => $task->id,
        'user_id' => $this->submitter->id,
        'from_status' => 'in_progress',
        'to_status' => 'in_review',
        'created_at' => now(),
    ]);

    // Accountable person (resolved as reviewer) approves - should succeed
    $transition = $this->service->transition($task, $this->designatedReviewer, TaskStatus::Approved);

    expect($transition)->toBeInstanceOf(StatusTransition::class);
    $task->refresh();
    expect($task->status)->toBe(TaskStatus::Approved);
});

test('non-accountable person cannot approve when RACI accountable is set', function () {
    // Set accountable person on work order (fallback reviewer)
    $this->workOrder->update(['accountable_id' => $this->designatedReviewer->id]);

    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->submitter->id,
        'assigned_to_id' => $this->submitter->id,
        'reviewer_id' => null, // No explicit reviewer, will fallback to RACI accountable
        'status' => TaskStatus::InReview,
    ]);

    // Create a transition record showing submitter submitted for review
    StatusTransition::create([
        'transitionable_type' => Task::class,
        'transitionable_id' => $task->id,
        'user_id' => $this->submitter->id,
        'from_status' => 'in_progress',
        'to_status' => 'in_review',
        'created_at' => now(),
    ]);

    // Other team member (not the accountable person) tries to approve - should fail
    $this->service->transition($task, $this->otherTeamMember, TaskStatus::Approved);
})->throws(InvalidTransitionException::class, 'Only the designated reviewer');
