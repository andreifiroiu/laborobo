<?php

declare(strict_types=1);

use App\Enums\MessageType;
use App\Models\CommunicationThread;
use App\Models\Message;
use App\Models\Party;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkOrder;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->team = $this->owner->createTeam(['name' => 'Test Team']);
    $this->owner->current_team_id = $this->team->id;
    $this->owner->save();

    $this->party = Party::factory()->create(['team_id' => $this->team->id]);
    $this->project = Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $this->party->id,
        'owner_id' => $this->owner->id,
    ]);
    $this->workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->owner->id,
    ]);
    $this->task = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->owner->id,
    ]);
});

test('GET /communications returns paginated messages across threads', function () {
    // Create threads for different work items
    $projectThread = CommunicationThread::create([
        'team_id' => $this->team->id,
        'threadable_type' => Project::class,
        'threadable_id' => $this->project->id,
    ]);

    $workOrderThread = CommunicationThread::create([
        'team_id' => $this->team->id,
        'threadable_type' => WorkOrder::class,
        'threadable_id' => $this->workOrder->id,
    ]);

    $taskThread = CommunicationThread::create([
        'team_id' => $this->team->id,
        'threadable_type' => Task::class,
        'threadable_id' => $this->task->id,
    ]);

    // Create messages in each thread
    Message::create([
        'communication_thread_id' => $projectThread->id,
        'author_id' => $this->owner->id,
        'author_type' => 'human',
        'content' => 'Project message content',
        'type' => MessageType::Note,
    ]);

    Message::create([
        'communication_thread_id' => $workOrderThread->id,
        'author_id' => $this->owner->id,
        'author_type' => 'human',
        'content' => 'Work order message content',
        'type' => MessageType::Question,
    ]);

    Message::create([
        'communication_thread_id' => $taskThread->id,
        'author_id' => $this->owner->id,
        'author_type' => 'human',
        'content' => 'Task message content',
        'type' => MessageType::Decision,
    ]);

    $response = $this->actingAs($this->owner)
        ->get(route('communications.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('communications/index')
        ->has('messages.data', 3)
        ->has('messages.data.0.workItem')
        ->has('filters')
    );
});

test('filtering by work item type returns only matching messages', function () {
    // Create threads for project and task
    $projectThread = CommunicationThread::create([
        'team_id' => $this->team->id,
        'threadable_type' => Project::class,
        'threadable_id' => $this->project->id,
    ]);

    $taskThread = CommunicationThread::create([
        'team_id' => $this->team->id,
        'threadable_type' => Task::class,
        'threadable_id' => $this->task->id,
    ]);

    Message::create([
        'communication_thread_id' => $projectThread->id,
        'author_id' => $this->owner->id,
        'author_type' => 'human',
        'content' => 'Project specific message',
        'type' => MessageType::Note,
    ]);

    Message::create([
        'communication_thread_id' => $taskThread->id,
        'author_id' => $this->owner->id,
        'author_type' => 'human',
        'content' => 'Task specific message',
        'type' => MessageType::Note,
    ]);

    // Filter by project type
    $response = $this->actingAs($this->owner)
        ->get(route('communications.index', ['type' => 'project']));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('communications/index')
        ->has('messages.data', 1)
        ->where('messages.data.0.content', 'Project specific message')
    );

    // Filter by task type
    $response = $this->actingAs($this->owner)
        ->get(route('communications.index', ['type' => 'task']));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('communications/index')
        ->has('messages.data', 1)
        ->where('messages.data.0.content', 'Task specific message')
    );
});

test('filtering by message type returns only matching messages', function () {
    $projectThread = CommunicationThread::create([
        'team_id' => $this->team->id,
        'threadable_type' => Project::class,
        'threadable_id' => $this->project->id,
    ]);

    Message::create([
        'communication_thread_id' => $projectThread->id,
        'author_id' => $this->owner->id,
        'author_type' => 'human',
        'content' => 'This is a note',
        'type' => MessageType::Note,
    ]);

    Message::create([
        'communication_thread_id' => $projectThread->id,
        'author_id' => $this->owner->id,
        'author_type' => 'human',
        'content' => 'This is a question',
        'type' => MessageType::Question,
    ]);

    Message::create([
        'communication_thread_id' => $projectThread->id,
        'author_id' => $this->owner->id,
        'author_type' => 'human',
        'content' => 'This is a status update',
        'type' => MessageType::StatusUpdate,
    ]);

    // Filter by question type
    $response = $this->actingAs($this->owner)
        ->get(route('communications.index', ['message_type' => 'question']));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('communications/index')
        ->has('messages.data', 1)
        ->where('messages.data.0.content', 'This is a question')
    );
});

test('full-text search on message content returns matching messages', function () {
    $projectThread = CommunicationThread::create([
        'team_id' => $this->team->id,
        'threadable_type' => Project::class,
        'threadable_id' => $this->project->id,
    ]);

    Message::create([
        'communication_thread_id' => $projectThread->id,
        'author_id' => $this->owner->id,
        'author_type' => 'human',
        'content' => 'The deadline for the homepage redesign is next week',
        'type' => MessageType::Note,
    ]);

    Message::create([
        'communication_thread_id' => $projectThread->id,
        'author_id' => $this->owner->id,
        'author_type' => 'human',
        'content' => 'Budget review meeting scheduled for Friday',
        'type' => MessageType::Note,
    ]);

    Message::create([
        'communication_thread_id' => $projectThread->id,
        'author_id' => $this->owner->id,
        'author_type' => 'human',
        'content' => 'Please review the homepage mockups',
        'type' => MessageType::Note,
    ]);

    // Search for "homepage"
    $response = $this->actingAs($this->owner)
        ->get(route('communications.index', ['search' => 'homepage']));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('communications/index')
        ->has('messages.data', 2)
    );

    // Search for "budget"
    $response = $this->actingAs($this->owner)
        ->get(route('communications.index', ['search' => 'budget']));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('communications/index')
        ->has('messages.data', 1)
        ->where('messages.data.0.content', 'Budget review meeting scheduled for Friday')
    );
});
