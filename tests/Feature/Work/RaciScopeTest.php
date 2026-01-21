<?php

declare(strict_types=1);

use App\Enums\WorkOrderStatus;
use App\Models\Party;
use App\Models\Project;
use App\Models\User;
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

test('scopeWhereUserHasRaciRole on Project returns projects where user is accountable', function () {
    $accountableProject = Project::factory()->create([
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
    ]);

    $results = Project::whereUserHasRaciRole($this->user->id)->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($accountableProject->id);
});

test('scopeWhereUserHasRaciRole on Project returns projects where user is in consulted or informed arrays', function () {
    $consultedProject = Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $this->party->id,
        'owner_id' => $this->otherUser->id,
        'accountable_id' => $this->otherUser->id,
        'consulted_ids' => [$this->user->id],
    ]);

    $informedProject = Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $this->party->id,
        'owner_id' => $this->otherUser->id,
        'accountable_id' => $this->otherUser->id,
        'informed_ids' => [$this->user->id],
    ]);

    Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $this->party->id,
        'owner_id' => $this->otherUser->id,
        'accountable_id' => $this->otherUser->id,
    ]);

    $results = Project::whereUserHasRaciRole($this->user->id, excludeInformed: false)->get();

    expect($results)->toHaveCount(2)
        ->and($results->pluck('id')->toArray())->toContain($consultedProject->id, $informedProject->id);
});

test('scopeWhereUserHasRaciRole on WorkOrder returns work orders where user has any RACI role', function () {
    $project = Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $this->party->id,
        'owner_id' => $this->otherUser->id,
    ]);

    $responsibleWorkOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $project->id,
        'created_by_id' => $this->otherUser->id,
        'accountable_id' => $this->otherUser->id,
        'responsible_id' => $this->user->id,
    ]);

    WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $project->id,
        'created_by_id' => $this->otherUser->id,
        'accountable_id' => $this->otherUser->id,
        'responsible_id' => $this->otherUser->id,
    ]);

    $results = WorkOrder::whereUserHasRaciRole($this->user->id)->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($responsibleWorkOrder->id);
});

test('scopeWhereUserHasRaciRole excludes informed items when excludeInformed flag is true', function () {
    $project = Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $this->party->id,
        'owner_id' => $this->otherUser->id,
    ]);

    $accountableWorkOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $project->id,
        'created_by_id' => $this->otherUser->id,
        'accountable_id' => $this->user->id,
    ]);

    $informedWorkOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $project->id,
        'created_by_id' => $this->otherUser->id,
        'accountable_id' => $this->otherUser->id,
        'informed_ids' => [$this->user->id],
    ]);

    $resultsExcludingInformed = WorkOrder::whereUserHasRaciRole($this->user->id, excludeInformed: true)->get();

    expect($resultsExcludingInformed)->toHaveCount(1)
        ->and($resultsExcludingInformed->first()->id)->toBe($accountableWorkOrder->id);

    $resultsIncludingInformed = WorkOrder::whereUserHasRaciRole($this->user->id, excludeInformed: false)->get();

    expect($resultsIncludingInformed)->toHaveCount(2)
        ->and($resultsIncludingInformed->pluck('id')->toArray())->toContain($accountableWorkOrder->id, $informedWorkOrder->id);
});

test('scopeInReviewWhereUserIsAccountable returns work orders in review status where user is accountable', function () {
    $project = Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $this->party->id,
        'owner_id' => $this->otherUser->id,
    ]);

    $inReviewAccountable = WorkOrder::factory()->inReview()->create([
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

    WorkOrder::factory()->active()->create([
        'team_id' => $this->team->id,
        'project_id' => $project->id,
        'created_by_id' => $this->otherUser->id,
        'accountable_id' => $this->user->id,
    ]);

    $results = WorkOrder::inReviewWhereUserIsAccountable($this->user->id)->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($inReviewAccountable->id)
        ->and($results->first()->status)->toBe(WorkOrderStatus::InReview);
});

test('getUserRaciRoles returns correct roles for a given user', function () {
    $project = Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $this->party->id,
        'owner_id' => $this->otherUser->id,
        'accountable_id' => $this->user->id,
        'responsible_id' => $this->user->id,
        'consulted_ids' => [$this->otherUser->id],
        'informed_ids' => [$this->user->id],
    ]);

    $roles = $project->getUserRaciRoles($this->user->id);

    expect($roles)->toBeArray()
        ->and($roles)->toContain('accountable', 'responsible', 'informed')
        ->and($roles)->not->toContain('consulted');

    $otherUserRoles = $project->getUserRaciRoles($this->otherUser->id);

    expect($otherUserRoles)->toBeArray()
        ->and($otherUserRoles)->toContain('consulted')
        ->and($otherUserRoles)->not->toContain('accountable', 'responsible', 'informed');
});
