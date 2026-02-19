<?php

declare(strict_types=1);

use App\Contracts\Tools\ToolInterface;
use App\Enums\AgentMemoryScope;
use App\Models\AgentActivityLog;
use App\Models\AgentConfiguration;
use App\Models\AgentMemory;
use App\Models\AIAgent;
use App\Models\GlobalAISettings;
use App\Models\Party;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use App\Services\AgentBudgetService;
use App\Services\AgentMemoryService;
use App\Services\AgentPermissionService;
use App\Services\AgentRunner;
use App\Services\ContextBuilder;
use App\Services\ToolGateway;
use App\Services\ToolRegistry;
use App\ValueObjects\AgentContext;

/**
 * A simple test tool implementation for testing BaseAgent.
 */
class BaseAgentTestTool implements ToolInterface
{
    public function name(): string
    {
        return 'base-agent-test-tool';
    }

    public function description(): string
    {
        return 'A test tool for BaseAgent testing';
    }

    public function category(): string
    {
        return 'tasks';
    }

    public function execute(array $params): array
    {
        return ['executed' => true, 'params' => $params];
    }

    public function getParameters(): array
    {
        return [
            'input' => [
                'type' => 'string',
                'description' => 'Input parameter',
                'required' => true,
            ],
        ];
    }
}

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
        'name' => 'Test Project',
        'description' => 'A test project for agent context building',
    ]);

    $this->agent = AIAgent::factory()->create([
        'code' => 'test-base-agent',
        'name' => 'Test Base Agent',
        'description' => 'Test agent for abstraction layer',
        'tools' => ['testing'],
    ]);

    $this->config = AgentConfiguration::create([
        'team_id' => $this->team->id,
        'ai_agent_id' => $this->agent->id,
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
        'can_modify_playbooks' => false,
    ]);

    $this->globalSettings = GlobalAISettings::create([
        'team_id' => $this->team->id,
        'total_monthly_budget' => 1000.00,
        'current_month_spend' => 0.00,
        'require_approval_external_sends' => true,
        'require_approval_financial' => true,
        'require_approval_contracts' => true,
        'require_approval_scope_changes' => false,
    ]);

    // Set up services
    $this->registry = new ToolRegistry();
    $this->permissionService = new AgentPermissionService();
    $this->budgetService = new AgentBudgetService();
    $this->gateway = new ToolGateway(
        $this->registry,
        $this->permissionService,
        $this->budgetService
    );
    $this->memoryService = new AgentMemoryService();
    $this->contextBuilder = new ContextBuilder($this->memoryService);
    $this->agentRunner = new AgentRunner(
        $this->gateway,
        $this->budgetService,
        $this->contextBuilder
    );
});

test('context builder assembles project-level context correctly', function () {
    // Create some work orders and tasks for the project
    $workOrder = \App\Models\WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'title' => 'Test Work Order',
    ]);

    $task = \App\Models\Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $workOrder->id,
        'project_id' => $this->project->id,
        'title' => 'Test Task',
    ]);

    $context = $this->contextBuilder->build($this->project, $this->agent);

    expect($context)->toBeInstanceOf(AgentContext::class);
    expect($context->projectContext)->toBeArray();
    expect($context->projectContext)->toHaveKey('name');
    expect($context->projectContext['name'])->toBe('Test Project');
});

test('context builder respects token limits with truncation', function () {
    // Create multiple work orders to generate significant context
    for ($i = 0; $i < 10; $i++) {
        \App\Models\WorkOrder::factory()->create([
            'team_id' => $this->team->id,
            'project_id' => $this->project->id,
            'title' => "Work Order {$i} with a longer description to generate more tokens",
            'description' => str_repeat("Description content for work order {$i}. ", 50),
        ]);
    }

    // Build context with a low token limit
    $context = $this->contextBuilder->build($this->project, $this->agent, maxTokens: 500);

    expect($context)->toBeInstanceOf(AgentContext::class);
    expect($context->getTokenEstimate())->toBeLessThanOrEqual(500);
});

test('agent memory service stores and retrieves values correctly', function () {
    $this->memoryService->store(
        $this->team,
        AgentMemoryScope::Project->value,
        $this->project->id,
        'test_key',
        ['data' => 'test value'],
        ttlMinutes: 60
    );

    $retrieved = $this->memoryService->retrieve(
        $this->team,
        AgentMemoryScope::Project->value,
        $this->project->id,
        'test_key'
    );

    expect($retrieved)->toBe(['data' => 'test value']);

    // Verify the memory record exists in database
    $memory = AgentMemory::where('team_id', $this->team->id)
        ->where('scope', AgentMemoryScope::Project)
        ->where('key', 'test_key')
        ->first();

    expect($memory)->not->toBeNull();
    expect($memory->expires_at)->not->toBeNull();
});

test('agent memory service handles expiration correctly', function () {
    // Store a memory that is already expired
    AgentMemory::create([
        'team_id' => $this->team->id,
        'ai_agent_id' => $this->agent->id,
        'scope' => AgentMemoryScope::Project,
        'scope_type' => Project::class,
        'scope_id' => $this->project->id,
        'key' => 'expired_key',
        'value' => ['data' => 'expired value'],
        'expires_at' => now()->subMinutes(10),
    ]);

    $retrieved = $this->memoryService->retrieve(
        $this->team,
        AgentMemoryScope::Project->value,
        $this->project->id,
        'expired_key'
    );

    expect($retrieved)->toBeNull();
});

test('agent memory service can forget stored values', function () {
    $this->memoryService->store(
        $this->team,
        AgentMemoryScope::Client->value,
        $this->party->id,
        'forget_key',
        ['data' => 'to be forgotten']
    );

    // Verify it exists
    $exists = $this->memoryService->retrieve(
        $this->team,
        AgentMemoryScope::Client->value,
        $this->party->id,
        'forget_key'
    );
    expect($exists)->not->toBeNull();

    // Forget it
    $this->memoryService->forget(
        $this->team,
        AgentMemoryScope::Client->value,
        $this->party->id,
        'forget_key'
    );

    // Verify it's gone
    $forgotten = $this->memoryService->retrieve(
        $this->team,
        AgentMemoryScope::Client->value,
        $this->party->id,
        'forget_key'
    );
    expect($forgotten)->toBeNull();
});

test('context builder builds client context from party', function () {
    $clientContext = $this->contextBuilder->buildClientContext($this->party);

    expect($clientContext)->toBeArray();
    expect($clientContext)->toHaveKey('name');
    expect($clientContext)->toHaveKey('type');
});

test('context builder builds org context from team', function () {
    $orgContext = $this->contextBuilder->buildOrgContext($this->team);

    expect($orgContext)->toBeArray();
    expect($orgContext)->toHaveKey('name');
});

test('agent context value object converts to prompt string', function () {
    $context = new AgentContext(
        projectContext: ['name' => 'Test Project', 'status' => 'active'],
        clientContext: ['name' => 'Test Client', 'type' => 'client'],
        orgContext: ['name' => 'Test Org'],
        metadata: ['entity_type' => 'project', 'entity_id' => 1]
    );

    $promptString = $context->toPromptString();

    expect($promptString)->toBeString();
    expect($promptString)->toContain('Project Context');
    expect($promptString)->toContain('Test Project');
    expect($promptString)->toContain('Client Context');
    expect($promptString)->toContain('Organization Context');
});
