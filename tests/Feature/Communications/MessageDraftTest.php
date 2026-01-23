<?php

declare(strict_types=1);

use App\Enums\AuthorType;
use App\Enums\CommunicationType;
use App\Enums\DraftStatus;
use App\Enums\InboxItemType;
use App\Enums\SourceType;
use App\Models\CommunicationThread;
use App\Models\InboxItem;
use App\Models\Message;
use App\Models\Party;
use App\Models\Project;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->team = $this->user->createTeam(['name' => 'Test Team']);
    $this->user->current_team_id = $this->team->id;
    $this->user->save();

    $this->party = Party::factory()->create([
        'team_id' => $this->team->id,
        'preferred_language' => 'en',
    ]);
    $this->project = Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $this->party->id,
        'owner_id' => $this->user->id,
    ]);

    $this->thread = CommunicationThread::create([
        'team_id' => $this->team->id,
        'threadable_type' => Project::class,
        'threadable_id' => $this->project->id,
    ]);
});

test('message model supports draft status fields', function () {
    $message = Message::create([
        'communication_thread_id' => $this->thread->id,
        'author_id' => $this->user->id,
        'author_type' => AuthorType::AiAgent,
        'content' => 'AI drafted status update for your project.',
        'type' => 'status_update',
        'draft_status' => DraftStatus::Draft,
        'draft_metadata' => [
            'communication_type' => CommunicationType::StatusUpdate->value,
            'confidence' => 'high',
            'origin' => 'manual_trigger',
        ],
    ]);

    $message->refresh();

    expect($message->draft_status)->toBe(DraftStatus::Draft);
    expect($message->isDraft())->toBeTrue();
    expect($message->isApproved())->toBeFalse();
    expect($message->draft_metadata)->toBeArray();
    expect($message->draft_metadata['communication_type'])->toBe('status_update');
    expect($message->draft_metadata['confidence'])->toBe('high');
});

test('party model supports preferred language field with default', function () {
    $partyWithLanguage = Party::factory()->create([
        'team_id' => $this->team->id,
        'preferred_language' => 'es',
    ]);

    // Create a party and then explicitly set the field to null to test the accessor
    $partyWithDefault = Party::factory()->create([
        'team_id' => $this->team->id,
    ]);
    // Test the accessor with a null value to verify it returns 'en' as default
    expect($partyWithDefault->getPreferredLanguageAttribute(null))->toBe('en');

    expect($partyWithLanguage->preferred_language)->toBe('es');
});

test('inbox item links to message draft via approvable morph', function () {
    $message = Message::create([
        'communication_thread_id' => $this->thread->id,
        'author_id' => $this->user->id,
        'author_type' => AuthorType::AiAgent,
        'content' => 'Draft message content for approval.',
        'type' => 'status_update',
        'draft_status' => DraftStatus::Draft,
    ]);

    $inboxItem = InboxItem::create([
        'team_id' => $this->team->id,
        'type' => InboxItemType::AgentDraft,
        'title' => 'Review AI Draft Communication',
        'content_preview' => substr($message->content, 0, 100),
        'full_content' => $message->content,
        'source_id' => 'client-comms-agent',
        'source_name' => 'Client Communications Agent',
        'source_type' => SourceType::AIAgent,
        'approvable_type' => Message::class,
        'approvable_id' => $message->id,
        'related_project_id' => $this->project->id,
        'related_project_name' => $this->project->name,
    ]);

    expect($inboxItem->approvable)->toBeInstanceOf(Message::class);
    expect($inboxItem->approvable->id)->toBe($message->id);
    expect($inboxItem->type)->toBe(InboxItemType::AgentDraft);
});

test('message draft approval workflow updates status and timestamps', function () {
    $message = Message::create([
        'communication_thread_id' => $this->thread->id,
        'author_id' => $this->user->id,
        'author_type' => AuthorType::AiAgent,
        'content' => 'Draft message awaiting approval.',
        'type' => 'status_update',
        'draft_status' => DraftStatus::Draft,
    ]);

    expect($message->isDraft())->toBeTrue();
    expect($message->approved_at)->toBeNull();

    $message->markAsApproved($this->user);
    $message->refresh();

    expect($message->isApproved())->toBeTrue();
    expect($message->approved_at)->not->toBeNull();
    expect($message->approved_by)->toBe($this->user->id);
});

test('message draft rejection workflow updates status and stores reason', function () {
    $message = Message::create([
        'communication_thread_id' => $this->thread->id,
        'author_id' => $this->user->id,
        'author_type' => AuthorType::AiAgent,
        'content' => 'Draft message that will be rejected.',
        'type' => 'status_update',
        'draft_status' => DraftStatus::Draft,
    ]);

    $rejectionReason = 'The tone is too informal for this client.';
    $message->markAsRejected($rejectionReason);
    $message->refresh();

    expect($message->isRejected())->toBeTrue();
    expect($message->rejected_at)->not->toBeNull();
    expect($message->rejection_reason)->toBe($rejectionReason);
});

test('draft status enum provides correct labels and state checks', function () {
    expect(DraftStatus::Draft->label())->toBe('Draft');
    expect(DraftStatus::Approved->label())->toBe('Approved');
    expect(DraftStatus::Rejected->label())->toBe('Rejected');
    expect(DraftStatus::Sent->label())->toBe('Sent');

    expect(DraftStatus::Draft->isFinal())->toBeFalse();
    expect(DraftStatus::Approved->isFinal())->toBeTrue();
    expect(DraftStatus::Rejected->isFinal())->toBeTrue();
    expect(DraftStatus::Sent->isFinal())->toBeTrue();
});
