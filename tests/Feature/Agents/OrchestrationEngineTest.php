<?php

declare(strict_types=1);

use App\Enums\InboxItemType;
use App\Enums\SourceType;
use App\Enums\Urgency;
use App\Models\AgentConfiguration;
use App\Models\AgentWorkflowState;
use App\Models\AIAgent;
use App\Models\GlobalAISettings;
use App\Models\InboxItem;
use App\Models\Team;
use App\Models\User;
use App\Models\WorkflowCustomization;
use App\Services\AgentApprovalService;
use App\Services\AgentOrchestrator;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->team = $this->user->createTeam(['name' => 'Test Team']);
    $this->user->current_team_id = $this->team->id;
    $this->user->save();

    $this->agent = AIAgent::factory()->create([
        'code' => 'test-orchestration-agent',
        'name' => 'Test Orchestration Agent',
        'description' => 'Agent for orchestration testing',
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

    $this->orchestrator = app(AgentOrchestrator::class);
    $this->approvalService = app(AgentApprovalService::class);
});

test('agent orchestrator creates workflow state record on execute', function () {
    $workflowClass = 'App\\Agents\\Workflows\\TestWorkflow';
    $input = ['task_id' => 123, 'action' => 'assign'];

    $state = $this->orchestrator->execute($workflowClass, $input, $this->team, $this->agent);

    expect($state)->toBeInstanceOf(AgentWorkflowState::class);
    expect($state->team_id)->toBe($this->team->id);
    expect($state->ai_agent_id)->toBe($this->agent->id);
    expect($state->workflow_class)->toBe($workflowClass);
    expect($state->state_data)->toBeArray();
    expect($state->state_data['input'])->toBe($input);
});

test('workflow pause creates agent workflow state record with pause data', function () {
    $state = AgentWorkflowState::create([
        'team_id' => $this->team->id,
        'ai_agent_id' => $this->agent->id,
        'workflow_class' => 'App\\Agents\\Workflows\\TestWorkflow',
        'current_node' => 'processing',
        'state_data' => ['task_id' => 123],
        'approval_required' => false,
    ]);

    expect($state->isPaused())->toBeFalse();

    $this->orchestrator->pause($state, 'Awaiting human approval for task assignment');

    $state->refresh();

    expect($state->isPaused())->toBeTrue();
    expect($state->paused_at)->not->toBeNull();
    expect($state->pause_reason)->toBe('Awaiting human approval for task assignment');
    expect($state->approval_required)->toBeTrue();
});

test('workflow resume from paused state clears pause and updates state', function () {
    $state = AgentWorkflowState::create([
        'team_id' => $this->team->id,
        'ai_agent_id' => $this->agent->id,
        'workflow_class' => 'App\\Agents\\Workflows\\TestWorkflow',
        'current_node' => 'await_approval',
        'state_data' => ['task_id' => 123],
        'paused_at' => now(),
        'pause_reason' => 'Awaiting approval',
        'approval_required' => true,
    ]);

    expect($state->isPaused())->toBeTrue();

    $this->orchestrator->resume($state, ['approved' => true, 'approver_id' => $this->user->id]);

    $state->refresh();

    expect($state->isPaused())->toBeFalse();
    expect($state->resumed_at)->not->toBeNull();
    expect($state->state_data['approval_data'])->toBe(['approved' => true, 'approver_id' => $this->user->id]);
});

test('inbox item is created for agent approval requests', function () {
    $state = AgentWorkflowState::create([
        'team_id' => $this->team->id,
        'ai_agent_id' => $this->agent->id,
        'workflow_class' => 'App\\Agents\\Workflows\\TaskAssignmentWorkflow',
        'current_node' => 'await_approval',
        'state_data' => ['task_id' => 123, 'assignee_id' => 456],
        'approval_required' => false,
    ]);

    $inboxItem = $this->approvalService->requestApproval(
        $state,
        'Assign task #123 to user #456'
    );

    expect($inboxItem)->toBeInstanceOf(InboxItem::class);
    expect($inboxItem->team_id)->toBe($this->team->id);
    expect($inboxItem->type)->toBe(InboxItemType::Approval);
    expect($inboxItem->source_type)->toBe(SourceType::AIAgent);
    expect($inboxItem->approvable_type)->toBe(AgentWorkflowState::class);
    expect($inboxItem->approvable_id)->toBe($state->id);
    expect($inboxItem->title)->toContain('Agent action requires approval');

    // Verify the state was updated to require approval
    $state->refresh();
    expect($state->approval_required)->toBeTrue();
    expect($state->isPaused())->toBeTrue();
});

test('workflow customization can be loaded from database', function () {
    $customization = WorkflowCustomization::create([
        'team_id' => $this->team->id,
        'workflow_class' => 'App\\Agents\\Workflows\\TaskAssignmentWorkflow',
        'customizations' => [
            'disabled_steps' => ['notify_slack'],
            'parameters' => ['auto_assign' => false],
            'hooks' => ['before_execute' => 'validateBudget'],
        ],
        'enabled' => true,
    ]);

    $loaded = WorkflowCustomization::forTeamAndWorkflow(
        $this->team->id,
        'App\\Agents\\Workflows\\TaskAssignmentWorkflow'
    );

    expect($loaded)->not->toBeNull();
    expect($loaded->customizations['disabled_steps'])->toContain('notify_slack');
    expect($loaded->customizations['parameters']['auto_assign'])->toBeFalse();
    expect($loaded->enabled)->toBeTrue();
});

test('approval handling resumes workflow and resolves inbox item', function () {
    // Create a paused workflow state
    $state = AgentWorkflowState::create([
        'team_id' => $this->team->id,
        'ai_agent_id' => $this->agent->id,
        'workflow_class' => 'App\\Agents\\Workflows\\TestWorkflow',
        'current_node' => 'await_approval',
        'state_data' => ['task_id' => 123],
        'paused_at' => now(),
        'pause_reason' => 'Awaiting approval',
        'approval_required' => true,
    ]);

    // Create an inbox item for approval
    $inboxItem = InboxItem::create([
        'team_id' => $this->team->id,
        'type' => InboxItemType::Approval,
        'title' => 'Agent action requires approval',
        'content_preview' => 'Test action',
        'full_content' => 'Full description',
        'source_id' => 'agent-' . $this->agent->id,
        'source_name' => $this->agent->name,
        'source_type' => SourceType::AIAgent,
        'approvable_type' => AgentWorkflowState::class,
        'approvable_id' => $state->id,
        'urgency' => Urgency::Normal,
    ]);

    // Handle approval
    $this->approvalService->handleApproval($inboxItem, $this->user);

    // Verify workflow resumed
    $state->refresh();
    expect($state->isPaused())->toBeFalse();
    expect($state->resumed_at)->not->toBeNull();

    // Verify inbox item marked as approved
    $inboxItem->refresh();
    expect($inboxItem->approved_at)->not->toBeNull();
});

test('rejection handling updates workflow state with rejection reason', function () {
    // Create a paused workflow state
    $state = AgentWorkflowState::create([
        'team_id' => $this->team->id,
        'ai_agent_id' => $this->agent->id,
        'workflow_class' => 'App\\Agents\\Workflows\\TestWorkflow',
        'current_node' => 'await_approval',
        'state_data' => ['task_id' => 123],
        'paused_at' => now(),
        'pause_reason' => 'Awaiting approval',
        'approval_required' => true,
    ]);

    // Create an inbox item for approval
    $inboxItem = InboxItem::create([
        'team_id' => $this->team->id,
        'type' => InboxItemType::Approval,
        'title' => 'Agent action requires approval',
        'content_preview' => 'Test action',
        'full_content' => 'Full description',
        'source_id' => 'agent-' . $this->agent->id,
        'source_name' => $this->agent->name,
        'source_type' => SourceType::AIAgent,
        'approvable_type' => AgentWorkflowState::class,
        'approvable_id' => $state->id,
        'urgency' => Urgency::Normal,
    ]);

    // Handle rejection
    $this->approvalService->handleRejection($inboxItem, $this->user, 'Not authorized for this action');

    // Verify workflow state updated with rejection
    $state->refresh();
    expect($state->state_data['rejected'])->toBeTrue();
    expect($state->state_data['rejection_reason'])->toBe('Not authorized for this action');
    expect($state->state_data['rejected_by'])->toBe($this->user->id);

    // Verify inbox item marked as rejected
    $inboxItem->refresh();
    expect($inboxItem->rejected_at)->not->toBeNull();
});
