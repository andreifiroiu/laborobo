<?php

declare(strict_types=1);

use App\Contracts\Tools\ToolInterface;
use App\Models\AgentConfiguration;
use App\Models\AIAgent;
use App\Models\GlobalAISettings;
use App\Models\Team;
use App\Models\User;
use App\Services\AgentBudgetService;
use App\Services\AgentPermissionService;
use App\Services\ToolGateway;
use App\Services\ToolRegistry;

/**
 * A simple test tool implementation for testing permissions.
 */
class PermissionTestTool implements ToolInterface
{
    public function __construct(
        private readonly string $toolName = 'permission-test-tool',
        private readonly string $toolCategory = 'tasks',
    ) {}

    public function name(): string
    {
        return $this->toolName;
    }

    public function description(): string
    {
        return 'A test tool for permission testing';
    }

    public function category(): string
    {
        return $this->toolCategory;
    }

    public function execute(array $params): array
    {
        return ['result' => 'executed'];
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
        'current_month_spend' => 0.00,
        'daily_spend' => 0.00,
        'can_create_work_orders' => true,
        'can_modify_tasks' => true,
        'can_access_client_data' => false,
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

    $this->permissionService = new AgentPermissionService();
    $this->budgetService = new AgentBudgetService();
    $this->registry = new ToolRegistry();
});

test('permission service allows tool execution when agent has required category permission', function () {
    $taskTool = new PermissionTestTool('task-tool', 'tasks');
    $this->registry->register($taskTool);

    // Config has can_modify_tasks = true
    $canExecute = $this->permissionService->canExecuteTool($this->config, $taskTool);

    expect($canExecute)->toBeTrue();
});

test('permission service blocks tool execution when agent lacks required category permission', function () {
    $financialTool = new PermissionTestTool('financial-tool', 'financial');
    $this->registry->register($financialTool);

    // Config has can_access_financial_data = false
    $canExecute = $this->permissionService->canExecuteTool($this->config, $financialTool);

    expect($canExecute)->toBeFalse();
});

test('permission service checks new permission categories correctly', function () {
    // Test deliverables permission (enabled)
    $deliverableTool = new PermissionTestTool('deliverable-tool', 'deliverables');
    expect($this->permissionService->canExecuteTool($this->config, $deliverableTool))->toBeTrue();

    // Test financial permission (disabled)
    $financialTool = new PermissionTestTool('financial-tool', 'financial');
    expect($this->permissionService->canExecuteTool($this->config, $financialTool))->toBeFalse();

    // Test playbooks permission (disabled)
    $playbookTool = new PermissionTestTool('playbook-tool', 'playbooks');
    expect($this->permissionService->canExecuteTool($this->config, $playbookTool))->toBeFalse();
});

test('permission service identifies system-level approval requirements', function () {
    // External sends require approval (set to true in beforeEach)
    expect($this->permissionService->requiresHumanApproval('external_sends', $this->globalSettings))
        ->toBeTrue();

    // Financial requires approval
    expect($this->permissionService->requiresHumanApproval('financial', $this->globalSettings))
        ->toBeTrue();

    // Contracts require approval
    expect($this->permissionService->requiresHumanApproval('contracts', $this->globalSettings))
        ->toBeTrue();

    // Scope changes do not require approval (set to false)
    expect($this->permissionService->requiresHumanApproval('scope_changes', $this->globalSettings))
        ->toBeFalse();
});

test('budget service allows run when daily spend is under cap', function () {
    // Config has monthly_budget_cap = 100.00 and daily_spend = 0.00
    $canRun = $this->budgetService->canRun($this->config, 10.00);

    expect($canRun)->toBeTrue();
    expect($this->budgetService->getDailyRemaining($this->config))->toBe(100.00);
});

test('budget service rejects run when daily spend exceeds cap', function () {
    // Set daily spend near the cap
    $this->config->daily_spend = 95.00;
    $this->config->save();
    $this->config->refresh();

    // Try to run with cost that would exceed cap
    $canRun = $this->budgetService->canRun($this->config, 10.00);

    expect($canRun)->toBeFalse();
    expect($this->budgetService->getDailyRemaining($this->config))->toBe(5.00);
});

test('budget service validates monthly budget correctly', function () {
    // Set current month spend
    $this->config->current_month_spend = 80.00;
    $this->config->save();
    $this->config->refresh();

    // Can run with cost under remaining
    expect($this->budgetService->canRun($this->config, 15.00))->toBeTrue();

    // Cannot run with cost over remaining
    expect($this->budgetService->canRun($this->config, 25.00))->toBeFalse();

    expect($this->budgetService->getMonthlyRemaining($this->config))->toBe(20.00);
});

test('budget service deducts cost correctly', function () {
    $this->budgetService->deductCost($this->config, 15.50);

    $this->config->refresh();

    expect((float) $this->config->daily_spend)->toBe(15.50);
    expect((float) $this->config->current_month_spend)->toBe(15.50);
});
