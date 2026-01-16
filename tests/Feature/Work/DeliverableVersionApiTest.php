<?php

declare(strict_types=1);

use App\Models\Deliverable;
use App\Models\DeliverableVersion;
use App\Models\Party;
use App\Models\Project;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');

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

test('GET versions list returns paginated results', function () {
    DeliverableVersion::factory()->count(20)->create([
        'deliverable_id' => $this->deliverable->id,
        'uploaded_by_id' => $this->user->id,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson("/work/deliverables/{$this->deliverable->id}/versions");

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'data' => [
            '*' => [
                'id',
                'version_number',
                'file_url',
                'file_name',
                'file_size',
                'mime_type',
                'notes',
                'uploaded_by',
                'created_at',
                'updated_at',
            ],
        ],
        'meta' => [
            'current_page',
            'last_page',
            'per_page',
            'total',
        ],
    ]);
    expect($response->json('meta.per_page'))->toBe(15);
});

test('POST upload creates new version with incremented version number', function () {
    DeliverableVersion::factory()->create([
        'deliverable_id' => $this->deliverable->id,
        'uploaded_by_id' => $this->user->id,
        'version_number' => 1,
    ]);

    $file = UploadedFile::fake()->create('document.pdf', 1024, 'application/pdf');

    $response = $this->actingAs($this->user)
        ->postJson("/work/deliverables/{$this->deliverable->id}/versions", [
            'file' => $file,
            'notes' => 'Updated version with fixes',
        ]);

    $response->assertStatus(201);
    $response->assertJsonPath('data.version_number', 2);
    $response->assertJsonPath('data.notes', 'Updated version with fixes');

    $this->assertDatabaseHas('deliverable_versions', [
        'deliverable_id' => $this->deliverable->id,
        'version_number' => 2,
    ]);
});

test('GET single version returns correct data', function () {
    $version = DeliverableVersion::factory()->create([
        'deliverable_id' => $this->deliverable->id,
        'uploaded_by_id' => $this->user->id,
        'version_number' => 1,
        'file_name' => 'test-document.pdf',
        'notes' => 'Initial version',
    ]);

    $response = $this->actingAs($this->user)
        ->getJson("/work/deliverables/{$this->deliverable->id}/versions/{$version->id}");

    $response->assertStatus(200);
    $response->assertJsonPath('data.id', $version->id);
    $response->assertJsonPath('data.version_number', 1);
    $response->assertJsonPath('data.file_name', 'test-document.pdf');
    $response->assertJsonPath('data.notes', 'Initial version');
});

test('POST restore creates new version copying previous version file', function () {
    $originalVersion = DeliverableVersion::factory()->create([
        'deliverable_id' => $this->deliverable->id,
        'uploaded_by_id' => $this->user->id,
        'version_number' => 1,
        'file_name' => 'original-document.pdf',
        'file_url' => '/storage/deliverables/1/v1_original-document.pdf',
        'notes' => 'Original version',
    ]);

    DeliverableVersion::factory()->create([
        'deliverable_id' => $this->deliverable->id,
        'uploaded_by_id' => $this->user->id,
        'version_number' => 2,
        'file_name' => 'updated-document.pdf',
        'notes' => 'Updated version',
    ]);

    $response = $this->actingAs($this->user)
        ->postJson("/work/deliverables/{$this->deliverable->id}/versions/{$originalVersion->id}/restore");

    $response->assertStatus(201);
    $response->assertJsonPath('data.version_number', 3);
    $response->assertJsonPath('data.file_name', 'original-document.pdf');

    $this->assertDatabaseHas('deliverable_versions', [
        'deliverable_id' => $this->deliverable->id,
        'version_number' => 3,
    ]);
});

test('DELETE soft deletes version', function () {
    $version = DeliverableVersion::factory()->create([
        'deliverable_id' => $this->deliverable->id,
        'uploaded_by_id' => $this->user->id,
        'version_number' => 1,
    ]);

    $response = $this->actingAs($this->user)
        ->deleteJson("/work/deliverables/{$this->deliverable->id}/versions/{$version->id}");

    $response->assertStatus(204);

    $this->assertSoftDeleted('deliverable_versions', [
        'id' => $version->id,
    ]);
});

test('user cannot access versions from another team deliverable', function () {
    $otherUser = User::factory()->create();
    $otherTeam = $otherUser->createTeam(['name' => 'Other Team']);
    $otherParty = Party::factory()->create(['team_id' => $otherTeam->id]);
    $otherProject = Project::factory()->create([
        'team_id' => $otherTeam->id,
        'party_id' => $otherParty->id,
        'owner_id' => $otherUser->id,
    ]);
    $otherWorkOrder = WorkOrder::factory()->create([
        'team_id' => $otherTeam->id,
        'project_id' => $otherProject->id,
    ]);
    $otherDeliverable = Deliverable::factory()->create([
        'team_id' => $otherTeam->id,
        'work_order_id' => $otherWorkOrder->id,
        'project_id' => $otherProject->id,
    ]);

    DeliverableVersion::factory()->create([
        'deliverable_id' => $otherDeliverable->id,
        'uploaded_by_id' => $otherUser->id,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson("/work/deliverables/{$otherDeliverable->id}/versions");

    $response->assertStatus(403);
});
