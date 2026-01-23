<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\ProjectUserRate;
use App\Models\Team;
use App\Models\User;
use App\Models\UserRate;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->team = $this->user->createTeam(['name' => 'Test Team']);
    $this->user->current_team_id = $this->team->id;
    $this->user->save();
});

test('user rate can be retrieved with team scoping', function () {
    $rate = UserRate::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'internal_rate' => 50.00,
        'billing_rate' => 100.00,
        'effective_date' => now()->subMonth(),
    ]);

    $otherTeam = Team::factory()->create();
    UserRate::factory()->create([
        'team_id' => $otherTeam->id,
        'user_id' => $this->user->id,
        'internal_rate' => 75.00,
        'billing_rate' => 150.00,
        'effective_date' => now()->subMonth(),
    ]);

    $rates = UserRate::forTeam($this->team->id)->get();

    expect($rates)->toHaveCount(1);
    expect($rates->first()->internal_rate)->toBe('50.00');
    expect($rates->first()->billing_rate)->toBe('100.00');
});

test('user rate effective_date filtering returns rate valid at specific date', function () {
    UserRate::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'internal_rate' => 40.00,
        'billing_rate' => 80.00,
        'effective_date' => now()->subMonths(6),
    ]);

    UserRate::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'internal_rate' => 50.00,
        'billing_rate' => 100.00,
        'effective_date' => now()->subMonth(),
    ]);

    UserRate::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'internal_rate' => 60.00,
        'billing_rate' => 120.00,
        'effective_date' => now()->addMonth(),
    ]);

    $rateAtPast = UserRate::forTeam($this->team->id)
        ->forUser($this->user->id)
        ->effectiveAt(now()->subMonths(3))
        ->first();

    expect($rateAtPast->internal_rate)->toBe('40.00');

    $rateAtNow = UserRate::forTeam($this->team->id)
        ->forUser($this->user->id)
        ->effectiveAt(now())
        ->first();

    expect($rateAtNow->internal_rate)->toBe('50.00');
});

test('project-specific rate override takes precedence over team rate', function () {
    $project = Project::factory()->create([
        'team_id' => $this->team->id,
        'owner_id' => $this->user->id,
    ]);

    UserRate::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'internal_rate' => 50.00,
        'billing_rate' => 100.00,
        'effective_date' => now()->subMonth(),
    ]);

    ProjectUserRate::factory()->create([
        'project_id' => $project->id,
        'user_id' => $this->user->id,
        'internal_rate' => 75.00,
        'billing_rate' => 150.00,
        'effective_date' => now()->subMonth(),
    ]);

    $projectRate = ProjectUserRate::forProject($project->id)
        ->forUser($this->user->id)
        ->effectiveAt(now())
        ->first();

    $teamRate = UserRate::forTeam($this->team->id)
        ->forUser($this->user->id)
        ->effectiveAt(now())
        ->first();

    expect($projectRate)->not->toBeNull();
    expect($projectRate->internal_rate)->toBe('75.00');
    expect($projectRate->billing_rate)->toBe('150.00');
    expect($teamRate->internal_rate)->toBe('50.00');
});

test('user rate validates positive decimal rates', function () {
    $rate = UserRate::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'internal_rate' => 123.45,
        'billing_rate' => 234.56,
        'effective_date' => now(),
    ]);

    expect($rate->internal_rate)->toBe('123.45');
    expect($rate->billing_rate)->toBe('234.56');
    expect($rate->effective_date->toDateString())->toBe(now()->toDateString());
});

test('user has many rates relationship', function () {
    UserRate::factory()->count(3)->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
    ]);

    expect($this->user->rates)->toHaveCount(3);
    expect($this->user->rates->first())->toBeInstanceOf(UserRate::class);
});

test('user has many project rates relationship', function () {
    $project = Project::factory()->create([
        'team_id' => $this->team->id,
        'owner_id' => $this->user->id,
    ]);

    ProjectUserRate::factory()->count(2)->create([
        'project_id' => $project->id,
        'user_id' => $this->user->id,
    ]);

    expect($this->user->projectRates)->toHaveCount(2);
    expect($this->user->projectRates->first())->toBeInstanceOf(ProjectUserRate::class);
});
