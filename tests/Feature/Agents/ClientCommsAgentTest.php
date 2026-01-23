<?php

declare(strict_types=1);

use App\Agents\ClientCommsAgent;
use App\Agents\Tools\GetPlaybooksTool;
use App\Agents\Tools\WorkOrderInfoTool;
use App\Enums\AgentType;
use App\Enums\AIConfidence;
use App\Enums\CommunicationType;
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

    $this->party = Party::factory()->create([
        'team_id' => $this->team->id,
        'preferred_language' => 'es',
    ]);
    $this->project = Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $this->party->id,
        'owner_id' => $this->user->id,
        'name' => 'Test Project',
    ]);

    $this->agent = AIAgent::factory()->create([
        'code' => 'client-comms-agent',
        'name' => 'Client Comms Agent',
        'type' => AgentType::ClientCommunication,
        'description' => 'Drafts professional client-facing communications with human approval required before delivery',
        'capabilities' => ['status_updates', 'deliverable_notifications', 'clarification_requests', 'milestone_announcements'],
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
        'can_modify_tasks' => false,
        'can_access_client_data' => true,
        'can_send_emails' => true,
        'can_modify_deliverables' => false,
        'can_access_financial_data' => false,
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

test('ClientCommsAgent instantiates with valid configuration', function () {
    $clientCommsAgent = new ClientCommsAgent(
        $this->agent,
        $this->config,
        $this->gateway,
        $this->budgetService
    );

    expect($clientCommsAgent)->toBeInstanceOf(\App\Agents\BaseAgent::class);
    expect($clientCommsAgent->getAIAgent()->type)->toBe(AgentType::ClientCommunication);
    expect($clientCommsAgent->getConfiguration()->enabled)->toBeTrue();
});

test('ClientCommsAgent instructions returns communication-focused system prompt', function () {
    $clientCommsAgent = new ClientCommsAgent(
        $this->agent,
        $this->config,
        $this->gateway,
        $this->budgetService
    );

    $instructions = $clientCommsAgent->instructions();

    expect($instructions)->toBeString();
    expect($instructions)->toContain('Client Communication Agent');
    expect($instructions)->toContain('Status Updates');
    expect($instructions)->toContain('Deliverable Notifications');
    expect($instructions)->toContain('Clarification Requests');
    expect($instructions)->toContain('Milestone Announcements');
    expect($instructions)->toContain('professional');
});

test('ClientCommsAgent tools filters to communication-relevant tools only', function () {
    // Register tools that the agent should have access to
    $this->registry->register(new WorkOrderInfoTool());
    $this->registry->register(new GetPlaybooksTool());

    $clientCommsAgent = new ClientCommsAgent(
        $this->agent,
        $this->config,
        $this->gateway,
        $this->budgetService
    );

    $tools = $clientCommsAgent->tools();
    expect($tools)->toBeArray();

    // Verify communication-relevant tools are available
    $toolNames = array_map(fn ($tool) => $tool->name(), $tools);
    expect($toolNames)->toContain('work-order-info');
    expect($toolNames)->toContain('get-playbooks');
});

test('ClientCommsAgent determines status update confidence correctly', function () {
    $clientCommsAgent = new ClientCommsAgent(
        $this->agent,
        $this->config,
        $this->gateway,
        $this->budgetService
    );

    // High confidence: recent activity, status transitions, and complete context
    $highConfidence = $clientCommsAgent->determineStatusUpdateConfidence(
        hasRecentActivity: true,
        hasStatusTransitions: true,
        contextCompleteness: 0.9
    );
    expect($highConfidence)->toBe(AIConfidence::High);

    // Medium confidence: some activity or transitions
    $mediumConfidence = $clientCommsAgent->determineStatusUpdateConfidence(
        hasRecentActivity: true,
        hasStatusTransitions: false,
        contextCompleteness: 0.6
    );
    expect($mediumConfidence)->toBe(AIConfidence::Medium);

    // Low confidence: minimal information
    $lowConfidence = $clientCommsAgent->determineStatusUpdateConfidence(
        hasRecentActivity: false,
        hasStatusTransitions: false,
        contextCompleteness: 0.2
    );
    expect($lowConfidence)->toBe(AIConfidence::Low);
});

test('ClientCommsAgent determines deliverable notification confidence correctly', function () {
    $clientCommsAgent = new ClientCommsAgent(
        $this->agent,
        $this->config,
        $this->gateway,
        $this->budgetService
    );

    // High confidence: deliverable details and acceptance criteria
    $highConfidence = $clientCommsAgent->determineDeliverableNotificationConfidence(
        hasDeliverableDetails: true,
        hasAcceptanceCriteria: true
    );
    expect($highConfidence)->toBe(AIConfidence::High);

    // Medium confidence: has details but no criteria
    $mediumConfidence = $clientCommsAgent->determineDeliverableNotificationConfidence(
        hasDeliverableDetails: true,
        hasAcceptanceCriteria: false
    );
    expect($mediumConfidence)->toBe(AIConfidence::Medium);

    // Low confidence: missing details
    $lowConfidence = $clientCommsAgent->determineDeliverableNotificationConfidence(
        hasDeliverableDetails: false,
        hasAcceptanceCriteria: false
    );
    expect($lowConfidence)->toBe(AIConfidence::Low);
});

test('ClientCommsAgent language handling returns correct language', function () {
    $clientCommsAgent = new ClientCommsAgent(
        $this->agent,
        $this->config,
        $this->gateway,
        $this->budgetService
    );

    // Test with Party that has Spanish preference
    $language = $clientCommsAgent->getTargetLanguage($this->party);
    expect($language)->toBe('es');

    // Test with Party that has no preference (defaults to 'en')
    $partyNoLang = Party::factory()->create([
        'team_id' => $this->team->id,
        'preferred_language' => null,
    ]);
    $languageDefault = $clientCommsAgent->getTargetLanguage($partyNoLang);
    expect($languageDefault)->toBe('en');
});
