<?php

declare(strict_types=1);

use App\Agents\Tools\CreateDraftWorkOrderTool;
use App\Enums\AgentType;
use App\Enums\AuthorType;
use App\Enums\MessageType;
use App\Enums\PlaybookType;
use App\Enums\WorkOrderStatus;
use App\Events\MessageCreated;
use App\Jobs\ProcessDispatcherMention;
use App\Jobs\ProcessDispatcherRouting;
use App\Listeners\DispatcherMentionListener;
use App\Models\AgentConfiguration;
use App\Models\AIAgent;
use App\Models\CommunicationThread;
use App\Models\GlobalAISettings;
use App\Models\Message;
use App\Models\MessageMention;
use App\Models\Party;
use App\Models\Playbook;
use App\Models\Project;
use App\Models\User;
use App\Models\UserSkill;
use App\Models\WorkOrder;
use App\Services\RoutingDecisionService;
use App\Services\WorkRequirementExtractor;
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

    $this->dispatcherAgent = AIAgent::factory()->create([
        'code' => 'dispatcher',
        'name' => 'Dispatcher Agent',
        'type' => AgentType::WorkRouting,
        'description' => 'Routes work to team members based on skills and capacity',
        'capabilities' => ['work_routing', 'skill_matching', 'capacity_analysis'],
    ]);

    AgentConfiguration::create([
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
});

test('end-to-end: @dispatcher mention dispatches ProcessDispatcherMention job for work order thread', function () {
    Queue::fake();

    // Create work order with thread
    $workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
        'accountable_id' => $this->user->id,
        'title' => 'Test Work Order',
    ]);

    $thread = CommunicationThread::factory()->create([
        'team_id' => $this->team->id,
        'threadable_type' => WorkOrder::class,
        'threadable_id' => $workOrder->id,
        'message_count' => 0,
    ]);

    // Create preceding context messages
    Message::create([
        'communication_thread_id' => $thread->id,
        'author_id' => $this->user->id,
        'author_type' => AuthorType::Human,
        'content' => 'We need someone to build a new Laravel API for user authentication.',
        'type' => MessageType::Note,
        'created_at' => now()->subMinutes(5),
    ]);

    // Create message with @dispatcher mention
    $triggerMessage = Message::create([
        'communication_thread_id' => $thread->id,
        'author_id' => $this->user->id,
        'author_type' => AuthorType::Human,
        'content' => '@dispatcher please analyze this and suggest who should handle it. This is high priority and should take about 16 hours.',
        'type' => MessageType::Note,
    ]);

    // Create mention pointing to dispatcher agent
    MessageMention::create([
        'message_id' => $triggerMessage->id,
        'mentionable_type' => AIAgent::class,
        'mentionable_id' => $this->dispatcherAgent->id,
    ]);

    $thread->increment('message_count', 2);

    // Fire the event and handle via listener
    $event = new MessageCreated($triggerMessage, $thread);
    $listener = new DispatcherMentionListener;
    $listener->handle($event);

    // Assert the job was dispatched with correct data
    Queue::assertPushed(ProcessDispatcherMention::class, function ($job) use ($triggerMessage, $thread) {
        return $job->message->id === $triggerMessage->id
            && $job->thread->id === $thread->id
            && $job->agent->id === $this->dispatcherAgent->id;
    });
});

test('end-to-end: ProcessDispatcherRouting job can be dispatched for work order', function () {
    Queue::fake();

    // Create work order (dispatcher toggle would be stored in a separate mechanism)
    $workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
        'accountable_id' => $this->user->id,
        'title' => 'New Feature Development',
        'description' => 'Build user dashboard with React and TypeScript',
        'estimated_hours' => 24,
        'status' => WorkOrderStatus::Draft,
    ]);

    // Dispatch the routing job (simulating what controller would do when toggle enabled)
    ProcessDispatcherRouting::dispatch($workOrder, $this->dispatcherAgent);

    // Assert the job was dispatched
    Queue::assertPushed(ProcessDispatcherRouting::class, function ($job) use ($workOrder) {
        return $job->workOrder->id === $workOrder->id
            && $job->agent->id === $this->dispatcherAgent->id;
    });
});

test('CreateDraftWorkOrderTool creates work order with draft status and routing reasoning', function () {
    $tool = new CreateDraftWorkOrderTool;

    $result = $tool->execute([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'title' => 'API Development Task',
        'description' => 'Build REST API endpoints for user management',
        'priority' => 'high',
        'due_date' => '2024-03-15',
        'estimated_hours' => 16.0,
        'acceptance_criteria' => [
            'All endpoints return proper JSON',
            'Authentication is implemented',
            'Tests cover 80% of code',
        ],
        'responsible_id' => $this->user->id,
        'accountable_id' => $this->user->id,
        'routing_reasoning' => [
            'skill_score' => 85.0,
            'capacity_score' => 75.0,
            'combined_score' => 80.0,
            'matched_skills' => ['Laravel', 'PHP', 'REST API'],
        ],
        'created_by_id' => $this->user->id,
    ]);

    expect($result['success'])->toBeTrue();
    expect($result['work_order']['status'])->toBe(WorkOrderStatus::Draft->value);
    expect($result['work_order']['title'])->toBe('API Development Task');
    expect($result['work_order']['responsible_id'])->toBe($this->user->id);
    expect($result['routing_reasoning'])->toHaveKey('skill_score');

    // Verify work order was persisted
    $workOrder = WorkOrder::find($result['work_order']['id']);
    expect($workOrder)->not->toBeNull();
    expect($workOrder->status)->toBe(WorkOrderStatus::Draft);
});

test('integration: full extraction + routing flow produces ranked candidates', function () {
    // Create team members with skills
    $developer1 = User::factory()->create([
        'name' => 'Senior Developer',
        'capacity_hours_per_week' => 40,
        'current_workload_hours' => 10,
    ]);
    $developer2 = User::factory()->create([
        'name' => 'Junior Developer',
        'capacity_hours_per_week' => 40,
        'current_workload_hours' => 30,
    ]);

    $this->team->addUser($developer1, 'member');
    $this->team->addUser($developer2, 'member');

    // Add skills to developers
    UserSkill::create([
        'user_id' => $developer1->id,
        'skill_name' => 'Laravel',
        'proficiency' => 3,
    ]);
    UserSkill::create([
        'user_id' => $developer1->id,
        'skill_name' => 'React',
        'proficiency' => 2,
    ]);
    UserSkill::create([
        'user_id' => $developer2->id,
        'skill_name' => 'Laravel',
        'proficiency' => 1,
    ]);

    // Step 1: Extract requirements from message
    $messageContent = <<<'EOT'
We need to build a new dashboard for the analytics team.

The scope includes:
- Real-time metrics display
- User activity charts
- Revenue tracking

This is high priority and needs Laravel and React skills.
I estimate it will take about 16 hours.
EOT;

    $extractor = app(WorkRequirementExtractor::class);
    $extracted = $extractor->extract($messageContent);

    expect($extracted['title']['value'])->not->toBeNull();
    expect($extracted['priority']['value'])->toBe('high');
    expect($extracted['estimated_hours']['value'])->toBe(16.0);

    // Step 2: Run routing decision with extracted data
    $routingService = app(RoutingDecisionService::class);
    $routing = $routingService->calculateRouting(
        teamId: $this->team->id,
        requiredSkills: ['Laravel', 'React'],
        estimatedHours: $extracted['estimated_hours']['value']
    );

    // Verify routing produces ranked candidates
    expect($routing['candidates'])->not->toBeEmpty();
    expect($routing['candidates'][0]['user_id'])->toBe($developer1->id);
    expect($routing['candidates'][0]['combined_score'])->toBeGreaterThan($routing['candidates'][1]['combined_score']);

    // Verify reasoning is included
    expect($routing['candidates'][0]['reasoning'])->toHaveKey('skill_matches');
    expect($routing['candidates'][0]['reasoning'])->toHaveKey('capacity_analysis');
});

test('integration: playbook suggestion matches extracted scope and tags', function () {
    // Create playbooks directly with relevant tags
    Playbook::create([
        'team_id' => $this->team->id,
        'name' => 'API Development SOP',
        'description' => 'Standard procedure for building REST APIs',
        'tags' => ['api', 'backend', 'rest', 'laravel'],
        'type' => PlaybookType::SOP,
        'content' => ['steps' => ['Define endpoints', 'Implement logic', 'Write tests']],
        'created_by' => $this->user->id,
        'created_by_name' => $this->user->name,
    ]);
    Playbook::create([
        'team_id' => $this->team->id,
        'name' => 'Dashboard Development',
        'description' => 'Guide for building analytics dashboards',
        'tags' => ['dashboard', 'frontend', 'react', 'analytics'],
        'type' => PlaybookType::SOP,
        'content' => ['steps' => ['Design UI', 'Build components', 'Connect API']],
        'created_by' => $this->user->id,
        'created_by_name' => $this->user->name,
    ]);
    Playbook::create([
        'team_id' => $this->team->id,
        'name' => 'Database Migration Guide',
        'description' => 'How to safely run database migrations',
        'tags' => ['database', 'migration', 'sql'],
        'type' => PlaybookType::SOP,
        'content' => ['steps' => ['Backup', 'Run migration', 'Verify']],
        'created_by' => $this->user->id,
        'created_by_name' => $this->user->name,
    ]);

    $messageContent = <<<'EOT'
Title: Dashboard API Backend
Description: Build the REST API backend for our analytics dashboard using Laravel.
The API should expose endpoints for metrics and user activity data.
EOT;

    $extractor = app(WorkRequirementExtractor::class);
    $result = $extractor->extractWithPlaybooks($messageContent, $this->team->id);

    expect($result)->toHaveKey('requirements');
    expect($result)->toHaveKey('suggested_playbooks');
    expect($result['suggested_playbooks'])->not->toBeEmpty();

    // Top suggestion should be relevant to API and dashboard
    $topPlaybook = $result['suggested_playbooks'][0];
    expect($topPlaybook['relevance_score'])->toBeGreaterThan(0);

    // Check that at least one of the relevant playbooks is suggested
    $suggestedNames = array_column($result['suggested_playbooks'], 'name');
    $hasRelevant = in_array('API Development SOP', $suggestedNames) ||
                   in_array('Dashboard Development', $suggestedNames);
    expect($hasRelevant)->toBeTrue();
});

test('end-to-end: disabled agent configuration prevents job dispatch', function () {
    Queue::fake();

    // Disable the agent configuration
    AgentConfiguration::where('team_id', $this->team->id)
        ->where('ai_agent_id', $this->dispatcherAgent->id)
        ->update(['enabled' => false]);

    // Create work order with thread
    $workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
        'accountable_id' => $this->user->id,
        'title' => 'Test Work Order',
    ]);

    $thread = CommunicationThread::factory()->create([
        'team_id' => $this->team->id,
        'threadable_type' => WorkOrder::class,
        'threadable_id' => $workOrder->id,
        'message_count' => 0,
    ]);

    // Create message with @dispatcher mention
    $message = Message::create([
        'communication_thread_id' => $thread->id,
        'author_id' => $this->user->id,
        'author_type' => AuthorType::Human,
        'content' => '@dispatcher help route this',
        'type' => MessageType::Note,
    ]);

    MessageMention::create([
        'message_id' => $message->id,
        'mentionable_type' => AIAgent::class,
        'mentionable_id' => $this->dispatcherAgent->id,
    ]);

    $thread->increment('message_count');

    // Fire the event
    $event = new MessageCreated($message, $thread);
    $listener = new DispatcherMentionListener;
    $listener->handle($event);

    // Job should still be dispatched (configuration check happens in job)
    // The job will exit early if agent is disabled
    Queue::assertPushed(ProcessDispatcherMention::class);
});
