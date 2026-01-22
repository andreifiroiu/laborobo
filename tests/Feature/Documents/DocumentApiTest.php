<?php

declare(strict_types=1);

use App\Models\CommunicationThread;
use App\Models\Document;
use App\Models\DocumentAnnotation;
use App\Models\DocumentShareAccess;
use App\Models\DocumentShareLink;
use App\Models\Folder;
use App\Models\Party;
use App\Models\Project;
use App\Models\User;

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
});

test('folder controller: user can create a folder', function () {
    $response = $this->actingAs($this->user)
        ->postJson(route('folders.store'), [
            'name' => 'New Folder',
            'project_id' => $this->project->id,
        ]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('folders', [
        'name' => 'New Folder',
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
    ]);
});

test('folder controller: user can list nested folders', function () {
    $rootFolder = Folder::create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'name' => 'Root Folder',
        'created_by_id' => $this->user->id,
    ]);

    $childFolder = Folder::create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'parent_id' => $rootFolder->id,
        'name' => 'Child Folder',
        'created_by_id' => $this->user->id,
    ]);

    $response = $this->actingAs($this->user)->getJson(route('folders.index', [
        'project_id' => $this->project->id,
    ]));

    $response->assertStatus(200);
    $response->assertJsonFragment(['name' => 'Root Folder']);
    $response->assertJsonFragment(['name' => 'Child Folder']);
});

test('folder controller: unauthorized user cannot access folder', function () {
    $otherUser = User::factory()->create();
    $otherTeam = $otherUser->createTeam(['name' => 'Other Team']);
    $otherUser->current_team_id = $otherTeam->id;
    $otherUser->save();

    $folder = Folder::create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'name' => 'Private Folder',
        'created_by_id' => $this->user->id,
    ]);

    $response = $this->actingAs($otherUser)->getJson(route('folders.show', $folder));

    $response->assertStatus(403);
});

test('document annotation controller: creates annotation with thread', function () {
    $document = Document::factory()->create([
        'team_id' => $this->team->id,
        'uploaded_by_id' => $this->user->id,
        'documentable_type' => Project::class,
        'documentable_id' => $this->project->id,
    ]);

    $response = $this->actingAs($this->user)
        ->postJson(route('documents.annotations.store', $document), [
            'page' => 1,
            'x_percent' => 25.5,
            'y_percent' => 50.0,
            'content' => 'This is my annotation comment',
        ]);

    $response->assertStatus(201);

    $annotation = DocumentAnnotation::first();
    expect($annotation)->not->toBeNull()
        ->and($annotation->thread)->toBeInstanceOf(CommunicationThread::class);
});

test('document share link controller: creates and revokes share links', function () {
    $document = Document::factory()->create([
        'team_id' => $this->team->id,
        'uploaded_by_id' => $this->user->id,
        'documentable_type' => Project::class,
        'documentable_id' => $this->project->id,
    ]);

    // Create share link
    $response = $this->actingAs($this->user)
        ->postJson(route('documents.share-links.store', $document), [
            'expires_in_days' => 7,
            'allow_download' => true,
        ]);

    $response->assertStatus(201);
    $shareLink = DocumentShareLink::first();
    expect($shareLink->token)->toHaveLength(64)
        ->and($shareLink->allow_download)->toBeTrue();

    // Revoke share link
    $deleteResponse = $this->actingAs($this->user)
        ->deleteJson(route('documents.share-links.destroy', [
            'document' => $document,
            'share_link' => $shareLink,
        ]));

    $deleteResponse->assertStatus(204);
    expect(DocumentShareLink::find($shareLink->id))->toBeNull();
});

test('shared document controller: public access with valid token', function () {
    $document = Document::factory()->create([
        'team_id' => $this->team->id,
        'uploaded_by_id' => $this->user->id,
        'documentable_type' => Project::class,
        'documentable_id' => $this->project->id,
        'file_url' => 'test/path/document.pdf',
    ]);

    $shareLink = DocumentShareLink::create([
        'document_id' => $document->id,
        'token' => DocumentShareLink::generateToken(),
        'expires_at' => now()->addDays(7),
        'allow_download' => true,
        'created_by_id' => $this->user->id,
    ]);

    // Access without authentication (JSON request)
    $response = $this->getJson(route('shared.document', ['token' => $shareLink->token]));

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'document' => ['id', 'name', 'type', 'previewUrl', 'allowDownload'],
    ]);

    // Verify access was tracked
    expect(DocumentShareAccess::count())->toBe(1);
});

test('shared document controller: password verification and access tracking', function () {
    $document = Document::factory()->create([
        'team_id' => $this->team->id,
        'uploaded_by_id' => $this->user->id,
        'documentable_type' => Project::class,
        'documentable_id' => $this->project->id,
        'file_url' => 'test/path/document.pdf',
    ]);

    $shareLink = DocumentShareLink::create([
        'document_id' => $document->id,
        'token' => DocumentShareLink::generateToken(),
        'expires_at' => now()->addDays(7),
        'password_hash' => bcrypt('secret123'),
        'allow_download' => false,
        'created_by_id' => $this->user->id,
    ]);

    // Initial access should indicate password required
    $initialResponse = $this->getJson(route('shared.document', ['token' => $shareLink->token]));
    $initialResponse->assertStatus(200);
    $initialResponse->assertJson(['requiresPassword' => true]);

    // Access with wrong password
    $wrongPasswordResponse = $this->postJson(route('shared.verify', ['token' => $shareLink->token]), [
        'password' => 'wrongpassword',
    ]);

    $wrongPasswordResponse->assertStatus(403);

    // Access with correct password
    $correctPasswordResponse = $this->postJson(route('shared.verify', ['token' => $shareLink->token]), [
        'password' => 'secret123',
    ]);

    $correctPasswordResponse->assertStatus(200);

    // Verify access was tracked
    expect(DocumentShareAccess::count())->toBe(1);
});

test('shared document controller: expired link returns error', function () {
    $document = Document::factory()->create([
        'team_id' => $this->team->id,
        'uploaded_by_id' => $this->user->id,
        'documentable_type' => Project::class,
        'documentable_id' => $this->project->id,
        'file_url' => 'test/path/document.pdf',
    ]);

    $shareLink = DocumentShareLink::create([
        'document_id' => $document->id,
        'token' => DocumentShareLink::generateToken(),
        'expires_at' => now()->subDay(), // Expired
        'allow_download' => true,
        'created_by_id' => $this->user->id,
    ]);

    $response = $this->getJson(route('shared.document', ['token' => $shareLink->token]));

    $response->assertStatus(410); // Gone
});
