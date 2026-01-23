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

    // Create a team member
    $this->teamMember = User::factory()->create();
    $this->team->addUser($this->teamMember, 'member');
});

test('user can list team member rates', function () {
    // Create rates for team members
    UserRate::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'internal_rate' => 50.00,
        'billing_rate' => 100.00,
        'effective_date' => now()->subMonth(),
    ]);

    UserRate::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->teamMember->id,
        'internal_rate' => 40.00,
        'billing_rate' => 80.00,
        'effective_date' => now()->subMonth(),
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('settings.rates.index'));

    $response->assertStatus(200);
    // Note: Using shouldExist: false because the frontend page component
    // will be implemented in Task Group 7
    $response->assertInertia(fn ($page) => $page
        ->component('account/settings/rates', shouldExist: false)
        ->has('rates', 2)
    );
});

test('user can create a new rate for a team member', function () {
    $response = $this->actingAs($this->user)
        ->post(route('settings.rates.store'), [
            'user_id' => $this->teamMember->id,
            'internal_rate' => 55.50,
            'billing_rate' => 110.75,
            'effective_date' => now()->format('Y-m-d'),
        ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    $this->assertDatabaseHas('user_rates', [
        'team_id' => $this->team->id,
        'user_id' => $this->teamMember->id,
        'internal_rate' => 55.50,
        'billing_rate' => 110.75,
    ]);
});

test('user can create multiple rates for history tracking', function () {
    // Create an initial rate
    $oldRate = UserRate::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->teamMember->id,
        'internal_rate' => 50.00,
        'billing_rate' => 100.00,
        'effective_date' => now()->subMonth(),
    ]);

    // Create a new rate (rates are immutable, so we create a new one instead of updating)
    $response = $this->actingAs($this->user)
        ->post(route('settings.rates.store'), [
            'user_id' => $this->teamMember->id,
            'internal_rate' => 60.00,
            'billing_rate' => 120.00,
            'effective_date' => now()->format('Y-m-d'),
        ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    // Both rates should exist (for history tracking)
    $this->assertDatabaseHas('user_rates', [
        'id' => $oldRate->id,
        'internal_rate' => 50.00,
        'billing_rate' => 100.00,
    ]);

    $this->assertDatabaseHas('user_rates', [
        'team_id' => $this->team->id,
        'user_id' => $this->teamMember->id,
        'internal_rate' => 60.00,
        'billing_rate' => 120.00,
    ]);

    // User should now have 2 rates
    expect(UserRate::where('user_id', $this->teamMember->id)->count())->toBe(2);
});

test('user can create a project-specific rate override', function () {
    $project = Project::factory()->create([
        'team_id' => $this->team->id,
        'owner_id' => $this->user->id,
    ]);

    $response = $this->actingAs($this->user)
        ->post(route('projects.rates.store', $project), [
            'user_id' => $this->teamMember->id,
            'internal_rate' => 75.00,
            'billing_rate' => 150.00,
            'effective_date' => now()->format('Y-m-d'),
        ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    $this->assertDatabaseHas('project_user_rates', [
        'project_id' => $project->id,
        'user_id' => $this->teamMember->id,
        'internal_rate' => 75.00,
        'billing_rate' => 150.00,
    ]);
});

test('rate validation requires positive numbers with max 2 decimal places', function () {
    // Test negative internal rate
    $response = $this->actingAs($this->user)
        ->post(route('settings.rates.store'), [
            'user_id' => $this->teamMember->id,
            'internal_rate' => -10.00,
            'billing_rate' => 100.00,
            'effective_date' => now()->format('Y-m-d'),
        ]);

    $response->assertSessionHasErrors(['internal_rate']);

    // Test too many decimal places
    $response = $this->actingAs($this->user)
        ->post(route('settings.rates.store'), [
            'user_id' => $this->teamMember->id,
            'internal_rate' => 50.123,
            'billing_rate' => 100.00,
            'effective_date' => now()->format('Y-m-d'),
        ]);

    $response->assertSessionHasErrors(['internal_rate']);

    // Test zero rate (should be valid - some team members may be unpaid)
    $response = $this->actingAs($this->user)
        ->post(route('settings.rates.store'), [
            'user_id' => $this->teamMember->id,
            'internal_rate' => 0.00,
            'billing_rate' => 0.00,
            'effective_date' => now()->format('Y-m-d'),
        ]);

    $response->assertSessionHasNoErrors();
});

test('user can delete a project-specific rate override', function () {
    $project = Project::factory()->create([
        'team_id' => $this->team->id,
        'owner_id' => $this->user->id,
    ]);

    $rate = ProjectUserRate::factory()->create([
        'project_id' => $project->id,
        'user_id' => $this->teamMember->id,
        'internal_rate' => 75.00,
        'billing_rate' => 150.00,
        'effective_date' => now()->subMonth(),
    ]);

    $response = $this->actingAs($this->user)
        ->delete(route('projects.rates.destroy', [$project, $rate]));

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    $this->assertDatabaseMissing('project_user_rates', [
        'id' => $rate->id,
    ]);
});
