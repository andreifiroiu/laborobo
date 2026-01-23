<?php

declare(strict_types=1);

use App\Enums\CommunicationType;
use App\Enums\DeliverableStatus;
use App\Enums\DraftStatus;
use App\Enums\InboxItemType;
use App\Enums\ProjectStatus;
use App\Enums\WorkOrderStatus;
use App\Events\DeliverableStatusChanged;
use App\Events\WorkOrderStatusChanged;
use App\Listeners\DeliverableStatusChangedListener;
use App\Listeners\WorkOrderStatusChangedListener;
use App\Models\Deliverable;
use App\Models\GlobalAISettings;
use App\Models\InboxItem;
use App\Models\Message;
use App\Models\Party;
use App\Models\Project;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\Artisan;

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

    // Enable auto-draft in team settings
    $settings = GlobalAISettings::forTeam($this->team);
    $settings->update([
        'client_comms_auto_draft' => true,
    ]);
});

test('manual trigger via controller creates draft and inbox item', function () {
    $this->actingAs($this->user);

    $response = $this->post(route('client-communications.draft'), [
        'entity_type' => 'project',
        'entity_id' => $this->project->id,
        'communication_type' => CommunicationType::StatusUpdate->value,
        'notes' => 'Please include recent progress updates',
    ]);

    $response->assertRedirect();

    // Verify draft was created
    $draft = Message::where('draft_status', DraftStatus::Draft)
        ->latest()
        ->first();

    expect($draft)->not->toBeNull();
    expect($draft->draft_metadata['communication_type'])->toBe(CommunicationType::StatusUpdate->value);
    expect($draft->draft_metadata['user_notes'])->toBe('Please include recent progress updates');

    // Verify inbox item was created
    $inboxItem = InboxItem::where('approvable_type', Message::class)
        ->where('approvable_id', $draft->id)
        ->first();

    expect($inboxItem)->not->toBeNull();
    expect($inboxItem->type)->toBe(InboxItemType::AgentDraft);
    expect($inboxItem->related_project_id)->toBe($this->project->id);
});

test('manual trigger for work order creates draft with work order context', function () {
    $this->actingAs($this->user);

    $response = $this->post(route('client-communications.draft'), [
        'entity_type' => 'work_order',
        'entity_id' => $this->workOrder->id,
        'communication_type' => CommunicationType::DeliverableNotification->value,
    ]);

    $response->assertRedirect();

    // Verify draft has work order context
    $draft = Message::where('draft_status', DraftStatus::Draft)
        ->latest()
        ->first();

    expect($draft)->not->toBeNull();
    expect($draft->draft_metadata['entity_type'])->toBe('WorkOrder');
    expect($draft->draft_metadata['entity_id'])->toBe($this->workOrder->id);

    // Verify inbox item links to work order
    $inboxItem = InboxItem::where('approvable_id', $draft->id)->first();
    expect($inboxItem->related_work_order_id)->toBe($this->workOrder->id);
});

test('work order status change listener creates draft for review status', function () {
    // Enable auto-draft setting
    $settings = GlobalAISettings::forTeam($this->team);
    $settings->update(['client_comms_auto_draft' => true]);

    // Create event with status transition to InReview
    $event = new WorkOrderStatusChanged(
        $this->workOrder,
        WorkOrderStatus::Active,
        WorkOrderStatus::InReview,
        $this->user
    );

    // Handle the event
    $listener = app(WorkOrderStatusChangedListener::class);
    $listener->handle($event);

    // Verify draft was created
    $draft = Message::where('draft_status', DraftStatus::Draft)->latest()->first();

    expect($draft)->not->toBeNull();
    expect($draft->draft_metadata['source_type'])->toBe('event_driven');
    expect($draft->draft_metadata['event_context']['from_status'])->toBe(WorkOrderStatus::Active->value);
    expect($draft->draft_metadata['event_context']['to_status'])->toBe(WorkOrderStatus::InReview->value);
});

test('work order status change listener creates draft for delivered status', function () {
    $settings = GlobalAISettings::forTeam($this->team);
    $settings->update(['client_comms_auto_draft' => true]);

    $event = new WorkOrderStatusChanged(
        $this->workOrder,
        WorkOrderStatus::Approved,
        WorkOrderStatus::Delivered,
        $this->user
    );

    $listener = app(WorkOrderStatusChangedListener::class);
    $listener->handle($event);

    $draft = Message::where('draft_status', DraftStatus::Draft)->latest()->first();

    expect($draft)->not->toBeNull();
    expect($draft->draft_metadata['communication_type'])->toBe(CommunicationType::StatusUpdate->value);
});

test('deliverable status change listener creates draft for ready deliverables', function () {
    $settings = GlobalAISettings::forTeam($this->team);
    $settings->update(['client_comms_auto_draft' => true]);

    $deliverable = Deliverable::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'project_id' => $this->project->id,
        'title' => 'Final Design',
        'status' => DeliverableStatus::Approved,
    ]);

    $event = new DeliverableStatusChanged(
        $deliverable,
        DeliverableStatus::InReview,
        DeliverableStatus::Approved,
        $this->user
    );

    $listener = app(DeliverableStatusChangedListener::class);
    $listener->handle($event);

    $draft = Message::where('draft_status', DraftStatus::Draft)->latest()->first();

    expect($draft)->not->toBeNull();
    expect($draft->draft_metadata['communication_type'])->toBe(CommunicationType::DeliverableNotification->value);
    expect($draft->draft_metadata['deliverable_id'])->toBe($deliverable->id);
});

test('scheduled command generates weekly summaries for active projects', function () {
    // Create a second active project with a recent work order
    $project2 = Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $this->party->id,
        'owner_id' => $this->user->id,
        'name' => 'Second Project',
        'status' => ProjectStatus::Active,
    ]);

    // Add a work order to the second project (required for activity check)
    WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $project2->id,
        'title' => 'Second Work Order',
        'status' => WorkOrderStatus::Active,
    ]);

    // Run the scheduled command
    Artisan::call('client-comms:weekly-summaries');

    // Verify drafts were created for active projects with recent activity
    $drafts = Message::where('draft_status', DraftStatus::Draft)
        ->where('draft_metadata->communication_type', CommunicationType::StatusUpdate->value)
        ->where('draft_metadata->source_type', 'scheduled')
        ->get();

    expect($drafts)->toHaveCount(2);
});

test('event listener skips draft when auto-draft is disabled', function () {
    // Disable auto-draft setting
    $settings = GlobalAISettings::forTeam($this->team);
    $settings->update(['client_comms_auto_draft' => false]);

    $event = new WorkOrderStatusChanged(
        $this->workOrder,
        WorkOrderStatus::Active,
        WorkOrderStatus::InReview,
        $this->user
    );

    $listener = app(WorkOrderStatusChangedListener::class);
    $listener->handle($event);

    // Verify no draft was created
    $draft = Message::where('draft_status', DraftStatus::Draft)->latest()->first();
    expect($draft)->toBeNull();
});
