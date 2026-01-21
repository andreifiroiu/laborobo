<?php

declare(strict_types=1);

use App\Models\Party;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\UserPreference;
use App\Models\WorkOrder;

/**
 * Strategic edge case tests for My Work feature.
 * These tests fill coverage gaps from Task Groups 1-4.
 *
 * @property User $user
 * @property \App\Models\Team $team
 * @property User $otherUser
 * @property Party $party
 */
beforeEach(function () {
    $this->user = User::factory()->create();
    $this->team = $this->user->createTeam(['name' => 'Test Team']);
    $this->user->current_team_id = $this->team->id;
    $this->user->save();

    $this->otherUser = User::factory()->create();
    $this->otherUser->current_team_id = $this->team->id;
    $this->otherUser->save();

    $this->party = Party::factory()->create(['team_id' => $this->team->id]);
});

test('user with multiple RACI roles on same project returns all roles', function () {
    $project = Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $this->party->id,
        'owner_id' => $this->otherUser->id,
        'accountable_id' => $this->user->id,
        'responsible_id' => $this->user->id,
        'consulted_ids' => [$this->user->id],
    ]);

    $roles = $project->getUserRaciRoles($this->user->id);

    expect($roles)->toBeArray()
        ->and($roles)->toContain('accountable', 'responsible', 'consulted')
        ->and($roles)->toHaveCount(3);
});

test('user with multiple RACI roles on same work order returns all roles', function () {
    $project = Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $this->party->id,
        'owner_id' => $this->otherUser->id,
    ]);

    $workOrder = WorkOrder::factory()->active()->create([
        'team_id' => $this->team->id,
        'project_id' => $project->id,
        'created_by_id' => $this->otherUser->id,
        'accountable_id' => $this->user->id,
        'responsible_id' => $this->user->id,
        'informed_ids' => [$this->user->id],
    ]);

    $roles = $workOrder->getUserRaciRoles($this->user->id);

    expect($roles)->toBeArray()
        ->and($roles)->toContain('accountable', 'responsible', 'informed')
        ->and($roles)->toHaveCount(3);
});

test('my work data returns empty state when user has no RACI roles or assignments', function () {
    // Create projects and work orders that do NOT involve the user
    Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $this->party->id,
        'owner_id' => $this->otherUser->id,
        'accountable_id' => $this->otherUser->id,
        'responsible_id' => $this->otherUser->id,
    ]);

    $project = Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $this->party->id,
        'owner_id' => $this->otherUser->id,
    ]);

    $workOrder = WorkOrder::factory()->active()->create([
        'team_id' => $this->team->id,
        'project_id' => $project->id,
        'created_by_id' => $this->otherUser->id,
        'accountable_id' => $this->otherUser->id,
    ]);

    Task::factory()->todo()->create([
        'team_id' => $this->team->id,
        'project_id' => $project->id,
        'work_order_id' => $workOrder->id,
        'assigned_to_id' => $this->otherUser->id,
        'created_by_id' => $this->otherUser->id,
    ]);

    UserPreference::set($this->user, 'work_view', 'my_work');

    $response = $this->actingAs($this->user)->get('/work');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('work/index')
        ->has('myWorkData.projects', 0)
        ->has('myWorkData.workOrders', 0)
        ->has('myWorkData.tasks', 0)
    );
});

test('my work metrics correctly count user with roles across multiple item types', function () {
    // User is accountable on 2 projects
    Project::factory()->count(2)->create([
        'team_id' => $this->team->id,
        'party_id' => $this->party->id,
        'owner_id' => $this->otherUser->id,
        'accountable_id' => $this->user->id,
    ]);

    $project = Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $this->party->id,
        'owner_id' => $this->otherUser->id,
        'responsible_id' => $this->user->id,
    ]);

    // User is accountable on 1 work order and responsible on another
    WorkOrder::factory()->active()->create([
        'team_id' => $this->team->id,
        'project_id' => $project->id,
        'created_by_id' => $this->otherUser->id,
        'accountable_id' => $this->user->id,
    ]);

    $responsibleWorkOrder = WorkOrder::factory()->active()->create([
        'team_id' => $this->team->id,
        'project_id' => $project->id,
        'created_by_id' => $this->otherUser->id,
        'accountable_id' => $this->otherUser->id,
        'responsible_id' => $this->user->id,
    ]);

    // User has 3 tasks assigned (1 done, 2 incomplete)
    Task::factory()->todo()->create([
        'team_id' => $this->team->id,
        'project_id' => $project->id,
        'work_order_id' => $responsibleWorkOrder->id,
        'assigned_to_id' => $this->user->id,
        'created_by_id' => $this->otherUser->id,
    ]);

    Task::factory()->inProgress()->create([
        'team_id' => $this->team->id,
        'project_id' => $project->id,
        'work_order_id' => $responsibleWorkOrder->id,
        'assigned_to_id' => $this->user->id,
        'created_by_id' => $this->otherUser->id,
    ]);

    Task::factory()->done()->create([
        'team_id' => $this->team->id,
        'project_id' => $project->id,
        'work_order_id' => $responsibleWorkOrder->id,
        'assigned_to_id' => $this->user->id,
        'created_by_id' => $this->otherUser->id,
    ]);

    UserPreference::set($this->user, 'work_view', 'my_work');

    $response = $this->actingAs($this->user)->get('/work');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('work/index')
        ->where('myWorkMetrics.accountableCount', 3)  // 2 projects + 1 work order
        ->where('myWorkMetrics.responsibleCount', 2)  // 1 project + 1 work order
        ->where('myWorkMetrics.assignedTasksCount', 2)  // Only incomplete tasks
    );
});

test('project scope returns project where user is in both consulted and informed arrays', function () {
    $project = Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $this->party->id,
        'owner_id' => $this->otherUser->id,
        'accountable_id' => $this->otherUser->id,
        'consulted_ids' => [$this->user->id, $this->otherUser->id],
        'informed_ids' => [$this->user->id],
    ]);

    // Test with excludeInformed = false to get the project
    $results = Project::whereUserHasRaciRole($this->user->id, excludeInformed: false)->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($project->id);

    // The user should have both consulted and informed roles
    $roles = $project->getUserRaciRoles($this->user->id);
    expect($roles)->toContain('consulted', 'informed')
        ->and($roles)->toHaveCount(2);
});

test('work order scope with user only in informed role is excluded by default', function () {
    $project = Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $this->party->id,
        'owner_id' => $this->otherUser->id,
    ]);

    // Work order where user is ONLY informed
    $informedOnlyWorkOrder = WorkOrder::factory()->active()->create([
        'team_id' => $this->team->id,
        'project_id' => $project->id,
        'created_by_id' => $this->otherUser->id,
        'accountable_id' => $this->otherUser->id,
        'responsible_id' => $this->otherUser->id,
        'informed_ids' => [$this->user->id],
    ]);

    // Work order where user has a primary role
    $accountableWorkOrder = WorkOrder::factory()->active()->create([
        'team_id' => $this->team->id,
        'project_id' => $project->id,
        'created_by_id' => $this->otherUser->id,
        'accountable_id' => $this->user->id,
    ]);

    // Default excludeInformed = true should only return accountable work order
    $resultsExcluded = WorkOrder::whereUserHasRaciRole($this->user->id)->get();

    expect($resultsExcluded)->toHaveCount(1)
        ->and($resultsExcluded->first()->id)->toBe($accountableWorkOrder->id);

    // With excludeInformed = false should return both
    $resultsIncluded = WorkOrder::whereUserHasRaciRole($this->user->id, excludeInformed: false)->get();

    expect($resultsIncluded)->toHaveCount(2);
});
