<?php

declare(strict_types=1);

use App\Enums\CommunicationType;
use App\Enums\DraftStatus;
use App\Enums\InboxItemType;
use App\Enums\ProjectStatus;
use App\Enums\WorkOrderStatus;
use App\Events\WorkOrderStatusChanged;
use App\Jobs\ClientCommunicationDeliveryJob;
use App\Listeners\WorkOrderStatusChangedListener;
use App\Models\GlobalAISettings;
use App\Models\InboxItem;
use App\Models\Message;
use App\Models\Party;
use App\Models\Project;
use App\Models\User;
use App\Models\WorkOrder;
use App\Notifications\ClientCommunicationNotification;
use App\Services\ClientCommsDraftService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

/**
 * Integration tests for Client Comms Agent end-to-end workflows.
 *
 * These tests verify critical user workflows that span multiple components:
 * - Draft creation -> Inbox approval -> Email delivery
 * - Event-driven triggers with proper context
 * - Multi-language support
 * - Edge cases (Party without email)
 */
beforeEach(function () {
    $this->user = User::factory()->create();
    $this->team = $this->user->createTeam(['name' => 'Test Team']);
    $this->user->current_team_id = $this->team->id;
    $this->user->save();

    $this->party = Party::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'Acme Corp',
        'contact_name' => 'Jane Smith',
        'contact_email' => 'jane@acme.com',
        'preferred_language' => 'en',
    ]);

    $this->project = Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $this->party->id,
        'owner_id' => $this->user->id,
        'name' => 'Website Redesign',
        'status' => ProjectStatus::Active,
        'progress' => 50,
    ]);

    $this->workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'title' => 'Homepage Design',
        'status' => WorkOrderStatus::Active,
    ]);

    // Enable auto-draft in team settings
    $settings = GlobalAISettings::forTeam($this->team);
    $settings->update(['client_comms_auto_draft' => true]);

    $this->draftService = app(ClientCommsDraftService::class);
});

test('end-to-end: user triggers draft, appears in inbox, approves, and email is sent', function () {
    Queue::fake();
    Notification::fake();

    // Step 1: User triggers draft creation via controller
    $this->actingAs($this->user);

    $response = $this->post(route('client-communications.draft'), [
        'entity_type' => 'project',
        'entity_id' => $this->project->id,
        'communication_type' => CommunicationType::StatusUpdate->value,
    ]);

    $response->assertRedirect();

    // Step 2: Verify draft appears in the system
    $draft = Message::where('draft_status', DraftStatus::Draft)->latest()->first();
    expect($draft)->not->toBeNull();
    expect($draft->content)->toContain($this->party->contact_name);

    // Step 3: Verify InboxItem is created for approval
    $inboxItem = InboxItem::where('approvable_type', Message::class)
        ->where('approvable_id', $draft->id)
        ->first();

    expect($inboxItem)->not->toBeNull();
    expect($inboxItem->type)->toBe(InboxItemType::AgentDraft);
    expect($inboxItem->related_project_id)->toBe($this->project->id);

    // Step 4: User approves the draft (queue is faked, job will be captured)
    $this->draftService->approveDraft($draft, $this->user);

    // Step 5: Verify draft status updated to approved
    $draft->refresh();
    expect($draft->draft_status)->toBe(DraftStatus::Approved);
    expect($draft->approved_by)->toBe($this->user->id);
    expect($draft->approved_at)->not->toBeNull();

    // Step 6: Verify InboxItem marked as approved
    $inboxItem->refresh();
    expect($inboxItem->approved_at)->not->toBeNull();

    // Step 7: Verify delivery job was dispatched
    Queue::assertPushed(ClientCommunicationDeliveryJob::class, function ($job) use ($draft) {
        return $job->message->id === $draft->id;
    });

    // Step 8: Process the delivery job manually to verify email flow
    // Reset queue fake to allow actual processing, then fake notifications
    Queue::swap(new \Illuminate\Support\Testing\Fakes\QueueFake(app()));
    $job = new ClientCommunicationDeliveryJob($draft, $this->party);
    $job->handle();

    // Step 9: Verify email was sent
    Notification::assertSentOnDemand(
        ClientCommunicationNotification::class,
        function ($notification, $channels, $notifiable) {
            return $notifiable->routes['mail'] === 'jane@acme.com';
        }
    );

    // Step 10: Verify message marked as sent
    $draft->refresh();
    expect($draft->draft_status)->toBe(DraftStatus::Sent);
});

test('multi-language: draft respects Party preferred language in metadata', function () {
    // Create a Party with Spanish preference
    $spanishParty = Party::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'Cliente Espanol',
        'contact_name' => 'Carlos Garcia',
        'contact_email' => 'carlos@ejemplo.com',
        'preferred_language' => 'es',
    ]);

    $project = Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $spanishParty->id,
        'owner_id' => $this->user->id,
        'name' => 'Spanish Project',
        'status' => ProjectStatus::Active,
    ]);

    // Create draft
    $draft = $this->draftService->createDraft(
        $project,
        CommunicationType::StatusUpdate
    );

    // Verify target language is captured in metadata
    expect($draft->draft_metadata['target_language'])->toBe('es');
    expect($draft->draft_metadata['communication_type'])->toBe(CommunicationType::StatusUpdate->value);
});

test('edge case: draft approval succeeds gracefully when Party has no email', function () {
    Queue::fake();

    // Create a Party without email
    $partyNoEmail = Party::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'No Email Client',
        'contact_name' => 'Bob Smith',
        'contact_email' => null,
        'email' => null,
        'preferred_language' => 'en',
    ]);

    $project = Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $partyNoEmail->id,
        'owner_id' => $this->user->id,
        'name' => 'Project Without Email',
        'status' => ProjectStatus::Active,
    ]);

    // Create and approve draft
    $draft = $this->draftService->createDraft(
        $project,
        CommunicationType::StatusUpdate
    );

    // Approval should not throw even though Party has no email
    $this->draftService->approveDraft($draft, $this->user);

    // Verify draft was approved
    $draft->refresh();
    expect($draft->draft_status)->toBe(DraftStatus::Approved);

    // Delivery job is still dispatched (the job handles null email gracefully)
    Queue::assertPushed(ClientCommunicationDeliveryJob::class);
});

test('event-driven: work order status change includes event context in draft metadata', function () {
    $settings = GlobalAISettings::forTeam($this->team);
    $settings->update(['client_comms_auto_draft' => true]);

    // Trigger event for work order status change
    $event = new WorkOrderStatusChanged(
        $this->workOrder,
        WorkOrderStatus::Active,
        WorkOrderStatus::InReview,
        $this->user
    );

    $listener = app(WorkOrderStatusChangedListener::class);
    $listener->handle($event);

    // Verify draft was created with event context
    $draft = Message::where('draft_status', DraftStatus::Draft)->latest()->first();

    expect($draft)->not->toBeNull();
    expect($draft->draft_metadata)->toHaveKey('source_type');
    expect($draft->draft_metadata['source_type'])->toBe('event_driven');
    expect($draft->draft_metadata)->toHaveKey('event_context');
    expect($draft->draft_metadata['event_context']['from_status'])->toBe(WorkOrderStatus::Active->value);
    expect($draft->draft_metadata['event_context']['to_status'])->toBe(WorkOrderStatus::InReview->value);
});

test('cannot reject a draft that is already approved', function () {
    Queue::fake();

    $draft = $this->draftService->createDraft(
        $this->project,
        CommunicationType::StatusUpdate
    );

    // Approve the draft first
    $this->draftService->approveDraft($draft, $this->user);
    $draft->refresh();

    // Attempting to reject should throw an exception
    expect(fn () => $this->draftService->rejectDraft($draft, 'Changed my mind'))
        ->toThrow(\InvalidArgumentException::class, 'Cannot reject a draft that is not in Draft status');
});

test('cannot approve a draft that is already rejected', function () {
    $draft = $this->draftService->createDraft(
        $this->project,
        CommunicationType::StatusUpdate
    );

    // Reject the draft first
    $this->draftService->rejectDraft($draft, 'Not appropriate');
    $draft->refresh();

    // Attempting to approve should throw an exception
    expect(fn () => $this->draftService->approveDraft($draft, $this->user))
        ->toThrow(\InvalidArgumentException::class, 'Cannot approve a draft that is not in Draft status');
});

test('delivery job skips sending when message is not in approved status', function () {
    Notification::fake();

    $draft = $this->draftService->createDraft(
        $this->project,
        CommunicationType::StatusUpdate
    );

    // Do not approve - draft is still in Draft status
    expect($draft->draft_status)->toBe(DraftStatus::Draft);

    // Run the delivery job directly (simulating a race condition or bug)
    $job = new ClientCommunicationDeliveryJob($draft, $this->party);
    $job->handle();

    // Verify no notification was sent
    Notification::assertNothingSent();

    // Verify draft status unchanged
    $draft->refresh();
    expect($draft->draft_status)->toBe(DraftStatus::Draft);
});

test('delivery job logs warning and skips when Party has no email', function () {
    Notification::fake();

    // Create Party without email
    $partyNoEmail = Party::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'No Email Client',
        'contact_name' => 'Bob',
        'contact_email' => null,
        'email' => null,
    ]);

    $project = Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $partyNoEmail->id,
        'owner_id' => $this->user->id,
        'name' => 'Test Project',
        'status' => ProjectStatus::Active,
    ]);

    $draft = $this->draftService->createDraft(
        $project,
        CommunicationType::StatusUpdate
    );

    // Manually set to approved for this test
    $draft->markAsApproved($this->user);
    $draft->refresh();

    // Run delivery job
    $job = new ClientCommunicationDeliveryJob($draft, $partyNoEmail);
    $job->handle();

    // Verify no notification was sent (Party has no email)
    Notification::assertNothingSent();

    // Message should NOT be marked as sent (delivery was skipped)
    $draft->refresh();
    expect($draft->draft_status)->toBe(DraftStatus::Approved);
});
