<?php

declare(strict_types=1);

use App\Models\Party;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\ReviewerResolver;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->team = $this->user->createTeam(['name' => 'Test Team']);
    $this->user->current_team_id = $this->team->id;
    $this->user->save();

    $this->party = Party::factory()->create(['team_id' => $this->team->id]);

    $this->resolver = new ReviewerResolver();
});

test('explicit reviewer_id takes priority for Task', function () {
    $explicitReviewer = User::factory()->create();
    $accountableUser = User::factory()->create();
    $workOrderAssignee = User::factory()->create();
    $projectOwner = User::factory()->create();

    $project = Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $this->party->id,
        'owner_id' => $projectOwner->id,
        'accountable_id' => $projectOwner->id,
    ]);

    $workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $project->id,
        'created_by_id' => $this->user->id,
        'accountable_id' => $accountableUser->id,
        'assigned_to_id' => $workOrderAssignee->id,
        'reviewer_id' => null,
    ]);

    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $workOrder->id,
        'project_id' => $project->id,
        'created_by_id' => $this->user->id,
        'reviewer_id' => $explicitReviewer->id,
    ]);

    $reviewer = $this->resolver->resolve($task);

    expect($reviewer)->toBeInstanceOf(User::class);
    expect($reviewer->id)->toBe($explicitReviewer->id);
});

test('explicit reviewer_id takes priority for WorkOrder', function () {
    $explicitReviewer = User::factory()->create();
    $accountableUser = User::factory()->create();
    $projectOwner = User::factory()->create();

    $project = Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $this->party->id,
        'owner_id' => $projectOwner->id,
        'accountable_id' => $projectOwner->id,
    ]);

    $workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $project->id,
        'created_by_id' => $this->user->id,
        'accountable_id' => $accountableUser->id,
        'reviewer_id' => $explicitReviewer->id,
        'assigned_to_id' => $this->user->id,
    ]);

    $reviewer = $this->resolver->resolve($workOrder);

    expect($reviewer)->toBeInstanceOf(User::class);
    expect($reviewer->id)->toBe($explicitReviewer->id);
});

test('fallback to Accountable person when no explicit reviewer', function () {
    $accountableUser = User::factory()->create();
    $workOrderAssignee = User::factory()->create();
    $projectOwner = User::factory()->create();

    $project = Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $this->party->id,
        'owner_id' => $projectOwner->id,
        'accountable_id' => $projectOwner->id,
    ]);

    $workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $project->id,
        'created_by_id' => $this->user->id,
        'accountable_id' => $accountableUser->id,
        'reviewer_id' => null,
        'assigned_to_id' => $workOrderAssignee->id,
    ]);

    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $workOrder->id,
        'project_id' => $project->id,
        'created_by_id' => $this->user->id,
        'reviewer_id' => null,
    ]);

    $reviewer = $this->resolver->resolve($task);

    expect($reviewer)->toBeInstanceOf(User::class);
    expect($reviewer->id)->toBe($accountableUser->id);
});

test('fallback chain works correctly for WorkOrder resolution', function () {
    // Tests that the resolver correctly follows the priority chain for WorkOrders
    $workOrderAssignee = User::factory()->create();
    $accountableUser = User::factory()->create();
    $projectOwner = User::factory()->create();

    $project = Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $this->party->id,
        'owner_id' => $projectOwner->id,
        'accountable_id' => $projectOwner->id,
    ]);

    // WorkOrder with no reviewer but has accountable - should return accountable
    $workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $project->id,
        'created_by_id' => $this->user->id,
        'accountable_id' => $accountableUser->id,
        'reviewer_id' => null,
        'assigned_to_id' => $workOrderAssignee->id,
    ]);

    $reviewer = $this->resolver->resolve($workOrder);

    // Should return the accountable user as that's priority 2
    expect($reviewer)->toBeInstanceOf(User::class);
    expect($reviewer->id)->toBe($accountableUser->id);
});

test('priority order is respected with all fallback levels populated', function () {
    // This test verifies the complete priority chain:
    // Priority 1: reviewer_id > Priority 2: accountable_id > Priority 3: assigned_to_id > Priority 4: project owner_id
    $explicitReviewer = User::factory()->create();
    $accountableUser = User::factory()->create();
    $workOrderAssignee = User::factory()->create();
    $projectOwner = User::factory()->create();

    $project = Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $this->party->id,
        'owner_id' => $projectOwner->id,
        'accountable_id' => $projectOwner->id,
    ]);

    $workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $project->id,
        'created_by_id' => $this->user->id,
        'accountable_id' => $accountableUser->id,
        'reviewer_id' => null,
        'assigned_to_id' => $workOrderAssignee->id,
    ]);

    // Task with explicit reviewer - should return explicit reviewer
    $taskWithReviewer = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $workOrder->id,
        'project_id' => $project->id,
        'created_by_id' => $this->user->id,
        'reviewer_id' => $explicitReviewer->id,
    ]);

    // Task without explicit reviewer - should fall back to accountable
    $taskWithoutReviewer = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $workOrder->id,
        'project_id' => $project->id,
        'created_by_id' => $this->user->id,
        'reviewer_id' => null,
    ]);

    $reviewerForTaskWithExplicit = $this->resolver->resolve($taskWithReviewer);
    $reviewerForTaskWithoutExplicit = $this->resolver->resolve($taskWithoutReviewer);

    expect($reviewerForTaskWithExplicit->id)->toBe($explicitReviewer->id);
    expect($reviewerForTaskWithoutExplicit->id)->toBe($accountableUser->id);
});
