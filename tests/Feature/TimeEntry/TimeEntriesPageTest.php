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
    ]);
    $this->task = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'project_id' => $this->project->id,
    ]);
});

test('page loads with user time entries including task and project data', function () {
    TimeEntry::factory()->manual()->count(3)->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'task_id' => $this->task->id,
    ]);

    $response = $this->actingAs($this->user)->get('/work/time-entries');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('work/time-entries/index')
        ->has('entries.data', 3)
        ->has('entries.data.0.task')
        ->has('entries.data.0.task.work_order')
        ->has('entries.data.0.task.work_order.project')
    );
});

test('date range filter works correctly', function () {
    TimeEntry::factory()->manual()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'task_id' => $this->task->id,
        'date' => '2026-01-05',
    ]);
    TimeEntry::factory()->manual()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'task_id' => $this->task->id,
        'date' => '2026-01-10',
    ]);
    TimeEntry::factory()->manual()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'task_id' => $this->task->id,
        'date' => '2026-01-20',
    ]);

    $response = $this->actingAs($this->user)->get('/work/time-entries?date_from=2026-01-08&date_to=2026-01-15');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->has('entries.data', 1)
        ->where('filters.date_from', '2026-01-08')
        ->where('filters.date_to', '2026-01-15')
    );
});

test('edit endpoint returns time entry with relationships', function () {
    $timeEntry = TimeEntry::factory()->manual()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'task_id' => $this->task->id,
        'hours' => 2.5,
        'note' => 'Test note',
    ]);

    $response = $this->actingAs($this->user)->get("/work/time-entries/{$timeEntry->id}");

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('work/time-entries/show')
        ->where('timeEntry.id', $timeEntry->id)
        ->where('timeEntry.hours', '2.50')
        ->where('timeEntry.note', 'Test note')
    );
});

test('delete removes entry and recalculates task hours', function () {
    $timeEntry = TimeEntry::factory()->manual()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'task_id' => $this->task->id,
        'hours' => 3.0,
    ]);
    $this->task->update(['actual_hours' => 3.0]);

    $response = $this->actingAs($this->user)->delete("/work/time-entries/{$timeEntry->id}");

    $response->assertRedirect();
    $this->assertSoftDeleted('time_entries', ['id' => $timeEntry->id]);

    $this->task->refresh();
    expect((float) $this->task->actual_hours)->toBe(0.0);
});
