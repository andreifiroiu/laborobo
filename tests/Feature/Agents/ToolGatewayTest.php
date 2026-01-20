<?php

declare(strict_types=1);

use App\Contracts\Tools\ToolInterface;
use App\Models\AgentActivityLog;
use App\Models\AgentConfiguration;
use App\Models\AIAgent;
use App\Models\Team;
use App\Models\User;
use App\Services\ToolGateway;
use App\Services\ToolRegistry;
use App\ValueObjects\ToolResult;

/**
 * A simple test tool implementation for testing purposes.
 */
class TestTool implements ToolInterface
{
    public function __construct(
        private readonly string $toolName = 'test-tool',
        private readonly string $toolCategory = 'tasks',
    ) {}

    public function name(): string
    {
        return $this->toolName;
    }

    public function description(): string
    {
        return 'A test tool for unit testing';
    }

    public function category(): string
    {
        return $this->toolCategory;
    }

    public function execute(array $params): array
    {
        return [
            'received_params' => $params,
            'result' => 'success',
        ];
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

/**
 * A test tool that throws an exception for error testing.
 */
class FailingTestTool implements ToolInterface
{
    public function name(): string
    {
        return 'failing-tool';
    }

    public function description(): string
    {
        return 'A tool that always fails';
    }

    public function category(): string
    {
        return 'general';
    }

    public function execute(array $params): array
    {
        throw new RuntimeException('Tool execution failed intentionally');
    }

    public function getParameters(): array
    {
        return [];
    }
}

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->team = $this->user->createTeam(['name' => 'Test Team']);
    $this->user->current_team_id = $this->team->id;
    $this->user->save();

    $this->agent = AIAgent::factory()->create();
    $this->config = AgentConfiguration::create([
        'team_id' => $this->team->id,
        'ai_agent_id' => $this->agent->id,
        'enabled' => true,
        'daily_run_limit' => 50,
        'monthly_budget_cap' => 100.00,
        'can_create_work_orders' => false,
        'can_modify_tasks' => true,
        'can_access_client_data' => false,
        'can_send_emails' => false,
        'can_modify_deliverables' => false,
        'can_access_financial_data' => false,
        'can_modify_playbooks' => false,
    ]);

    // Get fresh instances of the services
    $this->registry = new ToolRegistry();
    $this->gateway = new ToolGateway($this->registry);
});

test('tool can be registered via service container registry', function () {
    $testTool = new TestTool();

    $this->registry->register($testTool);

    expect($this->registry->has('test-tool'))->toBeTrue();
    expect($this->registry->get('test-tool'))->toBeInstanceOf(ToolInterface::class);
    expect($this->registry->get('test-tool')->name())->toBe('test-tool');
    expect($this->registry->count())->toBe(1);
});

test('permission check allows execution when agent has required permissions', function () {
    $testTool = new TestTool('task-tool', 'tasks');
    $this->registry->register($testTool);

    // Config has can_modify_tasks = true
    expect($this->gateway->hasPermission($this->config, $testTool))->toBeTrue();

    // Execute the tool
    $result = $this->gateway->execute(
        $this->agent,
        $this->config,
        'task-tool',
        ['input' => 'test data']
    );

    expect($result)->toBeInstanceOf(ToolResult::class);
    expect($result->success)->toBeTrue();
    expect($result->status)->toBe('success');
    expect($result->data['result'])->toBe('success');
});

test('permission check blocks execution when agent lacks required permissions', function () {
    // Create a tool that requires work_orders permission (which config doesn't have)
    $workOrderTool = new TestTool('work-order-tool', 'work_orders');
    $this->registry->register($workOrderTool);

    // Config has can_create_work_orders = false
    expect($this->gateway->hasPermission($this->config, $workOrderTool))->toBeFalse();

    // Execute should return denied result
    $result = $this->gateway->execute(
        $this->agent,
        $this->config,
        'work-order-tool',
        ['input' => 'test data']
    );

    expect($result)->toBeInstanceOf(ToolResult::class);
    expect($result->success)->toBeFalse();
    expect($result->status)->toBe('denied');
    expect($result->isDenied())->toBeTrue();
    expect($result->error)->toContain('Permission denied');
});

test('tool execution is logged to agent activity log', function () {
    $testTool = new TestTool('logged-tool', 'tasks');
    $this->registry->register($testTool);

    // Execute the tool
    $result = $this->gateway->execute(
        $this->agent,
        $this->config,
        'logged-tool',
        ['input' => 'log test']
    );

    expect($result->success)->toBeTrue();

    // Verify activity log was created
    $activityLog = AgentActivityLog::where('ai_agent_id', $this->agent->id)
        ->where('team_id', $this->team->id)
        ->latest()
        ->first();

    expect($activityLog)->not->toBeNull();
    expect($activityLog->run_type)->toBe('tool_execution');
    expect($activityLog->tool_calls)->toBeArray();
    expect($activityLog->tool_calls[0]['tool'])->toBe('logged-tool');
    expect($activityLog->tool_calls[0]['status'])->toBe('success');
});

test('denied tool execution is also logged to activity log', function () {
    $emailTool = new TestTool('email-tool', 'email');
    $this->registry->register($emailTool);

    // Config has can_send_emails = false, so this should be denied
    $result = $this->gateway->execute(
        $this->agent,
        $this->config,
        'email-tool',
        ['input' => 'denied test']
    );

    expect($result->isDenied())->toBeTrue();

    // Verify denied execution was logged
    $activityLog = AgentActivityLog::where('ai_agent_id', $this->agent->id)
        ->where('team_id', $this->team->id)
        ->latest()
        ->first();

    expect($activityLog)->not->toBeNull();
    expect($activityLog->tool_calls[0]['status'])->toBe('denied');
    expect($activityLog->error)->toContain('Permission denied');
});

test('tool interface contract is enforced via type hints', function () {
    // This test verifies that ToolInterface contract is properly typed
    $testTool = new TestTool();

    expect($testTool)->toBeInstanceOf(ToolInterface::class);
    expect($testTool->name())->toBeString();
    expect($testTool->description())->toBeString();
    expect($testTool->category())->toBeString();
    expect($testTool->getParameters())->toBeArray();
    expect($testTool->execute(['input' => 'test']))->toBeArray();
});

test('JSON tool configuration can be loaded from file', function () {
    $this->registry->loadDefinitionsFromDirectory(config_path('agent-tools'));

    // Check that sample-tool.json was loaded
    $definition = $this->registry->getDefinition('sample-tool');

    expect($definition)->not->toBeNull();
    expect($definition['name'])->toBe('sample-tool');
    expect($definition['category'])->toBe('general');
    expect($definition['description'])->toBe('A sample tool for testing the tool gateway system');
    expect($definition['parameters'])->toBeArray();
    expect($definition['required_permissions'])->toBeArray();
});

test('tool result value object has correct named constructors', function () {
    // Test success constructor
    $successResult = ToolResult::success(['data' => 'value'], 100);
    expect($successResult->success)->toBeTrue();
    expect($successResult->status)->toBe('success');
    expect($successResult->data)->toBe(['data' => 'value']);
    expect($successResult->executionTimeMs)->toBe(100);
    expect($successResult->error)->toBeNull();

    // Test failure constructor
    $failureResult = ToolResult::failure('Something went wrong', 50);
    expect($failureResult->success)->toBeFalse();
    expect($failureResult->status)->toBe('failed');
    expect($failureResult->error)->toBe('Something went wrong');
    expect($failureResult->isFailed())->toBeTrue();

    // Test denied constructor
    $deniedResult = ToolResult::denied('Permission denied');
    expect($deniedResult->success)->toBeFalse();
    expect($deniedResult->status)->toBe('denied');
    expect($deniedResult->isDenied())->toBeTrue();
    expect($deniedResult->executionTimeMs)->toBe(0);
});
