<?php

declare(strict_types=1);

use App\Agents\PMCopilotAgent;
use App\Agents\Tools\GetPlaybooksTool;
use App\Agents\Tools\GetTeamCapacityTool;
use App\Agents\Tools\TaskListTool;
use App\Agents\Tools\WorkOrderInfoTool;
use App\Enums\AgentType;
use App\Enums\AIConfidence;
use App\Models\AgentConfiguration;
use App\Models\AIAgent;
use App\Models\GlobalAISettings;
use App\Models\Party;
use App\Models\Project;
use App\Models\User;
use App\Services\AgentBudgetService;
use App\Services\ToolGateway;
use App\Services\ToolRegistry;

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

    $this->agent = AIAgent::factory()->create([
        'code' => 'pm-copilot-agent',
        'name' => 'PM Copilot Agent',
        'type' => AgentType::ProjectManagement,
        'description' => 'Assists with project management tasks including deliverable generation and task breakdown',
        'capabilities' => ['deliverable_generation', 'task_breakdown', 'project_insights'],
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
        // PM Copilot needs playbook read access (uses modify permission for now)
        'can_modify_playbooks' => true,
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

    $this->registry = new ToolRegistry();
    $this->budgetService = new AgentBudgetService();
    $this->gateway = new ToolGateway($this->registry);
});

test('PMCopilotAgent instantiates with valid configuration', function () {
    $pmCopilotAgent = new PMCopilotAgent(
        $this->agent,
        $this->config,
        $this->gateway,
        $this->budgetService
    );

    expect($pmCopilotAgent)->toBeInstanceOf(\App\Agents\BaseAgent::class);
    expect($pmCopilotAgent->getAIAgent()->type)->toBe(AgentType::ProjectManagement);
    expect($pmCopilotAgent->getConfiguration()->enabled)->toBeTrue();
});

test('PMCopilotAgent instructions returns PM-specific system prompt', function () {
    $pmCopilotAgent = new PMCopilotAgent(
        $this->agent,
        $this->config,
        $this->gateway,
        $this->budgetService
    );

    $instructions = $pmCopilotAgent->instructions();

    expect($instructions)->toBeString();
    expect($instructions)->toContain('PM Copilot');
    expect($instructions)->toContain('deliverable');
    expect($instructions)->toContain('task');
    expect($instructions)->toContain('confidence');
});

test('PMCopilotAgent tools filters to PM-relevant tools only', function () {
    // Register PM-relevant tools
    $this->registry->register(new WorkOrderInfoTool());
    $this->registry->register(new GetPlaybooksTool());
    $this->registry->register(new TaskListTool());
    $this->registry->register(new GetTeamCapacityTool());

    $pmCopilotAgent = new PMCopilotAgent(
        $this->agent,
        $this->config,
        $this->gateway,
        $this->budgetService
    );

    $tools = $pmCopilotAgent->tools();
    expect($tools)->toBeArray();

    // Verify PM-relevant tools are available
    $toolNames = array_map(fn ($tool) => $tool->name(), $tools);
    expect($toolNames)->toContain('work-order-info');
    expect($toolNames)->toContain('get-playbooks');
    expect($toolNames)->toContain('task-list');
    expect($toolNames)->toContain('get-team-capacity');
});

test('PMCopilotAgent determines deliverable confidence levels correctly', function () {
    $pmCopilotAgent = new PMCopilotAgent(
        $this->agent,
        $this->config,
        $this->gateway,
        $this->budgetService
    );

    // High confidence: clear description, acceptance criteria, and playbook match
    $highConfidence = $pmCopilotAgent->determineDeliverableConfidence(
        hasDescription: true,
        hasAcceptanceCriteria: true,
        playbookMatchScore: 0.8
    );
    expect($highConfidence)->toBe(AIConfidence::High);

    // Medium confidence: has description and either criteria or playbook
    $mediumConfidence = $pmCopilotAgent->determineDeliverableConfidence(
        hasDescription: true,
        hasAcceptanceCriteria: true,
        playbookMatchScore: 0.3
    );
    expect($mediumConfidence)->toBe(AIConfidence::Medium);

    // Low confidence: missing description or low scores
    $lowConfidence = $pmCopilotAgent->determineDeliverableConfidence(
        hasDescription: false,
        hasAcceptanceCriteria: false,
        playbookMatchScore: 0.0
    );
    expect($lowConfidence)->toBe(AIConfidence::Low);
});

test('PMCopilotAgent determines task confidence levels correctly', function () {
    $pmCopilotAgent = new PMCopilotAgent(
        $this->agent,
        $this->config,
        $this->gateway,
        $this->budgetService
    );

    // High confidence: detailed scope, estimate, and playbook pattern
    $highConfidence = $pmCopilotAgent->determineTaskConfidence(
        hasDetailedScope: true,
        hasEstimate: true,
        playbookPatternMatch: true
    );
    expect($highConfidence)->toBe(AIConfidence::High);

    // Medium confidence: some details available
    $mediumConfidence = $pmCopilotAgent->determineTaskConfidence(
        hasDetailedScope: true,
        hasEstimate: false,
        playbookPatternMatch: true
    );
    expect($mediumConfidence)->toBe(AIConfidence::Medium);

    // Low confidence: minimal information
    $lowConfidence = $pmCopilotAgent->determineTaskConfidence(
        hasDetailedScope: false,
        hasEstimate: false,
        playbookPatternMatch: false
    );
    expect($lowConfidence)->toBe(AIConfidence::Low);
});

test('PMCopilotAgent determines insight confidence levels correctly', function () {
    $pmCopilotAgent = new PMCopilotAgent(
        $this->agent,
        $this->config,
        $this->gateway,
        $this->budgetService
    );

    // High confidence: data-driven insight with strong evidence
    $highConfidence = $pmCopilotAgent->determineInsightConfidence(
        dataPointCount: 10,
        signalStrength: 0.9,
        historicalBaseline: true
    );
    expect($highConfidence)->toBe(AIConfidence::High);

    // Medium confidence: some data with moderate signal
    $mediumConfidence = $pmCopilotAgent->determineInsightConfidence(
        dataPointCount: 5,
        signalStrength: 0.5,
        historicalBaseline: false
    );
    expect($mediumConfidence)->toBe(AIConfidence::Medium);

    // Low confidence: limited data or weak signal
    $lowConfidence = $pmCopilotAgent->determineInsightConfidence(
        dataPointCount: 2,
        signalStrength: 0.2,
        historicalBaseline: false
    );
    expect($lowConfidence)->toBe(AIConfidence::Low);
});
