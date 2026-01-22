<?php

declare(strict_types=1);

use App\Enums\DocumentType;
use App\Models\CommunicationThread;
use App\Models\Document;
use App\Models\DocumentShareLink;
use App\Models\Folder;
use App\Models\Message;
use App\Models\Party;
use App\Models\Project;
use App\Models\User;
use App\Services\FileUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Integration tests for document management feature.
 * These tests cover critical end-to-end workflows that were identified
 * as gaps in the existing test suite.
 */
beforeEach(function () {
    Storage::fake('s3');
    Storage::fake('local');

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

/*
 * Test 1: Full upload-to-folder workflow via API
 * This tests the complete flow of uploading a document to a folder
 */
test('complete document upload to folder workflow stores file in S3 with folder association', function () {
    // Create a folder first
    $folder = Folder::create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'name' => 'Project Documents',
        'created_by_id' => $this->user->id,
    ]);

    // Upload a file using FileUploadService
    $file = UploadedFile::fake()->create('report.pdf', 2048, 'application/pdf');
    $fileUploadService = new FileUploadService();

    $path = $fileUploadService->storeDocument(
        file: $file,
        teamId: $this->team->id,
        context: 'projects',
        entityId: $this->project->id,
        disk: 's3'
    );

    // Create document record with folder association
    // Note: 'type' uses DocumentType enum, not MIME type
    $document = Document::create([
        'team_id' => $this->team->id,
        'uploaded_by_id' => $this->user->id,
        'documentable_type' => Project::class,
        'documentable_id' => $this->project->id,
        'folder_id' => $folder->id,
        'name' => 'report.pdf',
        'file_url' => $path,
        'type' => DocumentType::Reference,
        'file_size' => 2048,
    ]);

    // Verify the complete workflow
    Storage::disk('s3')->assertExists($path);
    expect($document->folder_id)->toBe($folder->id)
        ->and($document->folder->name)->toBe('Project Documents')
        ->and($path)->toContain("{$this->team->id}/projects/{$this->project->id}/");
});

/*
 * Test 2: Thread-level comment creation on document
 * This tests adding comments to a document via the API
 */
test('document comment controller adds thread-level comment to document', function () {
    $document = Document::factory()->create([
        'team_id' => $this->team->id,
        'uploaded_by_id' => $this->user->id,
        'documentable_type' => Project::class,
        'documentable_id' => $this->project->id,
    ]);

    $response = $this->actingAs($this->user)
        ->postJson(route('documents.comments.store', $document), [
            'content' => 'This is a thread-level comment on the document.',
        ]);

    $response->assertStatus(201);

    // Verify thread and message were created
    $document->refresh();
    expect($document->thread)->toBeInstanceOf(CommunicationThread::class);

    $message = Message::where('communication_thread_id', $document->thread->id)->first();
    expect($message)->not->toBeNull()
        ->and($message->content)->toBe('This is a thread-level comment on the document.')
        ->and($message->author_id)->toBe($this->user->id);
});

/*
 * Test 3: Folder nesting depth enforcement at controller level
 * This verifies the 3-level nesting limit is enforced when creating folders
 */
test('folder controller enforces maximum 3-level nesting depth', function () {
    // Create 3 levels of nested folders
    $level1 = Folder::create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'name' => 'Level 1',
        'created_by_id' => $this->user->id,
    ]);

    $level2 = Folder::create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'parent_id' => $level1->id,
        'name' => 'Level 2',
        'created_by_id' => $this->user->id,
    ]);

    $level3 = Folder::create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'parent_id' => $level2->id,
        'name' => 'Level 3',
        'created_by_id' => $this->user->id,
    ]);

    // Try to create a 4th level - should be rejected
    $response = $this->actingAs($this->user)
        ->postJson(route('folders.store'), [
            'name' => 'Level 4 - Should Fail',
            'project_id' => $this->project->id,
            'parent_id' => $level3->id,
        ]);

    // Controller returns 422 with an error message (not validation errors)
    $response->assertStatus(422);
    $response->assertJson(['error' => 'Maximum folder nesting depth reached.']);
});

/*
 * Test 4: Document access control inheritance from project
 * This tests that document policy correctly inherits from parent project
 */
test('document access inherits from parent project permissions', function () {
    // Create a private project
    $privateProject = Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $this->party->id,
        'owner_id' => $this->user->id,
        'is_private' => true,
    ]);

    // Create a document in the private project
    $document = Document::factory()->create([
        'team_id' => $this->team->id,
        'uploaded_by_id' => $this->user->id,
        'documentable_type' => Project::class,
        'documentable_id' => $privateProject->id,
    ]);

    // Create another team member without access to the private project
    $otherUser = User::factory()->create();
    $memberRole = $this->team->getRole('member');
    $this->team->users()->attach($otherUser, ['role_id' => $memberRole->id]);
    $otherUser->current_team_id = $this->team->id;
    $otherUser->save();

    // Other user should not be able to view the document
    // Using annotations index since there's no documents.show route
    $response = $this->actingAs($otherUser)
        ->getJson(route('documents.annotations.index', $document));

    $response->assertStatus(403);

    // Project owner should be able to view it
    $response = $this->actingAs($this->user)
        ->getJson(route('documents.annotations.index', $document));

    $response->assertStatus(200);
});

/*
 * Test 5: Folder access control inheritance from project
 * This tests that folder policy correctly inherits from parent project
 */
test('folder access inherits from parent project permissions', function () {
    // Create a private project
    $privateProject = Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $this->party->id,
        'owner_id' => $this->user->id,
        'is_private' => true,
    ]);

    // Create a folder in the private project
    $folder = Folder::create([
        'team_id' => $this->team->id,
        'project_id' => $privateProject->id,
        'name' => 'Private Project Folder',
        'created_by_id' => $this->user->id,
    ]);

    // Create another team member without access to the private project
    $otherUser = User::factory()->create();
    $memberRole = $this->team->getRole('member');
    $this->team->users()->attach($otherUser, ['role_id' => $memberRole->id]);
    $otherUser->current_team_id = $this->team->id;
    $otherUser->save();

    // Other user should not be able to view the folder
    $response = $this->actingAs($otherUser)
        ->getJson(route('folders.show', $folder));

    $response->assertStatus(403);
});

/*
 * Test 6: Team-scoped folder is accessible by all team members
 * This verifies team-scoped folders (no project_id) are accessible to all team members
 */
test('team-scoped folder is accessible by all team members', function () {
    // Create a team-scoped folder (project_id is null)
    $teamFolder = Folder::create([
        'team_id' => $this->team->id,
        'project_id' => null,
        'name' => 'Team Resources',
        'created_by_id' => $this->user->id,
    ]);

    // Create another team member
    $otherUser = User::factory()->create();
    $memberRole = $this->team->getRole('member');
    $this->team->users()->attach($otherUser, ['role_id' => $memberRole->id]);
    $otherUser->current_team_id = $this->team->id;
    $otherUser->save();

    // Other team member should be able to access the folder
    $response = $this->actingAs($otherUser)
        ->getJson(route('folders.show', $teamFolder));

    $response->assertStatus(200);
    $response->assertJsonFragment(['name' => 'Team Resources']);
});

/*
 * Test 7: Share link expiration is enforced when accessing document
 * This tests that expired share links are properly rejected
 */
test('share link expiration is enforced when accessing document', function () {
    $document = Document::factory()->create([
        'team_id' => $this->team->id,
        'uploaded_by_id' => $this->user->id,
        'documentable_type' => Project::class,
        'documentable_id' => $this->project->id,
        'file_url' => 'test/path/document.pdf',
    ]);

    // Create a share link that expires in 1 day
    $shareLink = DocumentShareLink::create([
        'document_id' => $document->id,
        'token' => DocumentShareLink::generateToken(),
        'expires_at' => now()->addDay(),
        'allow_download' => true,
        'created_by_id' => $this->user->id,
    ]);

    // Should be accessible
    $response = $this->getJson(route('shared.document', ['token' => $shareLink->token]));
    $response->assertStatus(200);

    // Simulate time passing - manually expire the link
    $shareLink->update(['expires_at' => now()->subHour()]);

    // Should now be rejected
    $response = $this->getJson(route('shared.document', ['token' => $shareLink->token]));
    $response->assertStatus(410); // HTTP 410 Gone
});

/*
 * Test 8: Document comment update and delete
 * This tests the full CRUD for document comments
 */
test('document comment controller supports update and delete operations', function () {
    $document = Document::factory()->create([
        'team_id' => $this->team->id,
        'uploaded_by_id' => $this->user->id,
        'documentable_type' => Project::class,
        'documentable_id' => $this->project->id,
    ]);

    // Create a comment
    $createResponse = $this->actingAs($this->user)
        ->postJson(route('documents.comments.store', $document), [
            'content' => 'Original comment text.',
        ]);

    $createResponse->assertStatus(201);

    $document->refresh();
    $message = Message::where('communication_thread_id', $document->thread->id)->first();

    // Update the comment
    $updateResponse = $this->actingAs($this->user)
        ->patchJson(route('documents.comments.update', [$document, $message]), [
            'content' => 'Updated comment text.',
        ]);

    $updateResponse->assertStatus(200);
    expect($message->fresh()->content)->toBe('Updated comment text.');

    // Delete the comment
    $deleteResponse = $this->actingAs($this->user)
        ->deleteJson(route('documents.comments.destroy', [$document, $message]));

    $deleteResponse->assertStatus(204);
    expect(Message::find($message->id))->toBeNull();
});

/*
 * Test 9: Annotation with page number for PDF
 * This tests creating an annotation with page tracking for PDFs
 */
test('annotation can be created with page number for PDF documents', function () {
    $document = Document::factory()->pdf()->create([
        'team_id' => $this->team->id,
        'uploaded_by_id' => $this->user->id,
        'documentable_type' => Project::class,
        'documentable_id' => $this->project->id,
    ]);

    $response = $this->actingAs($this->user)
        ->postJson(route('documents.annotations.store', $document), [
            'page' => 5,
            'x_percent' => 33.33,
            'y_percent' => 66.67,
            'content' => 'This annotation is on page 5 of the PDF.',
        ]);

    $response->assertStatus(201);

    $annotation = $document->annotations()->first();
    expect($annotation)->not->toBeNull()
        ->and($annotation->page)->toBe(5)
        ->and((float) $annotation->x_percent)->toEqual(33.33)
        ->and((float) $annotation->y_percent)->toEqual(66.67);
});

/*
 * Test 10: Share link with password and download permission combination
 * This tests creating and accessing a share link with both password and download restrictions
 */
test('share link respects both password protection and download permission settings', function () {
    $document = Document::factory()->create([
        'team_id' => $this->team->id,
        'uploaded_by_id' => $this->user->id,
        'documentable_type' => Project::class,
        'documentable_id' => $this->project->id,
        'file_url' => 'test/path/document.pdf',
    ]);

    // Create share link with password and download disabled
    $response = $this->actingAs($this->user)
        ->postJson(route('documents.share-links.store', $document), [
            'expires_in_days' => 30,
            'password' => 'securepassword123',
            'allow_download' => false,
        ]);

    $response->assertStatus(201);

    $shareLink = DocumentShareLink::first();
    expect($shareLink->hasPassword())->toBeTrue()
        ->and($shareLink->allow_download)->toBeFalse();

    // Access without password should prompt for password
    $accessResponse = $this->getJson(route('shared.document', ['token' => $shareLink->token]));
    $accessResponse->assertStatus(200);
    $accessResponse->assertJson(['requiresPassword' => true]);

    // Access with correct password
    $verifyResponse = $this->postJson(route('shared.verify', ['token' => $shareLink->token]), [
        'password' => 'securepassword123',
    ]);

    $verifyResponse->assertStatus(200);
    $verifyResponse->assertJsonPath('document.allowDownload', false);
});
