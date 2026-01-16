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

test('index returns paginated entries for current user', function () {
    TimeEntry::factory()->manual()->count(30)->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'task_id' => $this->task->id,
    ]);

    $response = $this->actingAs($this->user)->get('/work/time-entries');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('work/time-entries/index')
        ->has('entries.data', 25)
        ->has('entries.links')
    );
});

test('index filters by date range', function () {
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
        'date' => '2026-01-15',
    ]);
    TimeEntry::factory()->manual()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'task_id' => $this->task->id,
        'date' => '2026-01-20',
    ]);

    $response = $this->actingAs($this->user)->get('/work/time-entries?date_from=2026-01-10&date_to=2026-01-15');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->has('entries.data', 2)
    );
});

test('index filters by billable status', function () {
    TimeEntry::factory()->manual()->billable()->count(3)->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'task_id' => $this->task->id,
    ]);
    TimeEntry::factory()->manual()->nonBillable()->count(2)->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'task_id' => $this->task->id,
    ]);

    $billableResponse = $this->actingAs($this->user)->get('/work/time-entries?billable=1');
    $billableResponse->assertStatus(200);
    $billableResponse->assertInertia(fn ($page) => $page
        ->has('entries.data', 3)
    );

    $nonBillableResponse = $this->actingAs($this->user)->get('/work/time-entries?billable=0');
    $nonBillableResponse->assertStatus(200);
    $nonBillableResponse->assertInertia(fn ($page) => $page
        ->has('entries.data', 2)
    );
});

test('store creates entry with is_billable field', function () {
    $response = $this->actingAs($this->user)->post('/work/time-entries', [
        'taskId' => $this->task->id,
        'hours' => 2.5,
        'date' => '2026-01-16',
        'note' => 'Test entry',
        'is_billable' => false,
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('time_entries', [
        'task_id' => $this->task->id,
        'user_id' => $this->user->id,
        'hours' => 2.5,
        'is_billable' => false,
        'note' => 'Test entry',
    ]);
});

test('update modifies hours, date, note, and is_billable', function () {
    $timeEntry = TimeEntry::factory()->manual()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'task_id' => $this->task->id,
        'hours' => 1.0,
        'date' => '2026-01-10',
        'note' => 'Original note',
        'is_billable' => true,
    ]);
    $this->task->update(['actual_hours' => 1.0]);

    $response = $this->actingAs($this->user)->patch("/work/time-entries/{$timeEntry->id}", [
        'hours' => 3.5,
        'date' => '2026-01-15',
        'note' => 'Updated note',
        'is_billable' => false,
    ]);

    $response->assertRedirect();

    $timeEntry->refresh();
    expect((float) $timeEntry->hours)->toBe(3.5)
        ->and($timeEntry->date->toDateString())->toBe('2026-01-15')
        ->and($timeEntry->note)->toBe('Updated note')
        ->and($timeEntry->is_billable)->toBeFalse();

    $this->task->refresh();
    expect((float) $this->task->actual_hours)->toBe(3.5);
});

test('destroy soft deletes and recalculates task hours', function () {
    $timeEntry = TimeEntry::factory()->manual()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'task_id' => $this->task->id,
        'hours' => 2.0,
    ]);
    $this->task->update(['actual_hours' => 2.0]);

    $response = $this->actingAs($this->user)->delete("/work/time-entries/{$timeEntry->id}");

    $response->assertRedirect();

    $this->assertSoftDeleted('time_entries', ['id' => $timeEntry->id]);

    $this->task->refresh();
    expect((float) $this->task->actual_hours)->toBe(0.0);
});

test('stopById stops a specific timer by ID', function () {
    $runningEntry = TimeEntry::factory()->running()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'task_id' => $this->task->id,
    ]);

    $response = $this->actingAs($this->user)->post("/work/time-entries/{$runningEntry->id}/stop");

    $response->assertRedirect();

    $runningEntry->refresh();
    expect($runningEntry->stopped_at)->not->toBeNull()
        ->and($runningEntry->hours)->toBeGreaterThanOrEqual(0);
});

test('stopById fails for timer not owned by user', function () {
    $otherUser = User::factory()->create();
    $otherUser->current_team_id = $this->team->id;
    $otherUser->save();

    $runningEntry = TimeEntry::factory()->running()->create([
        'team_id' => $this->team->id,
        'user_id' => $otherUser->id,
        'task_id' => $this->task->id,
    ]);

    $response = $this->actingAs($this->user)->post("/work/time-entries/{$runningEntry->id}/stop");

    $response->assertStatus(403);

    $runningEntry->refresh();
    expect($runningEntry->stopped_at)->toBeNull();
});
