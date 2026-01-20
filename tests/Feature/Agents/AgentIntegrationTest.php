<?php

declare(strict_types=1);

use App\Agents\Tools\TaskListTool;
use App\Contracts\Tools\ToolInterface;
use App\Enums\AgentType;
use App\Enums\InboxItemType;
use App\Enums\SourceType;
use App\Enums\Urgency;
use App\Models\AgentActivityLog;
use App\Models\AgentConfiguration;
use App\Models\AgentTemplate;
use App\Models\AgentWorkflowState;
use App\Models\AIAgent;
use App\Models\GlobalAISettings;
use App\Models\InboxItem;
use App\Models\Party;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\AgentApprovalService;
use App\Services\AgentBudgetService;
use App\Services\AgentOrchestrator;
use App\Services\AgentPermissionService;
use App\Services\ToolGateway;
use App\Services\ToolRegistry;
use App\ValueObjects\ToolResult;

/**
 * A test tool that throws an exception for failure testing.
 */
class FailingIntegrationTool implements ToolInterface
{
    public function name(): string
    {
        return 'failing-integration-tool';
    }

    public function description(): string
    {
        return 'A tool that always fails for testing failure handling';
    }

    public function category(): string
    {
        return 'tasks';
    }

    public function execute(array $params): array
    {
        throw new RuntimeException('Simulated tool failure for integration testing');
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

    $this->agent = AIAgent::factory()->create([
        'code' => 'integration-test-agent',
        'name' => 'Integration Test Agent',
        'type' => AgentType::ProjectManagement,
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
});

describe('End-to-End Workflow Integration', function () {
    test('complete flow: tool permission denied creates inbox item, approval resumes workflow', function () {
        // Create a paused workflow state awaiting approval for a denied action
        $state = AgentWorkflowState::create([
            'team_id' => $this->team->id,
            'ai_agent_id' => $this->agent->id,
            'workflow_class' => 'App\\Agents\\Workflows\\EmailWorkflow',
            'current_node' => 'send_email',
            'state_data' => [
                'recipient' => 'client@example.com',
                'subject' => 'Project Update',
                'denied_tool' => 'send-email',
                'denial_reason' => 'Agent lacks can_send_emails permission',
            ],
            'approval_required' => false,
        ]);

        // Request approval (simulating what happens after permission denial)
        $approvalService = app(AgentApprovalService::class);
        $inboxItem = $approvalService->requestApproval(
            $state,
            'Send email to client@example.com regarding Project Update'
        );

        // Verify inbox item was created correctly
        expect($inboxItem)->toBeInstanceOf(InboxItem::class);
        expect($inboxItem->type)->toBe(InboxItemType::Approval);
        expect($inboxItem->source_type)->toBe(SourceType::AIAgent);
        expect($inboxItem->approvable_type)->toBe(AgentWorkflowState::class);
        expect($inboxItem->approvable_id)->toBe($state->id);

        // Verify workflow is now paused
        $state->refresh();
        expect($state->isPaused())->toBeTrue();
        expect($state->approval_required)->toBeTrue();

        // Simulate approval
        $approvalService->handleApproval($inboxItem, $this->user);

        // Verify workflow was resumed
        $state->refresh();
        expect($state->isPaused())->toBeFalse();
        expect($state->resumed_at)->not->toBeNull();
        expect($state->state_data['approval_data']['approved'])->toBeTrue();
        expect($state->state_data['approval_data']['approver_id'])->toBe($this->user->id);

        // Verify inbox item is marked as approved
        $inboxItem->refresh();
        expect($inboxItem->approved_at)->not->toBeNull();
    });

    test('agent creation from template via API inherits all permissions correctly', function () {
        // Create a template with comprehensive permissions
        $template = AgentTemplate::create([
            'code' => 'full-permission-template',
            'name' => 'Full Permission Template',
            'type' => AgentType::ProjectManagement,
            'description' => 'Template with all permissions enabled',
            'default_instructions' => 'You have full access.',
            'default_tools' => ['task-list', 'work-order-info', 'create-note'],
            'default_permissions' => [
                'can_create_work_orders' => true,
                'can_modify_tasks' => true,
                'can_access_client_data' => true,
                'can_send_emails' => false,
                'can_modify_deliverables' => true,
                'can_access_financial_data' => false,
                'can_modify_playbooks' => true,
            ],
            'is_active' => true,
        ]);

        // Create agent via API endpoint
        $response = $this->actingAs($this->user)->post('/settings/agents', [
            'template_id' => $template->id,
            'name' => 'Inherited Permissions Agent',
            'description' => 'Agent testing permission inheritance',
        ]);

        $response->assertRedirect();

        // Get the created agent
        $agent = AIAgent::where('name', 'Inherited Permissions Agent')->first();
        expect($agent)->not->toBeNull();
        expect($agent->template_id)->toBe($template->id);

        // Get the configuration created for this agent
        $config = AgentConfiguration::where('ai_agent_id', $agent->id)
            ->where('team_id', $this->team->id)
            ->first();

        expect($config)->not->toBeNull();

        // Verify all template permissions were inherited
        expect($config->can_create_work_orders)->toBe($template->default_permissions['can_create_work_orders']);
        expect($config->can_modify_tasks)->toBe($template->default_permissions['can_modify_tasks']);
        expect($config->can_access_client_data)->toBe($template->default_permissions['can_access_client_data']);
        expect($config->can_send_emails)->toBe($template->default_permissions['can_send_emails']);
        expect($config->can_modify_deliverables)->toBe($template->default_permissions['can_modify_deliverables']);
        expect($config->can_access_financial_data)->toBe($template->default_permissions['can_access_financial_data']);
        expect($config->can_modify_playbooks)->toBe($template->default_permissions['can_modify_playbooks']);
    });
});

describe('Budget Boundary Conditions', function () {
    test('budget check fails when daily available but monthly exceeded', function () {
        $budgetService = new AgentBudgetService();

        // Set daily spend low but monthly at cap
        $this->config->daily_spend = 5.00;
        $this->config->current_month_spend = 98.00; // Monthly cap is 100
        $this->config->save();
        $this->config->refresh();

        // Request cost of 10 would exceed monthly but not daily
        $canRun = $budgetService->canRun($this->config, 10.00);

        expect($canRun)->toBeFalse();
        expect($budgetService->getDailyRemaining($this->config))->toBe(95.00); // 100 - 5
        expect($budgetService->getMonthlyRemaining($this->config))->toBe(2.00); // 100 - 98
    });

    test('budget check fails when monthly available but daily exceeded', function () {
        $budgetService = new AgentBudgetService();

        // Set monthly spend low but daily at cap
        $this->config->daily_spend = 98.00; // Daily approaching cap
        $this->config->current_month_spend = 10.00; // Monthly has room
        $this->config->save();
        $this->config->refresh();

        // Request cost of 5 would exceed daily but not monthly
        $canRun = $budgetService->canRun($this->config, 5.00);

        expect($canRun)->toBeFalse();
        expect($budgetService->getDailyRemaining($this->config))->toBe(2.00); // 100 - 98
        expect($budgetService->getMonthlyRemaining($this->config))->toBe(90.00); // 100 - 10
    });
});

describe('Tool Execution Edge Cases', function () {
    test('failed tool execution is logged with error details', function () {
        $registry = new ToolRegistry();
        $permissionService = new AgentPermissionService();
        $budgetService = new AgentBudgetService();
        $gateway = new ToolGateway($registry, $permissionService, $budgetService);

        // Register a failing tool
        $failingTool = new FailingIntegrationTool();
        $registry->register($failingTool);

        // Execute the failing tool
        $result = $gateway->execute(
            $this->agent,
            $this->config,
            'failing-integration-tool',
            []
        );

        // Verify result indicates failure
        expect($result)->toBeInstanceOf(ToolResult::class);
        expect($result->success)->toBeFalse();
        expect($result->status)->toBe('failed');
        expect($result->isFailed())->toBeTrue();
        expect($result->error)->toContain('Simulated tool failure');

        // Verify the failure was logged
        $activityLog = AgentActivityLog::where('ai_agent_id', $this->agent->id)
            ->where('team_id', $this->team->id)
            ->latest()
            ->first();

        expect($activityLog)->not->toBeNull();
        expect($activityLog->tool_calls[0]['status'])->toBe('failed');
        expect($activityLog->error)->toContain('Simulated tool failure');
    });

    test('executing unknown tool returns appropriate error', function () {
        $registry = new ToolRegistry();
        $permissionService = new AgentPermissionService();
        $budgetService = new AgentBudgetService();
        $gateway = new ToolGateway($registry, $permissionService, $budgetService);

        // Do not register any tools - try to execute non-existent tool
        $result = $gateway->execute(
            $this->agent,
            $this->config,
            'non-existent-tool',
            []
        );

        expect($result->success)->toBeFalse();
        expect($result->error)->toContain('not found');
    });
});

describe('Agent Configuration States', function () {
    test('disabled agent configuration blocks execution via API', function () {
        // Disable the agent configuration
        $this->config->update(['enabled' => false]);

        $response = $this->actingAs($this->user)->post("/settings/agents/{$this->agent->id}/run", [
            'prompt' => 'This should not execute',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Verify no activity was logged for this disabled agent
        $activityCount = AgentActivityLog::where('ai_agent_id', $this->agent->id)
            ->where('team_id', $this->team->id)
            ->where('run_type', 'agent_run')
            ->count();

        expect($activityCount)->toBe(0);
    });
});

describe('Context Builder with Real Data', function () {
    test('context builder truncates appropriately with large project data', function () {
        // Create substantial test data
        $party = Party::factory()->create(['team_id' => $this->team->id]);
        $project = Project::factory()->create([
            'team_id' => $this->team->id,
            'party_id' => $party->id,
            'owner_id' => $this->user->id,
            'name' => 'Large Context Test Project',
            'description' => str_repeat('Project description content. ', 100), // ~2700 chars
        ]);

        // Create many work orders with descriptions
        for ($i = 0; $i < 20; $i++) {
            $workOrder = WorkOrder::factory()->create([
                'team_id' => $this->team->id,
                'project_id' => $project->id,
                'created_by_id' => $this->user->id,
                'title' => "Work Order {$i} - Website Development Phase",
                'description' => str_repeat("Detailed work order description for item {$i}. ", 30),
            ]);

            // Create tasks for each work order
            Task::factory()->count(5)->create([
                'team_id' => $this->team->id,
                'project_id' => $project->id,
                'work_order_id' => $workOrder->id,
                'created_by_id' => $this->user->id,
            ]);
        }

        // Build context with a strict token limit
        $contextBuilder = app(\App\Services\ContextBuilder::class);
        $context = $contextBuilder->build($project, $this->agent, maxTokens: 1000);

        // Verify context was built and respects token limit
        expect($context)->toBeInstanceOf(\App\ValueObjects\AgentContext::class);
        expect($context->getTokenEstimate())->toBeLessThanOrEqual(1000);

        // Verify essential project info is still present
        expect($context->projectContext)->toHaveKey('name');
        expect($context->projectContext['name'])->toBe('Large Context Test Project');
    });
});

describe('GlobalAISettings Override', function () {
    test('system-level approval requirement forces workflow pause for financial actions', function () {
        // Ensure global settings require approval for financial actions
        expect($this->globalSettings->require_approval_financial)->toBeTrue();

        $permissionService = new AgentPermissionService();

        // Even if agent has permission, system-level should require approval
        $requiresApproval = $permissionService->requiresHumanApproval('financial', $this->globalSettings);
        expect($requiresApproval)->toBeTrue();

        // Verify external sends also require approval
        $requiresApprovalSends = $permissionService->requiresHumanApproval('external_sends', $this->globalSettings);
        expect($requiresApprovalSends)->toBeTrue();

        // Verify scope changes do NOT require approval (set to false)
        $requiresApprovalScope = $permissionService->requiresHumanApproval('scope_changes', $this->globalSettings);
        expect($requiresApprovalScope)->toBeFalse();
    });
});
