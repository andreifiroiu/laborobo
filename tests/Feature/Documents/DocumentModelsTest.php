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
use App\Models\Team;
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

test('folder supports nested folder creation up to 3 levels', function () {
    // Level 1: Root folder
    $level1 = Folder::create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'name' => 'Level 1 Folder',
        'created_by_id' => $this->user->id,
    ]);

    expect($level1)->toBeInstanceOf(Folder::class)
        ->and($level1->parent_id)->toBeNull()
        ->and($level1->depth())->toBe(1);

    // Level 2: Child of level 1
    $level2 = Folder::create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'parent_id' => $level1->id,
        'name' => 'Level 2 Folder',
        'created_by_id' => $this->user->id,
    ]);

    expect($level2->parent_id)->toBe($level1->id)
        ->and($level2->depth())->toBe(2);

    // Level 3: Child of level 2
    $level3 = Folder::create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'parent_id' => $level2->id,
        'name' => 'Level 3 Folder',
        'created_by_id' => $this->user->id,
    ]);

    expect($level3->parent_id)->toBe($level2->id)
        ->and($level3->depth())->toBe(3);

    // Verify children relationships work
    expect($level1->children)->toHaveCount(1)
        ->and($level2->children)->toHaveCount(1)
        ->and($level3->children)->toHaveCount(0);
});

test('folder can be project-scoped or team-scoped', function () {
    // Project-scoped folder (project_id is set)
    $projectFolder = Folder::create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'name' => 'Project Documents',
        'created_by_id' => $this->user->id,
    ]);

    expect($projectFolder->project_id)->toBe($this->project->id)
        ->and($projectFolder->isProjectScoped())->toBeTrue()
        ->and($projectFolder->isTeamScoped())->toBeFalse();

    // Team-scoped folder (project_id is null)
    $teamFolder = Folder::create([
        'team_id' => $this->team->id,
        'project_id' => null,
        'name' => 'Team Resources',
        'created_by_id' => $this->user->id,
    ]);

    expect($teamFolder->project_id)->toBeNull()
        ->and($teamFolder->isProjectScoped())->toBeFalse()
        ->and($teamFolder->isTeamScoped())->toBeTrue();

    // Verify forTeam scope works
    $folders = Folder::forTeam($this->team->id)->get();
    expect($folders)->toHaveCount(2);
});

test('document annotation stores coordinate as percentages', function () {
    $document = Document::factory()->create([
        'team_id' => $this->team->id,
        'uploaded_by_id' => $this->user->id,
        'documentable_type' => Project::class,
        'documentable_id' => $this->project->id,
    ]);

    $thread = CommunicationThread::create([
        'team_id' => $this->team->id,
        'threadable_type' => DocumentAnnotation::class,
        'threadable_id' => 0, // Will be updated after annotation creation
    ]);

    $annotation = DocumentAnnotation::create([
        'document_id' => $document->id,
        'page' => 3,
        'x_percent' => 45.75,
        'y_percent' => 82.30,
        'communication_thread_id' => $thread->id,
        'created_by_id' => $this->user->id,
    ]);

    // Use toEqual for decimal comparison (casted to string)
    expect((float) $annotation->x_percent)->toEqual(45.75)
        ->and((float) $annotation->y_percent)->toEqual(82.30)
        ->and($annotation->page)->toBe(3)
        ->and($annotation->document_id)->toBe($document->id);
});

test('document annotation associates with communication thread', function () {
    $document = Document::factory()->create([
        'team_id' => $this->team->id,
        'uploaded_by_id' => $this->user->id,
        'documentable_type' => Project::class,
        'documentable_id' => $this->project->id,
    ]);

    $thread = CommunicationThread::create([
        'team_id' => $this->team->id,
        'threadable_type' => DocumentAnnotation::class,
        'threadable_id' => 0,
    ]);

    $annotation = DocumentAnnotation::create([
        'document_id' => $document->id,
        'page' => null, // For images, page is null
        'x_percent' => 25.00,
        'y_percent' => 50.00,
        'communication_thread_id' => $thread->id,
        'created_by_id' => $this->user->id,
    ]);

    // Update thread to point to annotation
    $thread->update([
        'threadable_id' => $annotation->id,
    ]);

    // Verify relationships
    expect($annotation->thread)->toBeInstanceOf(CommunicationThread::class)
        ->and($annotation->thread->id)->toBe($thread->id)
        ->and($annotation->creator)->toBeInstanceOf(User::class)
        ->and($annotation->creator->id)->toBe($this->user->id)
        ->and($annotation->document)->toBeInstanceOf(Document::class);
});

test('document share link generates token and handles expiration', function () {
    $document = Document::factory()->create([
        'team_id' => $this->team->id,
        'uploaded_by_id' => $this->user->id,
        'documentable_type' => Project::class,
        'documentable_id' => $this->project->id,
    ]);

    // Create share link with expiration
    $shareLink = DocumentShareLink::create([
        'document_id' => $document->id,
        'token' => DocumentShareLink::generateToken(),
        'expires_at' => now()->addDays(7),
        'password_hash' => null,
        'allow_download' => true,
        'created_by_id' => $this->user->id,
    ]);

    expect($shareLink->token)->toHaveLength(64)
        ->and($shareLink->isExpired())->toBeFalse()
        ->and($shareLink->allow_download)->toBeTrue()
        ->and($shareLink->hasPassword())->toBeFalse();

    // Create expired share link
    $expiredLink = DocumentShareLink::create([
        'document_id' => $document->id,
        'token' => DocumentShareLink::generateToken(),
        'expires_at' => now()->subDay(),
        'password_hash' => null,
        'allow_download' => false,
        'created_by_id' => $this->user->id,
    ]);

    expect($expiredLink->isExpired())->toBeTrue();

    // Verify active scope filters out expired links
    $activeLinks = DocumentShareLink::active()->get();
    expect($activeLinks)->toHaveCount(1)
        ->and($activeLinks->first()->id)->toBe($shareLink->id);
});

test('document share access tracks access events', function () {
    $document = Document::factory()->create([
        'team_id' => $this->team->id,
        'uploaded_by_id' => $this->user->id,
        'documentable_type' => Project::class,
        'documentable_id' => $this->project->id,
    ]);

    $shareLink = DocumentShareLink::create([
        'document_id' => $document->id,
        'token' => DocumentShareLink::generateToken(),
        'expires_at' => null, // Permanent link
        'password_hash' => null,
        'allow_download' => true,
        'created_by_id' => $this->user->id,
    ]);

    // Record access
    $access1 = DocumentShareAccess::create([
        'document_share_link_id' => $shareLink->id,
        'accessed_at' => now(),
        'ip_address' => '192.168.1.100',
        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0',
    ]);

    $access2 = DocumentShareAccess::create([
        'document_share_link_id' => $shareLink->id,
        'accessed_at' => now()->addMinutes(30),
        'ip_address' => '10.0.0.55',
        'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0)',
    ]);

    // Verify access records
    expect($shareLink->accesses)->toHaveCount(2)
        ->and($access1->shareLink)->toBeInstanceOf(DocumentShareLink::class)
        ->and($access1->shareLink->id)->toBe($shareLink->id)
        ->and($access1->ip_address)->toBe('192.168.1.100');
});

test('document model has folder and annotation relationships', function () {
    $folder = Folder::create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'name' => 'Test Folder',
        'created_by_id' => $this->user->id,
    ]);

    $document = Document::factory()->create([
        'team_id' => $this->team->id,
        'uploaded_by_id' => $this->user->id,
        'documentable_type' => Project::class,
        'documentable_id' => $this->project->id,
        'folder_id' => $folder->id,
    ]);

    // Create communication thread for document
    $documentThread = CommunicationThread::create([
        'team_id' => $this->team->id,
        'threadable_type' => Document::class,
        'threadable_id' => $document->id,
    ]);

    // Create annotation for document
    $annotationThread = CommunicationThread::create([
        'team_id' => $this->team->id,
        'threadable_type' => DocumentAnnotation::class,
        'threadable_id' => 0,
    ]);

    $annotation = DocumentAnnotation::create([
        'document_id' => $document->id,
        'page' => 1,
        'x_percent' => 10.00,
        'y_percent' => 20.00,
        'communication_thread_id' => $annotationThread->id,
        'created_by_id' => $this->user->id,
    ]);

    // Create share link
    $shareLink = DocumentShareLink::create([
        'document_id' => $document->id,
        'token' => DocumentShareLink::generateToken(),
        'expires_at' => null,
        'allow_download' => true,
        'created_by_id' => $this->user->id,
    ]);

    // Verify relationships
    $document->refresh();

    expect($document->folder)->toBeInstanceOf(Folder::class)
        ->and($document->folder->id)->toBe($folder->id)
        ->and($document->thread)->toBeInstanceOf(CommunicationThread::class)
        ->and($document->annotations)->toHaveCount(1)
        ->and($document->shareLinks)->toHaveCount(1);

    // Verify folder has documents
    expect($folder->documents)->toHaveCount(1);
});
