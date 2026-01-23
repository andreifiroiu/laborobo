<?php

declare(strict_types=1);

use App\Enums\AIConfidence;
use App\Enums\AuthorType;
use App\Enums\CommunicationType;
use App\Enums\DraftStatus;
use App\Enums\InboxItemType;
use App\Enums\ProjectStatus;
use App\Enums\WorkOrderStatus;
use App\Models\CommunicationThread;
use App\Models\InboxItem;
use App\Models\Message;
use App\Models\Party;
use App\Models\Project;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\ClientCommsDraftService;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->team = $this->user->createTeam(['name' => 'Test Team']);
    $this->user->current_team_id = $this->team->id;
    $this->user->save();

    $this->party = Party::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'Test Client',
        'contact_name' => 'John Doe',
        'contact_email' => 'john@example.com',
        'preferred_language' => 'en',
    ]);

    $this->project = Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $this->party->id,
        'owner_id' => $this->user->id,
        'name' => 'Test Project',
        'status' => ProjectStatus::Active,
    ]);

    $this->workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'title' => 'Test Work Order',
        'status' => WorkOrderStatus::Active,
    ]);

    $this->draftService = app(ClientCommsDraftService::class);
});

test('creates draft with all communication types', function () {
    // Test each communication type
    $communicationTypes = [
        CommunicationType::StatusUpdate,
        CommunicationType::DeliverableNotification,
        CommunicationType::ClarificationRequest,
        CommunicationType::MilestoneAnnouncement,
    ];

    foreach ($communicationTypes as $type) {
        $draft = $this->draftService->createDraft($this->project, $type);

        expect($draft)->toBeInstanceOf(Message::class);
        expect($draft->author_type)->toBe(AuthorType::AiAgent);
        expect($draft->draft_status)->toBe(DraftStatus::Draft);
        expect($draft->draft_metadata)->toBeArray();
        expect($draft->draft_metadata['communication_type'])->toBe($type->value);
        expect($draft->draft_metadata)->toHaveKey('confidence');
        expect($draft->draft_metadata)->toHaveKey('context_summary');
    }
});

test('creates InboxItem for draft approval', function () {
    $draft = $this->draftService->createDraft(
        $this->workOrder,
        CommunicationType::StatusUpdate
    );

    $inboxItem = $this->draftService->createApprovalItem($draft, $this->workOrder);

    expect($inboxItem)->toBeInstanceOf(InboxItem::class);
    expect($inboxItem->type)->toBe(InboxItemType::AgentDraft);
    expect($inboxItem->approvable_type)->toBe(Message::class);
    expect($inboxItem->approvable_id)->toBe($draft->id);
    expect($inboxItem->related_work_order_id)->toBe($this->workOrder->id);
    expect($inboxItem->related_project_id)->toBe($this->workOrder->project_id);
    expect($inboxItem->ai_confidence)->toBeInstanceOf(AIConfidence::class);
});

test('draft approval triggers delivery job dispatch', function () {
    Queue::fake();

    $draft = $this->draftService->createDraft(
        $this->project,
        CommunicationType::StatusUpdate
    );

    $inboxItem = $this->draftService->createApprovalItem($draft, $this->project);

    $this->draftService->approveDraft($draft, $this->user);

    // Verify draft was approved
    $draft->refresh();
    expect($draft->draft_status)->toBe(DraftStatus::Approved);
    expect($draft->approved_by)->toBe($this->user->id);
    expect($draft->approved_at)->not->toBeNull();

    // Verify inbox item was marked as approved
    $inboxItem->refresh();
    expect($inboxItem->approved_at)->not->toBeNull();

    // Verify delivery job was dispatched
    Queue::assertPushed(\App\Jobs\ClientCommunicationDeliveryJob::class, function ($job) use ($draft) {
        return $job->message->id === $draft->id;
    });
});

test('rejects draft with feedback', function () {
    $draft = $this->draftService->createDraft(
        $this->workOrder,
        CommunicationType::ClarificationRequest
    );

    $inboxItem = $this->draftService->createApprovalItem($draft, $this->workOrder);
    $rejectionReason = 'The tone is not appropriate for this client';

    $this->draftService->rejectDraft($draft, $rejectionReason);

    $draft->refresh();
    expect($draft->draft_status)->toBe(DraftStatus::Rejected);
    expect($draft->rejection_reason)->toBe($rejectionReason);
    expect($draft->rejected_at)->not->toBeNull();

    $inboxItem->refresh();
    expect($inboxItem->rejected_at)->not->toBeNull();
});

test('edits draft content before approval', function () {
    $draft = $this->draftService->createDraft(
        $this->project,
        CommunicationType::MilestoneAnnouncement
    );

    $originalContent = $draft->content;
    $newContent = 'This is the updated draft content with revised messaging.';

    $updatedDraft = $this->draftService->updateDraft($draft, $newContent);

    expect($updatedDraft->id)->toBe($draft->id);
    expect($updatedDraft->content)->toBe($newContent);
    expect($updatedDraft->content)->not->toBe($originalContent);
    expect($updatedDraft->edited_at)->not->toBeNull();
    expect($updatedDraft->draft_status)->toBe(DraftStatus::Draft);
    // Metadata should be preserved
    expect($updatedDraft->draft_metadata)->toHaveKey('communication_type');
});

test('creates communication thread if not exists when creating draft', function () {
    // Ensure no thread exists initially
    expect($this->project->communicationThread)->toBeNull();

    $draft = $this->draftService->createDraft(
        $this->project,
        CommunicationType::StatusUpdate
    );

    $this->project->refresh();

    expect($draft->communicationThread)->not->toBeNull();
    expect($draft->communication_thread_id)->not->toBeNull();

    // Verify thread is linked to the project
    $thread = CommunicationThread::find($draft->communication_thread_id);
    expect($thread->threadable_type)->toBe(Project::class);
    expect($thread->threadable_id)->toBe($this->project->id);
});

test('cannot edit draft after approval', function () {
    Queue::fake();

    $draft = $this->draftService->createDraft(
        $this->project,
        CommunicationType::StatusUpdate
    );

    $this->draftService->approveDraft($draft, $this->user);

    $draft->refresh();
    expect($draft->draft_status)->toBe(DraftStatus::Approved);

    expect(fn () => $this->draftService->updateDraft($draft, 'New content'))
        ->toThrow(\InvalidArgumentException::class, 'Cannot edit a draft that is not in Draft status');
});
