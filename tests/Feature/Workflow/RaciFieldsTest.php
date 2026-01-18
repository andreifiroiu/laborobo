<?php

declare(strict_types=1);

use App\Models\Party;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use App\Models\WorkOrder;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->team = $this->user->createTeam(['name' => 'Test Team']);
    $this->user->current_team_id = $this->team->id;
    $this->user->save();

    $this->party = Party::factory()->create(['team_id' => $this->team->id]);
});

test('Project requires accountable_id field', function () {
    $accountableUser = User::factory()->create();

    $project = Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $this->party->id,
        'owner_id' => $this->user->id,
        'accountable_id' => $accountableUser->id,
    ]);

    expect($project->accountable_id)->toBe($accountableUser->id);
    expect($project->accountable)->toBeInstanceOf(User::class);
    expect($project->accountable->id)->toBe($accountableUser->id);
});

test('WorkOrder requires accountable_id field', function () {
    $project = Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $this->party->id,
        'owner_id' => $this->user->id,
        'accountable_id' => $this->user->id,
    ]);

    $accountableUser = User::factory()->create();
    $responsibleUser = User::factory()->create();
    $reviewerUser = User::factory()->create();

    $workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $project->id,
        'created_by_id' => $this->user->id,
        'accountable_id' => $accountableUser->id,
        'responsible_id' => $responsibleUser->id,
        'reviewer_id' => $reviewerUser->id,
    ]);

    expect($workOrder->accountable_id)->toBe($accountableUser->id);
    expect($workOrder->accountable)->toBeInstanceOf(User::class);
    expect($workOrder->accountable->id)->toBe($accountableUser->id);
    expect($workOrder->reviewer)->toBeInstanceOf(User::class);
    expect($workOrder->reviewer->id)->toBe($reviewerUser->id);
});

test('Project consulted_ids and informed_ids are cast as arrays', function () {
    $consultedUsers = User::factory()->count(2)->create();
    $informedUsers = User::factory()->count(3)->create();

    $project = Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $this->party->id,
        'owner_id' => $this->user->id,
        'accountable_id' => $this->user->id,
        'consulted_ids' => $consultedUsers->pluck('id')->toArray(),
        'informed_ids' => $informedUsers->pluck('id')->toArray(),
    ]);

    $project->refresh();

    expect($project->consulted_ids)->toBeArray();
    expect($project->consulted_ids)->toHaveCount(2);
    expect($project->informed_ids)->toBeArray();
    expect($project->informed_ids)->toHaveCount(3);
});

test('WorkOrder consulted_ids and informed_ids are cast as arrays', function () {
    $project = Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $this->party->id,
        'owner_id' => $this->user->id,
        'accountable_id' => $this->user->id,
    ]);

    $consultedUsers = User::factory()->count(2)->create();
    $informedUsers = User::factory()->count(3)->create();

    $workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $project->id,
        'created_by_id' => $this->user->id,
        'accountable_id' => $this->user->id,
        'consulted_ids' => $consultedUsers->pluck('id')->toArray(),
        'informed_ids' => $informedUsers->pluck('id')->toArray(),
    ]);

    $workOrder->refresh();

    expect($workOrder->consulted_ids)->toBeArray();
    expect($workOrder->consulted_ids)->toHaveCount(2);
    expect($workOrder->informed_ids)->toBeArray();
    expect($workOrder->informed_ids)->toHaveCount(3);
});

test('Project RACI relationships resolve to User models', function () {
    $accountableUser = User::factory()->create();
    $responsibleUser = User::factory()->create();

    $project = Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $this->party->id,
        'owner_id' => $this->user->id,
        'accountable_id' => $accountableUser->id,
        'responsible_id' => $responsibleUser->id,
    ]);

    expect($project->accountable)->toBeInstanceOf(User::class);
    expect($project->accountable->id)->toBe($accountableUser->id);
    expect($project->responsible)->toBeInstanceOf(User::class);
    expect($project->responsible->id)->toBe($responsibleUser->id);
});

test('WorkOrder RACI relationships resolve to User models', function () {
    $project = Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $this->party->id,
        'owner_id' => $this->user->id,
        'accountable_id' => $this->user->id,
    ]);

    $accountableUser = User::factory()->create();
    $responsibleUser = User::factory()->create();
    $reviewerUser = User::factory()->create();

    $workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $project->id,
        'created_by_id' => $this->user->id,
        'accountable_id' => $accountableUser->id,
        'responsible_id' => $responsibleUser->id,
        'reviewer_id' => $reviewerUser->id,
    ]);

    expect($workOrder->accountable)->toBeInstanceOf(User::class);
    expect($workOrder->accountable->id)->toBe($accountableUser->id);
    expect($workOrder->responsible)->toBeInstanceOf(User::class);
    expect($workOrder->responsible->id)->toBe($responsibleUser->id);
    expect($workOrder->reviewer)->toBeInstanceOf(User::class);
    expect($workOrder->reviewer->id)->toBe($reviewerUser->id);
});
