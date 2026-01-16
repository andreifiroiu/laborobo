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
    ]);
});

test('scopeRunningForUser returns entries with started_at and null stopped_at', function () {
    $runningEntry = TimeEntry::factory()->running()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'task_id' => $this->task->id,
    ]);

    $results = TimeEntry::runningForUser($this->user->id)->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($runningEntry->id)
        ->and($results->first()->started_at)->not->toBeNull()
        ->and($results->first()->stopped_at)->toBeNull();
});

test('scopeRunningForUser excludes entries with stopped_at set', function () {
    TimeEntry::factory()->running()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'task_id' => $this->task->id,
    ]);

    TimeEntry::factory()->timer()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'task_id' => $this->task->id,
    ]);

    $results = TimeEntry::runningForUser($this->user->id)->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->stopped_at)->toBeNull();
});

test('scopeRunningForUser filters by user_id correctly', function () {
    $otherUser = User::factory()->create();
    $otherUser->current_team_id = $this->team->id;
    $otherUser->save();

    $currentUserEntry = TimeEntry::factory()->running()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'task_id' => $this->task->id,
    ]);

    TimeEntry::factory()->running()->create([
        'team_id' => $this->team->id,
        'user_id' => $otherUser->id,
        'task_id' => $this->task->id,
    ]);

    $results = TimeEntry::runningForUser($this->user->id)->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($currentUserEntry->id)
        ->and($results->first()->user_id)->toBe($this->user->id);
});

test('scopeRunningForUser returns empty when no active timers', function () {
    TimeEntry::factory()->timer()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'task_id' => $this->task->id,
    ]);

    TimeEntry::factory()->manual()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'task_id' => $this->task->id,
    ]);

    $results = TimeEntry::runningForUser($this->user->id)->get();

    expect($results)->toHaveCount(0);
});
