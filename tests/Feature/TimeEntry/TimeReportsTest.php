<?php

declare(strict_types=1);

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
        'estimated_hours' => 10,
    ]);
    $this->task = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'project_id' => $this->project->id,
        'estimated_hours' => 5,
    ]);
});

test('reports page loads with default By User tab', function () {
    $response = $this->actingAs($this->user)->get('/reports/time');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('reports/time/index')
        ->has('byUserData')
        ->has('filters')
    );
});

test('By User tab shows user hours by date', function () {
    TimeEntry::factory()->manual()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'task_id' => $this->task->id,
        'hours' => 2.5,
        'date' => '2026-01-13',
    ]);
    TimeEntry::factory()->manual()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'task_id' => $this->task->id,
        'hours' => 3.0,
        'date' => '2026-01-14',
    ]);

    $response = $this->actingAs($this->user)->get('/reports/time?date_from=2026-01-13&date_to=2026-01-14');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->has('byUserData', 1)
        ->where('byUserData.0.user_name', $this->user->name)
        ->where('byUserData.0.total_hours', fn ($value) => (float) $value === 5.5)
    );
});

test('By Project tab shows hierarchical project data', function () {
    TimeEntry::factory()->manual()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'task_id' => $this->task->id,
        'hours' => 4.0,
        'date' => now()->toDateString(),
    ]);

    $response = $this->actingAs($this->user)->get('/reports/time/by-project');

    $response->assertStatus(200);
    $response->assertJson([
        'data' => [
            [
                'id' => $this->project->id,
                'name' => $this->project->name,
            ],
        ],
    ]);
});

test('Actual vs Estimated tab shows variance data', function () {
    $this->task->update(['estimated_hours' => 5, 'actual_hours' => 6]);

    $response = $this->actingAs($this->user)->get('/reports/time/actual-vs-estimated');

    $response->assertStatus(200);
    $response->assertJson([
        'data' => [
            [
                'id' => $this->task->id,
                'name' => $this->task->title,
                'estimated_hours' => 5.0,
                'actual_hours' => 6.0,
                'variance' => 1.0,
                'variance_percent' => 20.0,
            ],
        ],
    ]);
});

test('date range filter applies to reports', function () {
    TimeEntry::factory()->manual()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'task_id' => $this->task->id,
        'hours' => 2.0,
        'date' => '2026-01-10',
    ]);
    TimeEntry::factory()->manual()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'task_id' => $this->task->id,
        'hours' => 3.0,
        'date' => '2026-01-20',
    ]);

    $response = $this->actingAs($this->user)->get('/reports/time?date_from=2026-01-15&date_to=2026-01-25');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->has('byUserData', 1)
        ->where('byUserData.0.total_hours', fn ($value) => (float) $value === 3.0)
    );
});

test('reports respect team context', function () {
    $otherUser = User::factory()->create();
    $otherTeam = $otherUser->createTeam(['name' => 'Other Team']);
    $otherUser->current_team_id = $otherTeam->id;
    $otherUser->save();

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
        'project_id' => $otherProject->id,
    ]);

    TimeEntry::factory()->manual()->create([
        'team_id' => $otherTeam->id,
        'user_id' => $otherUser->id,
        'task_id' => $otherTask->id,
        'hours' => 10.0,
        'date' => now()->toDateString(),
    ]);

    $response = $this->actingAs($this->user)->get('/reports/time');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->has('byUserData', 0)
    );
});
