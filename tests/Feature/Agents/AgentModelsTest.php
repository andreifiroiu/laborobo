<?php

declare(strict_types=1);

use App\Enums\AgentMemoryScope;
use App\Enums\AgentType;
use App\Models\AgentActivityLog;
use App\Models\AgentConfiguration;
use App\Models\AgentMemory;
use App\Models\AgentTemplate;
use App\Models\AgentWorkflowState;
use App\Models\AIAgent;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->team = $this->user->createTeam(['name' => 'Test Team']);
    $this->user->current_team_id = $this->team->id;
    $this->user->save();
});

test('agent template can be created with required fields', function () {
    $template = AgentTemplate::create([
        'code' => 'dispatcher',
        'name' => 'Dispatcher Agent',
        'type' => AgentType::WorkRouting,
        'description' => 'Routes and triages incoming work',
        'default_instructions' => 'You are a dispatcher agent.',
        'default_tools' => ['task-list', 'work-order-info'],
        'default_permissions' => ['can_create_work_orders' => true],
        'is_active' => true,
    ]);

    expect($template->id)->toBeInt();
    expect($template->code)->toBe('dispatcher');
    expect($template->type)->toBe(AgentType::WorkRouting);
    expect($template->default_tools)->toBeArray();
    expect($template->default_permissions)->toBeArray();
    expect($template->is_active)->toBeTrue();
});

test('agent template code must be unique', function () {
    AgentTemplate::create([
        'code' => 'unique-code',
        'name' => 'First Template',
        'type' => AgentType::ProjectManagement,
        'description' => 'First template description',
        'default_instructions' => 'Instructions',
        'default_tools' => [],
        'default_permissions' => [],
    ]);

    AgentTemplate::create([
        'code' => 'unique-code',
        'name' => 'Second Template',
        'type' => AgentType::ProjectManagement,
        'description' => 'Second template description',
        'default_instructions' => 'Instructions',
        'default_tools' => [],
        'default_permissions' => [],
    ]);
})->throws(\Illuminate\Database\QueryException::class);

test('ai agent can belong to an agent template', function () {
    $template = AgentTemplate::create([
        'code' => 'pm-copilot',
        'name' => 'PM Copilot',
        'type' => AgentType::ProjectManagement,
        'description' => 'Project management assistant',
        'default_instructions' => 'You help manage projects.',
        'default_tools' => ['task-list'],
        'default_permissions' => ['can_modify_tasks' => true],
    ]);

    $agent = AIAgent::create([
        'code' => 'team-pm-copilot',
        'name' => 'Team PM Copilot',
        'type' => AgentType::ProjectManagement,
        'description' => 'Custom PM agent',
        'tools' => ['planning', 'scheduling'],
        'template_id' => $template->id,
        'is_custom' => false,
    ]);

    expect($agent->template)->toBeInstanceOf(AgentTemplate::class);
    expect($agent->template->code)->toBe('pm-copilot');
    expect($agent->is_custom)->toBeFalse();
});

test('ai agent template relationship is nullable', function () {
    $agent = AIAgent::create([
        'code' => 'fully-custom-agent',
        'name' => 'Fully Custom Agent',
        'type' => AgentType::ContentCreation,
        'description' => 'A fully custom agent without template',
        'tools' => ['writing'],
        'template_id' => null,
        'is_custom' => true,
    ]);

    expect($agent->template)->toBeNull();
    expect($agent->is_custom)->toBeTrue();
});

test('agent memory can be scoped to project client or org levels', function () {
    // Create a project for scoping
    $party = \App\Models\Party::factory()->create(['team_id' => $this->team->id]);
    $project = Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $party->id,
        'owner_id' => $this->user->id,
    ]);

    $agent = AIAgent::factory()->create();

    // Project-scoped memory
    $projectMemory = AgentMemory::create([
        'team_id' => $this->team->id,
        'ai_agent_id' => $agent->id,
        'scope' => AgentMemoryScope::Project,
        'scope_type' => Project::class,
        'scope_id' => $project->id,
        'key' => 'project_context',
        'value' => ['recent_tasks' => ['Task 1', 'Task 2']],
        'expires_at' => now()->addDays(7),
    ]);

    // Client-scoped memory
    $clientMemory = AgentMemory::create([
        'team_id' => $this->team->id,
        'ai_agent_id' => $agent->id,
        'scope' => AgentMemoryScope::Client,
        'scope_type' => \App\Models\Party::class,
        'scope_id' => $party->id,
        'key' => 'client_preferences',
        'value' => ['communication_style' => 'formal'],
    ]);

    // Org-scoped memory
    $orgMemory = AgentMemory::create([
        'team_id' => $this->team->id,
        'ai_agent_id' => $agent->id,
        'scope' => AgentMemoryScope::Org,
        'scope_type' => Team::class,
        'scope_id' => $this->team->id,
        'key' => 'team_patterns',
        'value' => ['typical_workflow' => 'agile'],
    ]);

    expect($projectMemory->scope)->toBe(AgentMemoryScope::Project);
    expect($clientMemory->scope)->toBe(AgentMemoryScope::Client);
    expect($orgMemory->scope)->toBe(AgentMemoryScope::Org);

    // Test retrieval by scope
    $projectMemories = AgentMemory::where('team_id', $this->team->id)
        ->where('scope', AgentMemoryScope::Project)
        ->get();

    expect($projectMemories)->toHaveCount(1);
    expect($projectMemories->first()->key)->toBe('project_context');
});

test('agent workflow state persists pause and resume timestamps', function () {
    $agent = AIAgent::factory()->create();

    $state = AgentWorkflowState::create([
        'team_id' => $this->team->id,
        'ai_agent_id' => $agent->id,
        'workflow_class' => 'App\\Agents\\Workflows\\TaskAssignmentWorkflow',
        'current_node' => 'await_approval',
        'state_data' => ['task_id' => 123, 'assignee_id' => 456],
        'paused_at' => now(),
        'pause_reason' => 'Awaiting manager approval for task assignment',
        'approval_required' => true,
    ]);

    expect($state->paused_at)->not->toBeNull();
    expect($state->resumed_at)->toBeNull();
    expect($state->completed_at)->toBeNull();
    expect($state->approval_required)->toBeTrue();
    expect($state->pause_reason)->toBe('Awaiting manager approval for task assignment');

    // Simulate resuming the workflow
    $state->update([
        'resumed_at' => now(),
        'paused_at' => null,
        'current_node' => 'execute_assignment',
    ]);

    $state->refresh();

    expect($state->resumed_at)->not->toBeNull();
    expect($state->paused_at)->toBeNull();
    expect($state->current_node)->toBe('execute_assignment');

    // Simulate completion
    $state->update([
        'completed_at' => now(),
        'current_node' => 'completed',
    ]);

    $state->refresh();

    expect($state->completed_at)->not->toBeNull();
});

test('agent activity log stores extended tool calls and context accessed fields', function () {
    $agent = AIAgent::factory()->create();

    $workflowState = AgentWorkflowState::create([
        'team_id' => $this->team->id,
        'ai_agent_id' => $agent->id,
        'workflow_class' => 'TestWorkflow',
        'current_node' => 'start',
        'state_data' => [],
        'approval_required' => false,
    ]);

    $activityLog = AgentActivityLog::create([
        'team_id' => $this->team->id,
        'ai_agent_id' => $agent->id,
        'run_type' => 'tool_execution',
        'input' => 'Get task list for project 123',
        'output' => 'Found 5 tasks',
        'tokens_used' => 150,
        'cost' => 0.0025,
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
            'client_memory' => ['party_id' => 456],
        ],
        'workflow_state_id' => $workflowState->id,
        'duration_ms' => 250,
    ]);

    expect($activityLog->tool_calls)->toBeArray();
    expect($activityLog->tool_calls[0]['tool'])->toBe('task-list');
    expect($activityLog->context_accessed)->toBeArray();
    expect($activityLog->context_accessed['project_memory']['project_id'])->toBe(123);
    expect($activityLog->workflow_state_id)->toBe($workflowState->id);
    expect($activityLog->duration_ms)->toBe(250);

    // Verify relationship
    expect($activityLog->workflowState)->toBeInstanceOf(AgentWorkflowState::class);
});

test('agent configuration has new permission fields', function () {
    $agent = AIAgent::factory()->create();

    $config = AgentConfiguration::create([
        'team_id' => $this->team->id,
        'ai_agent_id' => $agent->id,
        'enabled' => true,
        'daily_run_limit' => 50,
        'monthly_budget_cap' => 100.00,
        'can_create_work_orders' => true,
        'can_modify_tasks' => true,
        'can_access_client_data' => true,
        'can_send_emails' => false,
        'can_modify_deliverables' => true,
        'can_access_financial_data' => false,
        'can_modify_playbooks' => false,
        'daily_spend' => 5.50,
        'tool_permissions' => ['task-list' => true, 'send-email' => false],
    ]);

    expect($config->can_modify_deliverables)->toBeTrue();
    expect($config->can_access_financial_data)->toBeFalse();
    expect($config->can_modify_playbooks)->toBeFalse();
    expect($config->daily_spend)->toBe('5.50');
    expect($config->tool_permissions)->toBeArray();
    expect($config->tool_permissions['task-list'])->toBeTrue();
});
