<?php

declare(strict_types=1);

use App\Models\Party;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\UserPreference;
use App\Models\WorkOrder;

/**
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

test('my work data returns projects with user RACI roles', function () {
    Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $this->party->id,
        'owner_id' => $this->otherUser->id,
        'accountable_id' => $this->user->id,
        'responsible_id' => $this->otherUser->id,
    ]);

    Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $this->party->id,
        'owner_id' => $this->otherUser->id,
        'accountable_id' => $this->otherUser->id,
        'responsible_id' => $this->user->id,
    ]);

    Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $this->party->id,
        'owner_id' => $this->otherUser->id,
        'accountable_id' => $this->otherUser->id,
        'responsible_id' => $this->otherUser->id,
    ]);

    UserPreference::set($this->user, 'work_view', 'my_work');

    $response = $this->actingAs($this->user)->get('/work');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('work/index')
        ->has('myWorkData.projects', 2)
        ->has('myWorkData.projects.0.userRaciRoles')
    );
});

test('my work data returns work orders with user RACI roles', function () {
    $project = Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $this->party->id,
        'owner_id' => $this->otherUser->id,
    ]);

    WorkOrder::factory()->active()->create([
        'team_id' => $this->team->id,
        'project_id' => $project->id,
        'created_by_id' => $this->otherUser->id,
        'accountable_id' => $this->user->id,
        'responsible_id' => $this->otherUser->id,
    ]);

    WorkOrder::factory()->active()->create([
        'team_id' => $this->team->id,
        'project_id' => $project->id,
        'created_by_id' => $this->otherUser->id,
        'accountable_id' => $this->otherUser->id,
        'consulted_ids' => [$this->user->id],
    ]);

    WorkOrder::factory()->active()->create([
        'team_id' => $this->team->id,
        'project_id' => $project->id,
        'created_by_id' => $this->otherUser->id,
        'accountable_id' => $this->otherUser->id,
        'responsible_id' => $this->otherUser->id,
    ]);

    UserPreference::set($this->user, 'work_view', 'my_work');

    $response = $this->actingAs($this->user)->get('/work');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('work/index')
        ->has('myWorkData.workOrders', 2)
        ->has('myWorkData.workOrders.0.userRaciRoles')
    );
});

test('my work data returns tasks assigned to user', function () {
    $project = Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $this->party->id,
        'owner_id' => $this->otherUser->id,
    ]);

    $workOrder = WorkOrder::factory()->active()->create([
        'team_id' => $this->team->id,
        'project_id' => $project->id,
        'created_by_id' => $this->otherUser->id,
    ]);

    $assignedTask = Task::factory()->todo()->create([
        'team_id' => $this->team->id,
        'project_id' => $project->id,
        'work_order_id' => $workOrder->id,
        'assigned_to_id' => $this->user->id,
        'created_by_id' => $this->otherUser->id,
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
        ->has('myWorkData.tasks', 1)
        ->where('myWorkData.tasks.0.id', (string) $assignedTask->id)
    );
});

test('my work metrics calculations are correct', function () {
    $project = Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $this->party->id,
        'owner_id' => $this->otherUser->id,
        'accountable_id' => $this->user->id,
    ]);

    Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $this->party->id,
        'owner_id' => $this->otherUser->id,
        'accountable_id' => $this->user->id,
    ]);

    Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $this->party->id,
        'owner_id' => $this->otherUser->id,
        'responsible_id' => $this->user->id,
    ]);

    $workOrder = WorkOrder::factory()->active()->create([
        'team_id' => $this->team->id,
        'project_id' => $project->id,
        'created_by_id' => $this->otherUser->id,
        'accountable_id' => $this->user->id,
    ]);

    WorkOrder::factory()->inReview()->create([
        'team_id' => $this->team->id,
        'project_id' => $project->id,
        'created_by_id' => $this->otherUser->id,
        'accountable_id' => $this->user->id,
    ]);

    WorkOrder::factory()->inReview()->create([
        'team_id' => $this->team->id,
        'project_id' => $project->id,
        'created_by_id' => $this->otherUser->id,
        'accountable_id' => $this->otherUser->id,
    ]);

    Task::factory()->todo()->create([
        'team_id' => $this->team->id,
        'project_id' => $project->id,
        'work_order_id' => $workOrder->id,
        'assigned_to_id' => $this->user->id,
        'created_by_id' => $this->otherUser->id,
    ]);

    Task::factory()->inProgress()->create([
        'team_id' => $this->team->id,
        'project_id' => $project->id,
        'work_order_id' => $workOrder->id,
        'assigned_to_id' => $this->user->id,
        'created_by_id' => $this->otherUser->id,
    ]);

    Task::factory()->done()->create([
        'team_id' => $this->team->id,
        'project_id' => $project->id,
        'work_order_id' => $workOrder->id,
        'assigned_to_id' => $this->user->id,
        'created_by_id' => $this->otherUser->id,
    ]);

    UserPreference::set($this->user, 'work_view', 'my_work');

    $response = $this->actingAs($this->user)->get('/work');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('work/index')
        ->has('myWorkMetrics')
        ->where('myWorkMetrics.accountableCount', 4)
        ->where('myWorkMetrics.responsibleCount', 1)
        ->where('myWorkMetrics.awaitingReviewCount', 1)
        ->where('myWorkMetrics.assignedTasksCount', 2)
    );
});

test('show informed toggle affects returned my work data', function () {
    Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $this->party->id,
        'owner_id' => $this->otherUser->id,
        'accountable_id' => $this->user->id,
    ]);

    Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $this->party->id,
        'owner_id' => $this->otherUser->id,
        'accountable_id' => $this->otherUser->id,
        'informed_ids' => [$this->user->id],
    ]);

    UserPreference::set($this->user, 'work_view', 'my_work');
    UserPreference::set($this->user, 'my_work_show_informed', 'false');

    $response = $this->actingAs($this->user)->get('/work');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('work/index')
        ->has('myWorkData.projects', 1)
    );

    UserPreference::set($this->user, 'my_work_show_informed', 'true');

    $responseWithInformed = $this->actingAs($this->user)->get('/work');

    $responseWithInformed->assertStatus(200);
    $responseWithInformed->assertInertia(fn ($page) => $page
        ->component('work/index')
        ->has('myWorkData.projects', 2)
    );
});
