<?php

declare(strict_types=1);

use App\Agents\Workflows\PMCopilotWorkflow;
use App\Enums\AgentType;
use App\Enums\AIConfidence;
use App\Enums\InboxItemType;
use App\Enums\PMCopilotMode;
use App\Enums\SourceType;
use App\Enums\Urgency;
use App\Models\AgentConfiguration;
use App\Models\AgentWorkflowState;
use App\Models\AIAgent;
use App\Models\GlobalAISettings;
use App\Models\InboxItem;
use App\Models\Party;
use App\Models\Project;
use App\Models\User;
use App\Models\WorkOrder;

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

    $this->workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
        'title' => 'Test Work Order',
        'description' => 'A detailed work order description for testing PM Copilot.',
        'acceptance_criteria' => ['Criteria 1', 'Criteria 2'],
    ]);

    $this->agent = AIAgent::factory()->create([
        'code' => 'pm-copilot-agent',
        'name' => 'PM Copilot Agent',
        'type' => AgentType::ProjectManagement,
        'description' => 'Assists with project management tasks',
        'tools' => ['deliverable_generation', 'task_breakdown', 'project_insights'],
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
});

test('manual trigger endpoint starts PM Copilot workflow', function () {
    $response = $this->actingAs($this->user)->post("/work/work-orders/{$this->workOrder->id}/pm-copilot/trigger");

    // The endpoint should return success even if the workflow encounters errors during execution
    // as long as the workflow state was created
    $response->assertSuccessful();
    $responseData = $response->json();

    // Verify response structure
    expect($responseData)->toHaveKey('success');
    expect($responseData)->toHaveKey('workflow_state_id');

    // Verify workflow state was created
    $workflowState = AgentWorkflowState::where('team_id', $this->team->id)
        ->where('workflow_class', PMCopilotWorkflow::class)
        ->latest()
        ->first();

    expect($workflowState)->not->toBeNull();
    expect($workflowState->state_data['input']['work_order_id'])->toBe($this->workOrder->id);
});

test('get suggestions endpoint returns alternatives', function () {
    // Create a workflow state with suggestions stored (using actual state data keys)
    $workflowState = AgentWorkflowState::create([
        'team_id' => $this->team->id,
        'ai_agent_id' => $this->agent->id,
        'workflow_class' => PMCopilotWorkflow::class,
        'current_node' => 'checkpoint_deliverables',
        'state_data' => [
            'input' => [
                'work_order_id' => $this->workOrder->id,
                'team_id' => $this->team->id,
                'pm_copilot_mode' => 'staged',
            ],
            'deliverable_suggestions' => [
                [
                    'title' => 'Deliverable 1',
                    'description' => 'Test deliverable description',
                    'type' => 'document',
                    'acceptance_criteria' => ['Criteria 1'],
                    'confidence' => 'high',
                ],
                [
                    'title' => 'Deliverable 2',
                    'description' => 'Another deliverable',
                    'type' => 'code',
                    'acceptance_criteria' => ['Criteria 2'],
                    'confidence' => 'medium',
                ],
            ],
            'task_suggestions' => [
                [
                    'tasks' => [
                        [
                            'title' => 'Task 1',
                            'description' => 'Test task',
                            'estimated_hours' => 4.0,
                            'position' => 1,
                            'confidence' => 'high',
                        ],
                    ],
                    'confidence' => 'high',
                    'reasoning' => 'Phase-based breakdown',
                ],
            ],
        ],
        'paused_at' => now(),
        'approval_required' => true,
    ]);

    $response = $this->actingAs($this->user)->get("/work/work-orders/{$this->workOrder->id}/pm-copilot/suggestions");

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'success',
        'workflow_state_id',
        'deliverable_suggestions',
        'task_suggestions',
    ]);

    expect($response->json('deliverable_suggestions'))->toHaveCount(2);
    expect($response->json('task_suggestions'))->toHaveCount(1);
});

test('approve suggestion endpoint updates InboxItem and creates deliverable', function () {
    // Create workflow state with suggestions
    $workflowState = AgentWorkflowState::create([
        'team_id' => $this->team->id,
        'ai_agent_id' => $this->agent->id,
        'workflow_class' => PMCopilotWorkflow::class,
        'current_node' => 'checkpoint_deliverables',
        'state_data' => [
            'input' => [
                'work_order_id' => $this->workOrder->id,
                'team_id' => $this->team->id,
                'pm_copilot_mode' => 'staged',
            ],
            'deliverable_suggestions' => [
                [
                    'title' => 'Test Deliverable',
                    'description' => 'Suggested deliverable',
                    'type' => 'document',
                    'acceptance_criteria' => ['Criteria'],
                    'confidence' => 'high',
                ],
            ],
        ],
        'paused_at' => now(),
        'approval_required' => true,
    ]);

    // Create an inbox item for this workflow (with all required fields)
    $inboxItem = InboxItem::create([
        'team_id' => $this->team->id,
        'type' => InboxItemType::Approval,
        'title' => 'PM Copilot Suggestions',
        'content_preview' => 'Review generated deliverables',
        'full_content' => 'Full content for PM Copilot suggestions approval',
        'source_type' => SourceType::AIAgent,
        'source_id' => 'agent-' . $this->agent->id,
        'source_name' => 'PM Copilot Agent',
        'approvable_type' => AgentWorkflowState::class,
        'approvable_id' => $workflowState->id,
        'urgency' => Urgency::Normal,
        'ai_confidence' => AIConfidence::High,
        'related_work_order_id' => $this->workOrder->id,
    ]);

    $response = $this->actingAs($this->user)->post("/work/pm-copilot/suggestions/{$inboxItem->id}/approve", [
        'suggestion_type' => 'deliverable',
        'suggestion_index' => 0,
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'Suggestion approved',
    ]);

    // Verify the deliverable was created
    $this->assertDatabaseHas('deliverables', [
        'work_order_id' => $this->workOrder->id,
        'title' => 'Test Deliverable',
    ]);
});

test('reject suggestion endpoint updates InboxItem with reason', function () {
    // Create workflow state with suggestions
    $workflowState = AgentWorkflowState::create([
        'team_id' => $this->team->id,
        'ai_agent_id' => $this->agent->id,
        'workflow_class' => PMCopilotWorkflow::class,
        'current_node' => 'checkpoint_deliverables',
        'state_data' => [
            'input' => [
                'work_order_id' => $this->workOrder->id,
                'team_id' => $this->team->id,
                'pm_copilot_mode' => 'staged',
            ],
            'deliverable_suggestions' => [
                [
                    'title' => 'Test Deliverable',
                    'description' => 'Suggested deliverable',
                    'type' => 'document',
                    'acceptance_criteria' => ['Criteria'],
                    'confidence' => 'low',
                ],
            ],
        ],
        'paused_at' => now(),
        'approval_required' => true,
    ]);

    // Create an inbox item for this workflow (with all required fields)
    $inboxItem = InboxItem::create([
        'team_id' => $this->team->id,
        'type' => InboxItemType::Approval,
        'title' => 'PM Copilot Suggestions',
        'content_preview' => 'Review generated deliverables',
        'full_content' => 'Full content for PM Copilot suggestions rejection',
        'source_type' => SourceType::AIAgent,
        'source_id' => 'agent-' . $this->agent->id,
        'source_name' => 'PM Copilot Agent',
        'approvable_type' => AgentWorkflowState::class,
        'approvable_id' => $workflowState->id,
        'urgency' => Urgency::Normal,
        'ai_confidence' => AIConfidence::Low,
        'related_work_order_id' => $this->workOrder->id,
    ]);

    $response = $this->actingAs($this->user)->post("/work/pm-copilot/suggestions/{$inboxItem->id}/reject", [
        'suggestion_type' => 'deliverable',
        'suggestion_index' => 0,
        'reason' => 'Does not match project requirements',
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'Suggestion rejected',
    ]);

    // Verify the inbox item was marked as rejected
    $inboxItem->refresh();
    expect($inboxItem->rejected_at)->not->toBeNull();
});

test('work order settings endpoint updates pm_copilot_mode', function () {
    // Verify initial state
    expect($this->workOrder->pm_copilot_mode)->toBe(PMCopilotMode::Full);

    $response = $this->actingAs($this->user)->patch("/work/work-orders/{$this->workOrder->id}/agent-settings", [
        'pm_copilot_mode' => 'staged',
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'work_order' => [
            'pm_copilot_mode' => 'staged',
        ],
    ]);

    // Verify the setting was persisted
    $this->workOrder->refresh();
    expect($this->workOrder->pm_copilot_mode)->toBe(PMCopilotMode::Staged);
});
