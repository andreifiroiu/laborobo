<?php

declare(strict_types=1);

/**
 * Integration tests for Deliverable Management feature.
 * These tests cover critical end-to-end workflows that are not covered by other tests.
 */

use App\Enums\DeliverableStatus;
use App\Models\Deliverable;
use App\Models\DeliverableVersion;
use App\Models\Party;
use App\Models\Project;
use App\Models\User;
use App\Models\WorkOrder;
use App\Notifications\DeliverableStatusChangedNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
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
    $this->deliverable = Deliverable::factory()->draft()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'project_id' => $this->project->id,
    ]);
});

test('first version upload starts at version number 1', function () {
    // Ensure deliverable has no versions initially
    expect($this->deliverable->versions()->count())->toBe(0);

    $file = UploadedFile::fake()->create('initial-document.pdf', 1024, 'application/pdf');

    $response = $this->actingAs($this->user)
        ->postJson("/work/deliverables/{$this->deliverable->id}/versions", [
            'file' => $file,
            'notes' => 'Initial version',
        ]);

    $response->assertStatus(201);
    $response->assertJsonPath('data.version_number', 1);

    $this->assertDatabaseHas('deliverable_versions', [
        'deliverable_id' => $this->deliverable->id,
        'version_number' => 1,
        'file_name' => 'initial-document.pdf',
    ]);
});

test('version upload with blocked extension is rejected', function () {
    $blockedFile = UploadedFile::fake()->create('malicious.exe', 100);

    $response = $this->actingAs($this->user)
        ->postJson("/work/deliverables/{$this->deliverable->id}/versions", [
            'file' => $blockedFile,
        ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['file']);

    $this->assertDatabaseMissing('deliverable_versions', [
        'deliverable_id' => $this->deliverable->id,
    ]);
});

test('deliverable detail page includes version data', function () {
    // Create some versions for the deliverable
    DeliverableVersion::factory()->create([
        'deliverable_id' => $this->deliverable->id,
        'uploaded_by_id' => $this->user->id,
        'version_number' => 1,
        'file_name' => 'document-v1.pdf',
    ]);
    DeliverableVersion::factory()->create([
        'deliverable_id' => $this->deliverable->id,
        'uploaded_by_id' => $this->user->id,
        'version_number' => 2,
        'file_name' => 'document-v2.pdf',
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('deliverables.show', $this->deliverable));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('work/deliverables/[id]')
        ->has('deliverable', fn ($deliverable) => $deliverable
            ->where('versionCount', 2)
            ->has('latestVersion')
            ->etc()
        )
        ->has('versions', 2)
    );
});

test('complete version restore workflow creates new version with incremented number', function () {
    // Create initial versions
    $v1 = DeliverableVersion::factory()->create([
        'deliverable_id' => $this->deliverable->id,
        'uploaded_by_id' => $this->user->id,
        'version_number' => 1,
        'file_name' => 'original.pdf',
        'file_url' => '/storage/deliverables/1/v1_original.pdf',
    ]);
    DeliverableVersion::factory()->create([
        'deliverable_id' => $this->deliverable->id,
        'uploaded_by_id' => $this->user->id,
        'version_number' => 2,
        'file_name' => 'updated.pdf',
    ]);

    // Restore version 1
    $response = $this->actingAs($this->user)
        ->postJson("/work/deliverables/{$this->deliverable->id}/versions/{$v1->id}/restore");

    $response->assertStatus(201);
    $response->assertJsonPath('data.version_number', 3);
    $response->assertJsonPath('data.file_name', 'original.pdf');

    // Verify the restored version has correct notes
    $restoredVersion = DeliverableVersion::where('version_number', 3)->first();
    expect($restoredVersion->notes)->toContain('Restored from version 1');

    // Verify total version count
    expect($this->deliverable->versions()->count())->toBe(3);
});

test('sequential status changes all dispatch notifications correctly', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $assignee = User::factory()->create();

    $workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'created_by_id' => $owner->id,
        'assigned_to_id' => $assignee->id,
    ]);
    $deliverable = Deliverable::factory()->draft()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $workOrder->id,
        'project_id' => $this->project->id,
    ]);

    // Status change 1: Draft -> In Review
    $this->actingAs($this->user)
        ->patch(route('deliverables.update', $deliverable), [
            'status' => DeliverableStatus::InReview->value,
        ]);

    Notification::assertSentToTimes(
        $owner,
        DeliverableStatusChangedNotification::class,
        1
    );

    // Status change 2: In Review -> Approved
    $deliverable->refresh();
    $this->actingAs($this->user)
        ->patch(route('deliverables.update', $deliverable), [
            'status' => DeliverableStatus::Approved->value,
        ]);

    Notification::assertSentToTimes(
        $owner,
        DeliverableStatusChangedNotification::class,
        2
    );

    // Status change 3: Approved -> Delivered
    $deliverable->refresh();
    $this->actingAs($this->user)
        ->patch(route('deliverables.update', $deliverable), [
            'status' => DeliverableStatus::Delivered->value,
        ]);

    Notification::assertSentToTimes(
        $owner,
        DeliverableStatusChangedNotification::class,
        3
    );
});

test('end-to-end upload flow stores file and creates version record', function () {
    $file = UploadedFile::fake()->create('report.pdf', 2048, 'application/pdf');

    $response = $this->actingAs($this->user)
        ->postJson("/work/deliverables/{$this->deliverable->id}/versions", [
            'file' => $file,
            'notes' => 'Final report submission',
        ]);

    $response->assertStatus(201);

    // Verify version record
    $version = DeliverableVersion::where('deliverable_id', $this->deliverable->id)->first();
    expect($version)->not->toBeNull();
    expect($version->version_number)->toBe(1);
    expect($version->file_name)->toBe('report.pdf');
    expect($version->mime_type)->toBe('application/pdf');
    expect($version->notes)->toBe('Final report submission');
    expect($version->uploaded_by_id)->toBe($this->user->id);

    // Verify file was stored
    Storage::disk('public')->assertExists("deliverables/{$this->deliverable->id}/v1_report.pdf");

    // Verify response includes uploaded_by user data
    $response->assertJsonStructure([
        'data' => [
            'id',
            'version_number',
            'file_url',
            'file_name',
            'file_size',
            'mime_type',
            'notes',
            'uploaded_by' => ['id', 'name'],
            'created_at',
        ],
    ]);
});

test('version deletion prevents access but preserves soft deleted record', function () {
    $version = DeliverableVersion::factory()->create([
        'deliverable_id' => $this->deliverable->id,
        'uploaded_by_id' => $this->user->id,
        'version_number' => 1,
    ]);
    $versionId = $version->id;

    // Delete the version
    $response = $this->actingAs($this->user)
        ->deleteJson("/work/deliverables/{$this->deliverable->id}/versions/{$versionId}");

    $response->assertStatus(204);

    // Verify soft deleted
    $this->assertSoftDeleted('deliverable_versions', ['id' => $versionId]);

    // Verify cannot access deleted version via show endpoint
    $response = $this->actingAs($this->user)
        ->getJson("/work/deliverables/{$this->deliverable->id}/versions/{$versionId}");

    $response->assertStatus(404);

    // Verify version list does not include deleted version
    $listResponse = $this->actingAs($this->user)
        ->getJson("/work/deliverables/{$this->deliverable->id}/versions");

    $listResponse->assertStatus(200);
    expect($listResponse->json('meta.total'))->toBe(0);
});

test('latest version is correctly identified after multiple uploads', function () {
    // Upload version 1
    $file1 = UploadedFile::fake()->create('v1.pdf', 1024, 'application/pdf');
    $this->actingAs($this->user)
        ->postJson("/work/deliverables/{$this->deliverable->id}/versions", [
            'file' => $file1,
        ]);

    // Upload version 2
    $file2 = UploadedFile::fake()->create('v2.pdf', 1024, 'application/pdf');
    $this->actingAs($this->user)
        ->postJson("/work/deliverables/{$this->deliverable->id}/versions", [
            'file' => $file2,
        ]);

    // Refresh and check latestVersion
    $this->deliverable->refresh();
    $latestVersion = $this->deliverable->latestVersion;

    expect($latestVersion)->not->toBeNull();
    expect($latestVersion->version_number)->toBe(2);
    expect($latestVersion->file_name)->toBe('v2.pdf');

    // Verify via detail page as well
    $response = $this->actingAs($this->user)
        ->get(route('deliverables.show', $this->deliverable));

    $response->assertInertia(fn ($page) => $page
        ->has('deliverable.latestVersion', fn ($v) => $v
            ->where('versionNumber', 2)
            ->where('fileName', 'v2.pdf')
            ->etc()
        )
    );
});
