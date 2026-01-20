<?php

declare(strict_types=1);

use App\Agents\Tools\TaskListTool;
use App\Agents\Tools\WorkOrderInfoTool;
use App\Enums\AgentType;
use App\Models\AgentActivityLog;
use App\Models\AgentConfiguration;
use App\Models\AgentTemplate;
use App\Models\AIAgent;
use App\Models\Party;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\AgentRunner;
use App\Services\ToolRegistry;
use Database\Seeders\AgentTemplateSeeder;
use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->team = $this->user->createTeam(['name' => 'Test Team']);
    $this->user->current_team_id = $this->team->id;
    $this->user->save();
});

describe('Agent Template Seeding', function () {
    test('AgentTemplateSeeder creates expected templates', function () {
        // Run the seeder
        $seeder = new AgentTemplateSeeder;
        $seeder->run();

        // Verify all expected templates exist
        expect(AgentTemplate::count())->toBeGreaterThanOrEqual(6);

        // Check for specific required templates
        $dispatcher = AgentTemplate::where('code', 'dispatcher')->first();
        expect($dispatcher)->not->toBeNull();
        expect($dispatcher->name)->toBe('Dispatcher');
        expect($dispatcher->type)->toBe(AgentType::WorkRouting);
        expect($dispatcher->default_tools)->toBeArray();
        expect($dispatcher->default_permissions)->toBeArray();
        expect($dispatcher->is_active)->toBeTrue();

        $pmCopilot = AgentTemplate::where('code', 'pm-copilot')->first();
        expect($pmCopilot)->not->toBeNull();
        expect($pmCopilot->type)->toBe(AgentType::ProjectManagement);

        $qaCompliance = AgentTemplate::where('code', 'qa-compliance')->first();
        expect($qaCompliance)->not->toBeNull();
        expect($qaCompliance->type)->toBe(AgentType::QualityAssurance);

        $finance = AgentTemplate::where('code', 'finance')->first();
        expect($finance)->not->toBeNull();

        $clientComms = AgentTemplate::where('code', 'client-comms')->first();
        expect($clientComms)->not->toBeNull();

        // Check that at least one domain skill template exists
        $copywriter = AgentTemplate::where('code', 'copywriter')->first();
        expect($copywriter)->not->toBeNull();
        expect($copywriter->type)->toBe(AgentType::ContentCreation);
    });

    test('agent creation from template inherits defaults', function () {
        // Create a template
        $template = AgentTemplate::create([
            'code' => 'test-template',
            'name' => 'Test Template',
            'type' => AgentType::ProjectManagement,
            'description' => 'A test template for verifying inheritance',
            'default_instructions' => 'You are a helpful assistant.',
            'default_tools' => ['task-list', 'work-order-info'],
            'default_permissions' => [
                'can_modify_tasks' => true,
                'can_create_work_orders' => true,
                'can_access_client_data' => false,
            ],
            'is_active' => true,
        ]);

        // Create an agent from the template
        $agent = AIAgent::create([
            'code' => 'team-test-agent',
            'name' => 'Team Test Agent',
            'type' => $template->type,
            'description' => $template->description,
            'capabilities' => $template->default_tools,
            'template_id' => $template->id,
            'is_custom' => false,
        ]);

        // Create configuration with template defaults
        $config = AgentConfiguration::create([
            'team_id' => $this->team->id,
            'ai_agent_id' => $agent->id,
            'enabled' => true,
            'daily_run_limit' => 50,
            'monthly_budget_cap' => 100.00,
            'daily_spend' => 0.00,
            'current_month_spend' => 0.00,
            'can_modify_tasks' => $template->default_permissions['can_modify_tasks'] ?? false,
            'can_create_work_orders' => $template->default_permissions['can_create_work_orders'] ?? false,
            'can_access_client_data' => $template->default_permissions['can_access_client_data'] ?? false,
        ]);

        // Verify agent inherits template properties
        expect($agent->template_id)->toBe($template->id);
        expect($agent->template)->toBeInstanceOf(AgentTemplate::class);
        expect($agent->type)->toBe($template->type);
        expect($agent->capabilities)->toBe($template->default_tools);

        // Verify configuration has template permissions
        expect($config->can_modify_tasks)->toBeTrue();
        expect($config->can_create_work_orders)->toBeTrue();
        expect($config->can_access_client_data)->toBeFalse();
    });
});

describe('Sample Tool Integration', function () {
    test('end-to-end: create agent, run with tool, log activity with tool calls', function () {
        // Set up test data
        $party = Party::factory()->create(['team_id' => $this->team->id]);
        $project = Project::factory()->create([
            'team_id' => $this->team->id,
            'party_id' => $party->id,
            'owner_id' => $this->user->id,
        ]);
        $workOrder = WorkOrder::factory()->create([
            'team_id' => $this->team->id,
            'project_id' => $project->id,
            'created_by_id' => $this->user->id,
        ]);
        Task::factory()->count(3)->create([
            'team_id' => $this->team->id,
            'project_id' => $project->id,
            'work_order_id' => $workOrder->id,
            'created_by_id' => $this->user->id,
        ]);

        // Create agent and configuration
        $agent = AIAgent::factory()->create([
            'code' => 'integration-test-agent',
            'name' => 'Integration Test Agent',
            'type' => AgentType::ProjectManagement,
        ]);

        $config = AgentConfiguration::create([
            'team_id' => $this->team->id,
            'ai_agent_id' => $agent->id,
            'enabled' => true,
            'daily_run_limit' => 50,
            'monthly_budget_cap' => 100.00,
            'daily_spend' => 0.00,
            'current_month_spend' => 0.00,
            'can_modify_tasks' => true,
            'can_create_work_orders' => true,
            'can_access_client_data' => true,
        ]);

        // Register sample tools
        $registry = app(ToolRegistry::class);
        $registry->register(new TaskListTool);
        $registry->register(new WorkOrderInfoTool);

        // Run agent with tool execution
        $runner = app(AgentRunner::class);
        $activityLog = $runner->run(
            $agent,
            $config,
            [
                'tool' => 'task-list',
                'tool_params' => ['work_order_id' => $workOrder->id],
            ],
        );

        // Verify activity log was created with tool calls
        expect($activityLog)->toBeInstanceOf(AgentActivityLog::class);
        expect($activityLog->team_id)->toBe($this->team->id);
        expect($activityLog->ai_agent_id)->toBe($agent->id);
        expect($activityLog->run_type)->toBe('agent_run');
        expect($activityLog->tool_calls)->toBeArray();
        expect($activityLog->tool_calls)->not->toBeEmpty();
        expect($activityLog->tool_calls[0]['tool'])->toBe('task-list');
        expect($activityLog->tool_calls[0]['status'])->toBe('success');

        // Verify activity log recorded execution details
        expect($activityLog->tokens_used)->toBeGreaterThanOrEqual(0);
        expect($activityLog->duration_ms)->toBeGreaterThanOrEqual(0);
        expect($activityLog->output)->not->toBeNull();

        // Verify budget tracking was attempted (cost recorded in activity log)
        expect($activityLog->cost)->toBeGreaterThanOrEqual(0);
    });

    test('TaskListTool returns tasks for a work order', function () {
        // Set up test data
        $party = Party::factory()->create(['team_id' => $this->team->id]);
        $project = Project::factory()->create([
            'team_id' => $this->team->id,
            'party_id' => $party->id,
            'owner_id' => $this->user->id,
        ]);
        $workOrder = WorkOrder::factory()->create([
            'team_id' => $this->team->id,
            'project_id' => $project->id,
            'created_by_id' => $this->user->id,
        ]);
        Task::factory()->count(3)->create([
            'team_id' => $this->team->id,
            'project_id' => $project->id,
            'work_order_id' => $workOrder->id,
            'created_by_id' => $this->user->id,
        ]);

        // Execute tool directly
        $tool = new TaskListTool;
        $result = $tool->execute(['work_order_id' => $workOrder->id]);

        // Verify result
        expect($result)->toHaveKey('tasks');
        expect($result['tasks'])->toHaveCount(3);
        expect($result)->toHaveKey('count');
        expect($result['count'])->toBe(3);
    });

    test('WorkOrderInfoTool returns work order details', function () {
        // Set up test data
        $party = Party::factory()->create(['team_id' => $this->team->id]);
        $project = Project::factory()->create([
            'team_id' => $this->team->id,
            'party_id' => $party->id,
            'owner_id' => $this->user->id,
        ]);
        $workOrder = WorkOrder::factory()->create([
            'team_id' => $this->team->id,
            'project_id' => $project->id,
            'created_by_id' => $this->user->id,
            'title' => 'Test Work Order',
        ]);

        // Execute tool directly
        $tool = new WorkOrderInfoTool;
        $result = $tool->execute(['work_order_id' => $workOrder->id]);

        // Verify result
        expect($result)->toHaveKey('work_order');
        expect($result['work_order']['id'])->toBe($workOrder->id);
        expect($result['work_order']['title'])->toBe('Test Work Order');
        expect($result['work_order'])->toHaveKey('status');
        expect($result['work_order'])->toHaveKey('project');
    });
});

describe('Daily Spend Reset Command', function () {
    test('daily spend reset command execution resets all configurations', function () {
        // Create multiple agent configurations with daily spend
        $agent1 = AIAgent::factory()->create();
        $agent2 = AIAgent::factory()->create();

        $config1 = AgentConfiguration::create([
            'team_id' => $this->team->id,
            'ai_agent_id' => $agent1->id,
            'enabled' => true,
            'daily_run_limit' => 50,
            'monthly_budget_cap' => 100.00,
            'daily_spend' => 25.50,
            'current_month_spend' => 50.00,
        ]);

        $config2 = AgentConfiguration::create([
            'team_id' => $this->team->id,
            'ai_agent_id' => $agent2->id,
            'enabled' => true,
            'daily_run_limit' => 50,
            'monthly_budget_cap' => 100.00,
            'daily_spend' => 10.75,
            'current_month_spend' => 30.00,
        ]);

        // Run the reset command
        Artisan::call('agents:reset-daily-spend');

        // Refresh and verify daily_spend is reset
        $config1->refresh();
        $config2->refresh();

        expect((float) $config1->daily_spend)->toBe(0.0);
        expect((float) $config2->daily_spend)->toBe(0.0);

        // Verify monthly spend is NOT reset
        expect((float) $config1->current_month_spend)->toBe(50.0);
        expect((float) $config2->current_month_spend)->toBe(30.0);
    });
});
