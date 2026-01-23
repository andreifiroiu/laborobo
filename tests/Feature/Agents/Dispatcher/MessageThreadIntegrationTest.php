<?php

declare(strict_types=1);

use App\Enums\AgentType;
use App\Enums\AuthorType;
use App\Enums\MessageType;
use App\Events\MessageCreated;
use App\Jobs\ProcessDispatcherMention;
use App\Listeners\DispatcherMentionListener;
use App\Models\AgentConfiguration;
use App\Models\AIAgent;
use App\Models\CommunicationThread;
use App\Models\GlobalAISettings;
use App\Models\Message;
use App\Models\MessageMention;
use App\Models\Party;
use App\Models\Project;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\ThreadContextService;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->user = User::factory()->create([
        'capacity_hours_per_week' => 40,
        'current_workload_hours' => 20,
    ]);
    $this->team = $this->user->createTeam(['name' => 'Test Team']);
    $this->user->current_team_id = $this->team->id;
    $this->user->save();

    $this->party = Party::factory()->create(['team_id' => $this->team->id]);
    $this->project = Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $this->party->id,
        'owner_id' => $this->user->id,
        'name' => 'Test Project',
    ]);

    $this->workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
        'title' => 'Test Work Order',
    ]);

    $this->dispatcherAgent = AIAgent::factory()->create([
        'code' => 'dispatcher',
        'name' => 'Dispatcher Agent',
        'type' => AgentType::WorkRouting,
        'description' => 'Routes work to team members based on skills and capacity',
        'capabilities' => ['work_routing', 'skill_matching', 'capacity_analysis'],
    ]);

    $this->config = AgentConfiguration::create([
        'team_id' => $this->team->id,
        'ai_agent_id' => $this->dispatcherAgent->id,
        'enabled' => true,
        'daily_run_limit' => 50,
        'monthly_budget_cap' => 100.00,
        'current_month_spend' => 0.00,
        'daily_spend' => 0.00,
        'can_create_work_orders' => true,
        'can_modify_tasks' => true,
        'can_access_client_data' => true,
        'can_send_emails' => false,
        'can_modify_deliverables' => true,
        'can_access_financial_data' => false,
        'can_modify_playbooks' => true,
    ]);

    GlobalAISettings::create([
        'team_id' => $this->team->id,
        'total_monthly_budget' => 1000.00,
        'current_month_spend' => 0.00,
        'require_approval_external_sends' => true,
        'require_approval_financial' => true,
        'require_approval_contracts' => true,
        'require_approval_scope_changes' => false,
    ]);

    // Create communication thread for the work order
    $this->thread = CommunicationThread::factory()->create([
        'team_id' => $this->team->id,
        'threadable_type' => WorkOrder::class,
        'threadable_id' => $this->workOrder->id,
        'message_count' => 0,
    ]);
});

test('MessageMention can point to AIAgent via mentionable_type', function () {
    // Create a message in the thread
    $message = Message::create([
        'communication_thread_id' => $this->thread->id,
        'author_id' => $this->user->id,
        'author_type' => AuthorType::Human,
        'content' => 'Hey @dispatcher, can you help route this work?',
        'type' => MessageType::Note,
    ]);

    // Create a mention pointing to AIAgent
    $mention = MessageMention::create([
        'message_id' => $message->id,
        'mentionable_type' => AIAgent::class,
        'mentionable_id' => $this->dispatcherAgent->id,
    ]);

    expect($mention->mentionable)->toBeInstanceOf(AIAgent::class);
    expect($mention->mentionable->id)->toBe($this->dispatcherAgent->id);
    expect($mention->mentionable->code)->toBe('dispatcher');
});

test('DispatcherMentionListener detects dispatcher mention and dispatches job', function () {
    Queue::fake();

    // Create a message with dispatcher mention
    $message = Message::create([
        'communication_thread_id' => $this->thread->id,
        'author_id' => $this->user->id,
        'author_type' => AuthorType::Human,
        'content' => 'Hey @dispatcher, please analyze this work order and suggest who should handle it.',
        'type' => MessageType::Note,
    ]);

    // Create mention linking to dispatcher agent
    MessageMention::create([
        'message_id' => $message->id,
        'mentionable_type' => AIAgent::class,
        'mentionable_id' => $this->dispatcherAgent->id,
    ]);

    $this->thread->increment('message_count');

    // Fire the MessageCreated event
    $event = new MessageCreated($message, $this->thread);
    $listener = new DispatcherMentionListener();
    $listener->handle($event);

    // Assert the job was dispatched
    Queue::assertPushed(ProcessDispatcherMention::class, function ($job) use ($message) {
        return $job->message->id === $message->id
            && $job->thread->id === $this->thread->id;
    });
});

test('DispatcherMentionListener does not trigger when no dispatcher mention exists', function () {
    Queue::fake();

    // Create a message WITHOUT dispatcher mention
    $message = Message::create([
        'communication_thread_id' => $this->thread->id,
        'author_id' => $this->user->id,
        'author_type' => AuthorType::Human,
        'content' => 'This is a regular message without any mentions.',
        'type' => MessageType::Note,
    ]);

    $this->thread->increment('message_count');

    // Fire the MessageCreated event
    $event = new MessageCreated($message, $this->thread);
    $listener = new DispatcherMentionListener();
    $listener->handle($event);

    // Assert no job was dispatched
    Queue::assertNotPushed(ProcessDispatcherMention::class);
});

test('ThreadContextService retrieves full thread context in chronological order', function () {
    // Create multiple messages in the thread
    $message1 = Message::create([
        'communication_thread_id' => $this->thread->id,
        'author_id' => $this->user->id,
        'author_type' => AuthorType::Human,
        'content' => 'First message about the project requirements.',
        'type' => MessageType::Note,
        'created_at' => now()->subMinutes(10),
    ]);

    $message2 = Message::create([
        'communication_thread_id' => $this->thread->id,
        'author_id' => $this->user->id,
        'author_type' => AuthorType::Human,
        'content' => 'Second message with more details about the work.',
        'type' => MessageType::Note,
        'created_at' => now()->subMinutes(5),
    ]);

    $message3 = Message::create([
        'communication_thread_id' => $this->thread->id,
        'author_id' => $this->user->id,
        'author_type' => AuthorType::Human,
        'content' => '@dispatcher please help route this work.',
        'type' => MessageType::Note,
        'created_at' => now(),
    ]);

    $this->thread->update(['message_count' => 3]);

    $service = new ThreadContextService();
    $context = $service->getThreadContext($this->thread);

    expect($context)->toBeArray();
    expect($context)->toHaveKey('messages');
    expect($context['messages'])->toHaveCount(3);

    // Verify chronological order (oldest first)
    expect($context['messages'][0]['id'])->toBe($message1->id);
    expect($context['messages'][1]['id'])->toBe($message2->id);
    expect($context['messages'][2]['id'])->toBe($message3->id);

    // Verify message structure includes author information
    expect($context['messages'][0])->toHaveKey('author_name');
    expect($context['messages'][0])->toHaveKey('author_type');
    expect($context['messages'][0])->toHaveKey('content');
});

test('agent response can be created within the same thread', function () {
    // Create the triggering message
    $humanMessage = Message::create([
        'communication_thread_id' => $this->thread->id,
        'author_id' => $this->user->id,
        'author_type' => AuthorType::Human,
        'content' => '@dispatcher please analyze this work order.',
        'type' => MessageType::Note,
    ]);

    $this->thread->increment('message_count');

    // Simulate agent response creation
    $responseContent = json_encode([
        'extracted_requirements' => [
            'title' => ['value' => 'Website Redesign', 'confidence' => 'high'],
            'description' => ['value' => 'Redesign the company website', 'confidence' => 'medium'],
        ],
        'routing_candidates' => [
            [
                'user_id' => $this->user->id,
                'user_name' => $this->user->name,
                'combined_score' => 85.5,
                'confidence' => 'high',
            ],
        ],
    ]);

    $agentMessage = Message::create([
        'communication_thread_id' => $this->thread->id,
        'author_id' => $this->dispatcherAgent->id,
        'author_type' => AuthorType::AiAgent,
        'content' => $responseContent,
        'type' => MessageType::Note,
    ]);

    $this->thread->increment('message_count');

    // Verify agent message was created correctly
    expect($agentMessage->author_type)->toBe(AuthorType::AiAgent);
    expect($agentMessage->author_id)->toBe($this->dispatcherAgent->id);
    expect($agentMessage->communication_thread_id)->toBe($this->thread->id);

    // Verify thread contains both messages
    $this->thread->refresh();
    expect($this->thread->message_count)->toBe(2);
    expect($this->thread->messages()->count())->toBe(2);

    // Verify the response content is structured JSON
    $decodedContent = json_decode($agentMessage->content, true);
    expect($decodedContent)->toHaveKey('extracted_requirements');
    expect($decodedContent)->toHaveKey('routing_candidates');
});

test('ThreadContextService formats thread for agent system prompt injection', function () {
    // Create messages with different authors
    Message::create([
        'communication_thread_id' => $this->thread->id,
        'author_id' => $this->user->id,
        'author_type' => AuthorType::Human,
        'content' => 'We need help with a web development project.',
        'type' => MessageType::Note,
        'created_at' => now()->subMinutes(5),
    ]);

    Message::create([
        'communication_thread_id' => $this->thread->id,
        'author_id' => $this->user->id,
        'author_type' => AuthorType::Human,
        'content' => '@dispatcher can you help route this?',
        'type' => MessageType::Note,
        'created_at' => now(),
    ]);

    $this->thread->update(['message_count' => 2]);

    $service = new ThreadContextService();
    $formattedContext = $service->formatForSystemPrompt($this->thread);

    expect($formattedContext)->toBeString();
    expect($formattedContext)->toContain('web development project');
    expect($formattedContext)->toContain('route this');

    // Should include work order context
    expect($formattedContext)->toContain($this->workOrder->title);
});
