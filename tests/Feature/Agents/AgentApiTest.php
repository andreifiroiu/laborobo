<?php

declare(strict_types=1);

use App\Enums\AgentType;
use App\Enums\ApprovalStatus;
use App\Models\AgentActivityLog;
use App\Models\AgentConfiguration;
use App\Models\AgentTemplate;
use App\Models\AgentWorkflowState;
use App\Models\AIAgent;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->team = $this->user->createTeam(['name' => 'Test Team']);
    $this->user->current_team_id = $this->team->id;
    $this->user->save();

    // Create a sample agent template
    $this->template = AgentTemplate::create([
        'code' => 'pm-copilot',
        'name' => 'PM Copilot',
        'type' => AgentType::ProjectManagement,
        'description' => 'Project management assistant',
        'default_instructions' => 'You help manage projects.',
        'default_tools' => ['task-list', 'work-order-info'],
        'default_permissions' => [
            'can_create_work_orders' => true,
            'can_modify_tasks' => true,
        ],
        'is_active' => true,
    ]);

    // Create a sample agent
    $this->agent = AIAgent::create([
        'code' => 'team-pm-copilot',
        'name' => 'Team PM Copilot',
        'type' => AgentType::ProjectManagement,
        'description' => 'Custom PM agent',
        'tools' => ['planning', 'scheduling'],
        'template_id' => $this->template->id,
        'is_custom' => false,
    ]);

    // Create agent configuration for the team
    $this->config = AgentConfiguration::create([
        'team_id' => $this->team->id,
        'ai_agent_id' => $this->agent->id,
        'enabled' => true,
        'daily_run_limit' => 50,
        'weekly_run_limit' => 200,
        'monthly_budget_cap' => 100.00,
        'current_month_spend' => 10.00,
        'daily_spend' => 2.00,
        'can_create_work_orders' => true,
        'can_modify_tasks' => true,
        'can_access_client_data' => true,
        'can_send_emails' => false,
        'requires_approval' => false,
    ]);
});

test('GET /settings/agent-templates returns template list', function () {
    // Create another template
    AgentTemplate::create([
        'code' => 'dispatcher',
        'name' => 'Dispatcher Agent',
        'type' => AgentType::WorkRouting,
        'description' => 'Routes and triages incoming work',
        'default_instructions' => 'You are a dispatcher agent.',
        'default_tools' => ['work-order-info'],
        'default_permissions' => ['can_create_work_orders' => true],
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->user)->get('/settings/agent-templates');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('settings/agent-templates/index', false)
        ->has('templates', 2)
        ->where('templates.0.code', 'dispatcher')
        ->where('templates.1.code', 'pm-copilot')
    );
});

test('POST /settings/agents creates agent from template', function () {
    // Use a fresh template so it doesn't conflict with the agent already created in beforeEach
    $newTemplate = AgentTemplate::create([
        'code' => 'dispatcher',
        'name' => 'Dispatcher Agent',
        'type' => AgentType::WorkRouting,
        'description' => 'Routes incoming work items',
        'default_instructions' => 'You are a dispatcher agent.',
        'default_tools' => ['work-order-info', 'task-list', 'get-team-capacity'],
        'default_permissions' => [
            'can_create_work_orders' => true,
            'can_modify_tasks' => false,
        ],
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->user)->post('/settings/agents', [
        'template_id' => $newTemplate->id,
        'name' => 'My Custom Dispatcher',
        'description' => 'A customized dispatcher for my team',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    $this->assertDatabaseHas('ai_agents', [
        'name' => 'My Custom Dispatcher',
        'template_id' => $newTemplate->id,
        'is_custom' => false,
    ]);

    // Verify tools were copied from template to agent capabilities
    $agent = AIAgent::where('name', 'My Custom Dispatcher')->first();
    expect($agent)->not->toBeNull();
    expect($agent->tools)->toBe(['work-order-info', 'task-list', 'get-team-capacity']);

    // Verify configuration was created with template permissions
    $this->assertDatabaseHas('agent_configurations', [
        'team_id' => $this->team->id,
        'ai_agent_id' => $agent->id,
        'enabled' => true,
        'can_create_work_orders' => true,
        'can_modify_tasks' => false,
    ]);
});

test('PATCH /settings/agents/{id}/configuration updates permissions and budget', function () {
    $response = $this->actingAs($this->user)->patch("/settings/agents/{$this->agent->id}/configuration", [
        'can_create_work_orders' => false,
        'can_modify_tasks' => true,
        'can_access_client_data' => false,
        'can_send_emails' => true,
        'can_modify_deliverables' => true,
        'can_access_financial_data' => false,
        'can_modify_playbooks' => false,
        'monthly_budget_cap' => 200.00,
        'daily_run_limit' => 100,
    ]);

    $response->assertRedirect();

    $this->config->refresh();

    expect($this->config->can_create_work_orders)->toBeFalse();
    expect($this->config->can_send_emails)->toBeTrue();
    expect($this->config->can_modify_deliverables)->toBeTrue();
    expect((float) $this->config->monthly_budget_cap)->toBe(200.00);
    expect($this->config->daily_run_limit)->toBe(100);
});

test('GET /settings/agents/{id}/activity returns activity logs with tool_calls', function () {
    // Create activity logs with tool_calls
    AgentActivityLog::create([
        'team_id' => $this->team->id,
        'ai_agent_id' => $this->agent->id,
        'run_type' => 'tool_execution',
        'input' => 'Get task list',
        'output' => 'Found 5 tasks',
        'tokens_used' => 150,
        'cost' => 0.0025,
        'approval_status' => ApprovalStatus::Approved,
        'tool_calls' => [
            [
                'tool' => 'task-list',
                'params' => ['project_id' => 123],
                'result' => ['count' => 5],
                'duration_ms' => 45,
            ],
        ],
        'context_accessed' => [
            'project_memory' => ['project_id' => 123],
        ],
        'duration_ms' => 250,
    ]);

    AgentActivityLog::create([
        'team_id' => $this->team->id,
        'ai_agent_id' => $this->agent->id,
        'run_type' => 'agent_run',
        'input' => 'Analyze project status',
        'output' => 'Project is on track',
        'tokens_used' => 300,
        'cost' => 0.005,
        'approval_status' => ApprovalStatus::Pending,
        'tool_calls' => [],
        'context_accessed' => [],
        'duration_ms' => 500,
    ]);

    $response = $this->actingAs($this->user)->get("/settings/agents/{$this->agent->id}/activity");

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('settings/agent-activity/index', false)
        ->has('activities.data', 2)
    );

    // Verify the structure by checking the response data
    $activities = $response->original->getData()['page']['props']['activities']['data'];
    expect($activities[0]['tool_calls'][0]['tool'])->toBe('task-list');
    expect($activities[0]['context_accessed']['project_memory']['project_id'])->toBe(123);
});

test('POST /settings/agents/{id}/run executes agent with budget check', function () {
    // Ensure budget is available
    $this->config->update([
        'daily_spend' => 0.00,
        'current_month_spend' => 0.00,
        'monthly_budget_cap' => 100.00,
    ]);

    $response = $this->actingAs($this->user)->post("/settings/agents/{$this->agent->id}/run", [
        'prompt' => 'List all tasks for project 123',
    ]);

    $response->assertRedirect();

    // Check that an activity log was created
    $this->assertDatabaseHas('agent_activity_logs', [
        'team_id' => $this->team->id,
        'ai_agent_id' => $this->agent->id,
        'run_type' => 'agent_run',
    ]);
});

test('POST /settings/agents/{id}/run returns error when budget exceeded', function () {
    // Set budget to be exceeded
    $this->config->update([
        'daily_spend' => 100.00,
        'current_month_spend' => 100.00,
        'monthly_budget_cap' => 100.00,
    ]);

    $response = $this->actingAs($this->user)->post("/settings/agents/{$this->agent->id}/run", [
        'prompt' => 'List all tasks',
    ]);

    // The controller returns back with an error when budget is exceeded
    $response->assertRedirect();
    $response->assertSessionHas('error');
});

test('POST /settings/workflow-states/{id}/approve resumes paused workflow', function () {
    // Create a paused workflow state
    $workflowState = AgentWorkflowState::create([
        'team_id' => $this->team->id,
        'ai_agent_id' => $this->agent->id,
        'workflow_class' => 'App\\Agents\\Workflows\\TaskAssignmentWorkflow',
        'current_node' => 'await_approval',
        'state_data' => ['task_id' => 123],
        'paused_at' => now(),
        'pause_reason' => 'Awaiting human approval',
        'approval_required' => true,
    ]);

    $response = $this->actingAs($this->user)->post("/settings/workflow-states/{$workflowState->id}/approve");

    $response->assertRedirect();

    $workflowState->refresh();

    expect($workflowState->resumed_at)->not->toBeNull();
    expect($workflowState->paused_at)->toBeNull();
    expect($workflowState->approval_required)->toBeFalse();
    expect($workflowState->state_data['approval_data']['approved'])->toBeTrue();
    expect($workflowState->state_data['approval_data']['approver_id'])->toBe($this->user->id);
});

test('POST /settings/workflow-states/{id}/reject updates workflow with rejection reason', function () {
    // Create a paused workflow state
    $workflowState = AgentWorkflowState::create([
        'team_id' => $this->team->id,
        'ai_agent_id' => $this->agent->id,
        'workflow_class' => 'App\\Agents\\Workflows\\TaskAssignmentWorkflow',
        'current_node' => 'await_approval',
        'state_data' => ['task_id' => 123],
        'paused_at' => now(),
        'pause_reason' => 'Awaiting human approval',
        'approval_required' => true,
    ]);

    $response = $this->actingAs($this->user)->post("/settings/workflow-states/{$workflowState->id}/reject", [
        'reason' => 'This action is not appropriate at this time.',
    ]);

    $response->assertRedirect();

    $workflowState->refresh();

    expect($workflowState->state_data['rejected'])->toBeTrue();
    expect($workflowState->state_data['rejection_reason'])->toBe('This action is not appropriate at this time.');
    expect($workflowState->state_data['rejected_by'])->toBe($this->user->id);
});
