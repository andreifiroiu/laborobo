<?php

declare(strict_types=1);

use App\Enums\CommunicationType;
use App\Enums\DeliverableStatus;
use App\Enums\PlaybookType;
use App\Enums\ProjectStatus;
use App\Enums\WorkOrderStatus;
use App\Models\CommunicationThread;
use App\Models\Deliverable;
use App\Models\Message;
use App\Models\Party;
use App\Models\Playbook;
use App\Models\Project;
use App\Models\StatusTransition;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\CommsContextBuilder;
use App\ValueObjects\AgentContext;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->team = $this->user->createTeam(['name' => 'Test Team']);
    $this->user->current_team_id = $this->team->id;
    $this->user->save();

    $this->party = Party::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'Acme Corp',
        'contact_name' => 'John Smith',
        'contact_email' => 'john@acme.com',
        'preferred_language' => 'es',
    ]);

    $this->project = Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $this->party->id,
        'owner_id' => $this->user->id,
        'name' => 'Website Redesign',
        'description' => 'Complete website overhaul for Acme Corp',
        'status' => ProjectStatus::Active,
        'progress' => 45,
    ]);

    $this->workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'title' => 'Homepage Design',
        'description' => 'Design the new homepage',
        'status' => WorkOrderStatus::Active,
    ]);

    $this->contextBuilder = app(CommsContextBuilder::class);
});

test('builds work item context for project with complete details', function () {
    // Add deliverables to the project via work order
    Deliverable::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'title' => 'Homepage Mockup',
        'status' => DeliverableStatus::InReview,
    ]);

    // Add status transitions
    StatusTransition::create([
        'transitionable_type' => WorkOrder::class,
        'transitionable_id' => $this->workOrder->id,
        'user_id' => $this->user->id,
        'action_type' => 'status_change',
        'from_status' => 'draft',
        'to_status' => 'active',
        'created_at' => now()->subDay(),
    ]);

    $context = $this->contextBuilder->buildWorkItemContext($this->project);

    expect($context)->toBeArray();
    expect($context['title'])->toBe('Website Redesign');
    expect($context['description'])->toBe('Complete website overhaul for Acme Corp');
    expect($context['status'])->toBe('active');
    expect($context['progress'])->toBe(45);
    expect($context)->toHaveKey('deliverables');
    expect($context['deliverables'])->toHaveCount(1);
    expect($context['deliverables'][0]['title'])->toBe('Homepage Mockup');
    expect($context['deliverables'][0]['status'])->toBe('in_review');
});

test('builds work item context for work order with status transitions', function () {
    // Add multiple status transitions
    StatusTransition::create([
        'transitionable_type' => WorkOrder::class,
        'transitionable_id' => $this->workOrder->id,
        'user_id' => $this->user->id,
        'action_type' => 'status_change',
        'from_status' => 'draft',
        'to_status' => 'active',
        'created_at' => now()->subDays(2),
    ]);

    StatusTransition::create([
        'transitionable_type' => WorkOrder::class,
        'transitionable_id' => $this->workOrder->id,
        'user_id' => $this->user->id,
        'action_type' => 'status_change',
        'from_status' => 'active',
        'to_status' => 'in_review',
        'created_at' => now()->subDay(),
    ]);

    $context = $this->contextBuilder->buildWorkItemContext($this->workOrder);

    expect($context)->toBeArray();
    expect($context['title'])->toBe('Homepage Design');
    expect($context['status'])->toBe('active');
    expect($context)->toHaveKey('recent_status_transitions');
    expect($context['recent_status_transitions'])->toHaveCount(2);
    expect($context['recent_status_transitions'][0]['to_status'])->toBe('in_review');
});

test('extracts conversation history from thread with author info', function () {
    $thread = CommunicationThread::create([
        'team_id' => $this->team->id,
        'threadable_type' => Project::class,
        'threadable_id' => $this->project->id,
    ]);

    // Add messages to the thread
    Message::create([
        'communication_thread_id' => $thread->id,
        'author_id' => $this->user->id,
        'author_type' => 'human',
        'content' => 'Here is the initial design concept.',
        'type' => 'note',
    ]);

    Message::create([
        'communication_thread_id' => $thread->id,
        'author_id' => $this->user->id,
        'author_type' => 'human',
        'content' => 'Updated mockup attached.',
        'type' => 'note',
    ]);

    $historyContext = $this->contextBuilder->buildThreadHistoryContext($thread, limit: 10);

    expect($historyContext)->toBeArray();
    expect($historyContext)->toHaveKey('messages');
    expect($historyContext['messages'])->toHaveCount(2);
    expect($historyContext['messages'][0])->toHaveKey('content');
    expect($historyContext['messages'][0])->toHaveKey('author_name');
    expect($historyContext['messages'][0])->toHaveKey('created_at');
});

test('finds communication templates from playbooks by type', function () {
    // Create template playbooks
    Playbook::factory()->create([
        'team_id' => $this->team->id,
        'type' => PlaybookType::Template,
        'name' => 'Status Update Template',
        'tags' => ['status_update', 'client_communication'],
    ]);

    Playbook::factory()->create([
        'team_id' => $this->team->id,
        'type' => PlaybookType::Template,
        'name' => 'Deliverable Notification Template',
        'tags' => ['deliverable_notification', 'client_communication'],
    ]);

    Playbook::factory()->create([
        'team_id' => $this->team->id,
        'type' => PlaybookType::SOP,
        'name' => 'Design Process SOP',
        'tags' => ['design'],
    ]);

    $templates = $this->contextBuilder->findCommunicationTemplates(
        CommunicationType::StatusUpdate,
        ['client_communication']
    );

    expect($templates)->toHaveCount(1);
    expect($templates->first()->name)->toBe('Status Update Template');
});

test('builds party context with contact info and preferences', function () {
    // Create another project for relationship history
    Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $this->party->id,
        'owner_id' => $this->user->id,
    ]);

    $partyContext = $this->contextBuilder->buildPartyContext($this->party);

    expect($partyContext)->toBeArray();
    expect($partyContext['name'])->toBe('Acme Corp');
    expect($partyContext['contact_name'])->toBe('John Smith');
    expect($partyContext['contact_email'])->toBe('john@acme.com');
    expect($partyContext['preferred_language'])->toBe('es');
    expect($partyContext)->toHaveKey('projects_count');
    expect($partyContext['projects_count'])->toBe(2);
});

test('assembles full context combining all sources for agent consumption', function () {
    // Add a deliverable
    Deliverable::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'title' => 'Design Mockup',
        'status' => DeliverableStatus::Approved,
    ]);

    // Create thread with message
    $thread = CommunicationThread::create([
        'team_id' => $this->team->id,
        'threadable_type' => Project::class,
        'threadable_id' => $this->project->id,
    ]);

    Message::create([
        'communication_thread_id' => $thread->id,
        'author_id' => $this->user->id,
        'author_type' => 'human',
        'content' => 'Previous client communication.',
        'type' => 'note',
    ]);

    // Create a template playbook
    Playbook::factory()->create([
        'team_id' => $this->team->id,
        'type' => PlaybookType::Template,
        'name' => 'Status Update Template',
        'tags' => ['status_update'],
    ]);

    $fullContext = $this->contextBuilder->buildFullContext(
        $this->project,
        CommunicationType::StatusUpdate
    );

    expect($fullContext)->toBeInstanceOf(AgentContext::class);
    expect($fullContext->isEmpty())->toBeFalse();
    expect($fullContext->projectContext)->toHaveKey('work_item');
    expect($fullContext->clientContext)->toHaveKey('name');
    expect($fullContext->metadata)->toHaveKey('communication_type');
    expect($fullContext->metadata['communication_type'])->toBe('status_update');
    expect($fullContext->metadata)->toHaveKey('target_language');
    expect($fullContext->metadata['target_language'])->toBe('es');
});
