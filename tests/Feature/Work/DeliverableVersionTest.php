<?php

declare(strict_types=1);

use App\Models\Deliverable;
use App\Models\DeliverableVersion;
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
    $this->project = Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $this->party->id,
        'owner_id' => $this->user->id,
    ]);
    $this->workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
    ]);
    $this->deliverable = Deliverable::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'project_id' => $this->project->id,
    ]);
});

test('deliverable version belongs to deliverable', function () {
    $version = DeliverableVersion::factory()->create([
        'deliverable_id' => $this->deliverable->id,
        'uploaded_by_id' => $this->user->id,
    ]);

    expect($version->deliverable)->toBeInstanceOf(Deliverable::class);
    expect($version->deliverable->id)->toBe($this->deliverable->id);
});

test('deliverable version belongs to user via uploaded_by relationship', function () {
    $version = DeliverableVersion::factory()->create([
        'deliverable_id' => $this->deliverable->id,
        'uploaded_by_id' => $this->user->id,
    ]);

    expect($version->uploadedBy)->toBeInstanceOf(User::class);
    expect($version->uploadedBy->id)->toBe($this->user->id);
});

test('deliverable has many versions', function () {
    DeliverableVersion::factory()->count(3)->create([
        'deliverable_id' => $this->deliverable->id,
        'uploaded_by_id' => $this->user->id,
    ]);

    expect($this->deliverable->versions)->toHaveCount(3);
    expect($this->deliverable->versions->first())->toBeInstanceOf(DeliverableVersion::class);
});

test('deliverable version can be soft deleted', function () {
    $version = DeliverableVersion::factory()->create([
        'deliverable_id' => $this->deliverable->id,
        'uploaded_by_id' => $this->user->id,
    ]);

    $version->delete();

    $this->assertSoftDeleted('deliverable_versions', [
        'id' => $version->id,
    ]);

    expect(DeliverableVersion::withTrashed()->find($version->id))->not->toBeNull();
});

test('versions are ordered latest first by scope', function () {
    DeliverableVersion::factory()->create([
        'deliverable_id' => $this->deliverable->id,
        'uploaded_by_id' => $this->user->id,
        'version_number' => 1,
    ]);
    DeliverableVersion::factory()->create([
        'deliverable_id' => $this->deliverable->id,
        'uploaded_by_id' => $this->user->id,
        'version_number' => 3,
    ]);
    DeliverableVersion::factory()->create([
        'deliverable_id' => $this->deliverable->id,
        'uploaded_by_id' => $this->user->id,
        'version_number' => 2,
    ]);

    $versions = DeliverableVersion::latestFirst()->get();

    expect($versions->first()->version_number)->toBe(3);
    expect($versions->last()->version_number)->toBe(1);
});

test('deliverable latest version returns highest version number', function () {
    DeliverableVersion::factory()->create([
        'deliverable_id' => $this->deliverable->id,
        'uploaded_by_id' => $this->user->id,
        'version_number' => 1,
    ]);
    DeliverableVersion::factory()->create([
        'deliverable_id' => $this->deliverable->id,
        'uploaded_by_id' => $this->user->id,
        'version_number' => 3,
    ]);
    DeliverableVersion::factory()->create([
        'deliverable_id' => $this->deliverable->id,
        'uploaded_by_id' => $this->user->id,
        'version_number' => 2,
    ]);

    $this->deliverable->refresh();

    expect($this->deliverable->latestVersion)->toBeInstanceOf(DeliverableVersion::class);
    expect($this->deliverable->latestVersion->version_number)->toBe(3);
});
