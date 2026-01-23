<?php

declare(strict_types=1);

use App\Agents\Workflows\PMCopilotWorkflow;
use App\Enums\AgentType;
use App\Enums\AIConfidence;
use App\Enums\BlockerReason;
use App\Enums\DeliverableStatus;
use App\Enums\InboxItemType;
use App\Enums\PMCopilotMode;
use App\Enums\PlaybookType;
use App\Enums\SourceType;
use App\Enums\TaskStatus;
use App\Enums\Urgency;
use App\Enums\WorkOrderStatus;
use App\Models\AgentConfiguration;
use App\Models\AgentWorkflowState;
use App\Models\AIAgent;
use App\Models\Deliverable;
use App\Models\GlobalAISettings;
use App\Models\InboxItem;
use App\Models\Party;
use App\Models\Playbook;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\AgentApprovalService;
use App\Services\AgentOrchestrator;
use App\Services\DeliverableGeneratorService;
use App\Services\PMCopilotAutoApprovalService;
use App\Services\ProjectInsightsService;
use App\Services\TaskBreakdownService;
use Carbon\Carbon;

/**
 * End-to-end integration tests for PM Copilot critical workflows.
 * These tests verify complete user journeys from trigger to creation.
 */
beforeEach(function () {
    $this->user = User::factory()->create([
        'capacity_hours_per_week' => 40,
        'current_workload_hours' => 20,
    ]);
    $this->team = $this->user->createTeam(['name' => 'Integration Test Team']);
    $this->user->current_team_id = $this->team->id;
    $this->user->save();

    $this->party = Party::factory()->create(['team_id' => $this->team->id]);
    $this->project = Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $this->party->id,
        'owner_id' => $this->user->id,
        'name' => 'Integration Test Project',
    ]);

    $this->agent = AIAgent::factory()->create([
        'code' => 'pm-copilot-agent',
        'name' => 'PM Copilot Agent',
        'type' => AgentType::ProjectManagement,
        'description' => 'Assists with project management tasks',
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
        'can_modify_playbooks' => true,
    ]);

    $this->globalSettings = GlobalAISettings::create([
        'team_id' => $this->team->id,
        'total_monthly_budget' => 1000.00,
        'current_month_spend' => 0.00,
        'pm_copilot_auto_suggest' => false,
        'pm_copilot_auto_approval_threshold' => 0.8,
    ]);

    $this->orchestrator = new AgentOrchestrator();
    $this->approvalService = new AgentApprovalService($this->orchestrator);
});

test('full workflow from trigger to deliverable and task creation', function () {
    // Create a work order with clear requirements
    $workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
        'title' => 'Build User Authentication System',
        'description' => 'Implement complete user authentication with login, registration, password reset, and session management.',
        'status' => WorkOrderStatus::Active,
        'pm_copilot_mode' => PMCopilotMode::Full,
        'acceptance_criteria' => [
            'Users can register with email',
            'Secure login functionality',
            'Password reset via email',
        ],
    ]);

    // Create a relevant playbook
    Playbook::factory()->create([
        'team_id' => $this->team->id,
        'created_by' => $this->user->id,
        'created_by_name' => $this->user->name,
        'name' => 'Authentication Implementation',
        'type' => PlaybookType::Template,
        'tags' => ['authentication', 'security'],
        'content' => [
            'deliverables' => [
                ['title' => 'Auth Module', 'type' => 'code'],
            ],
            'checklist' => ['Implement secure hashing'],
        ],
    ]);

    // Step 1: Trigger the workflow
    $state = $this->orchestrator->invokePMCopilot($workOrder, $this->agent);

    expect($state)->toBeInstanceOf(AgentWorkflowState::class);
    expect($state->current_node)->toBe('start');

    // Step 2: Run the workflow using setCurrentState
    $workflow = new PMCopilotWorkflow($this->orchestrator, $this->approvalService);
    $workflow->setCurrentState($state);
    $workflow->run();

    $state->refresh();

    // Full mode should complete without pausing
    expect($state->isCompleted())->toBeTrue();

    // Step 3: Verify deliverable alternatives were generated
    expect($state->state_data)->toHaveKey('deliverable_alternatives');
    $deliverableAlternatives = $state->state_data['deliverable_alternatives'] ?? [];
    expect(count($deliverableAlternatives))->toBeGreaterThanOrEqual(2);

    // Step 4: Verify task breakdown was generated
    expect($state->state_data)->toHaveKey('task_breakdown');
    $taskBreakdown = $state->state_data['task_breakdown'] ?? [];
    expect(count($taskBreakdown))->toBeGreaterThanOrEqual(1);
});

test('auto-approval flow for high confidence suggestions without budget impact', function () {
    $workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
        'title' => 'Create API Documentation',
        'description' => 'Generate comprehensive API documentation following OpenAPI 3.0 spec.',
        'status' => WorkOrderStatus::Active,
        'pm_copilot_mode' => PMCopilotMode::Full,
        'acceptance_criteria' => [
            'All endpoints documented',
            'Examples included',
            'Authentication flow documented',
        ],
    ]);

    // Set up auto-approval with lower threshold
    $this->globalSettings->update(['pm_copilot_auto_approval_threshold' => 0.7]);

    $autoApprovalService = new PMCopilotAutoApprovalService();

    // Simulate a high-confidence suggestion without budget impact
    $highConfidenceSuggestion = [
        'title' => 'API Reference Document',
        'description' => 'Complete API reference documentation',
        'confidence' => AIConfidence::High->value,
        'confidence_score' => 0.9,
        'has_budget_impact' => false,
    ];

    // Should be auto-approved
    expect($autoApprovalService->shouldAutoApprove($highConfidenceSuggestion, $this->globalSettings))->toBeTrue();

    // Simulate a medium-confidence suggestion
    $mediumConfidenceSuggestion = [
        'title' => 'API Quick Start Guide',
        'confidence' => AIConfidence::Medium->value,
        'confidence_score' => 0.6,
        'has_budget_impact' => false,
    ];

    // Should require manual approval
    expect($autoApprovalService->shouldAutoApprove($mediumConfidenceSuggestion, $this->globalSettings))->toBeFalse();

    // Simulate suggestion with budget impact
    $budgetImpactSuggestion = [
        'title' => 'External API Integration',
        'confidence' => AIConfidence::High->value,
        'confidence_score' => 0.95,
        'has_budget_impact' => true,
        'budget_cost' => 500.00,
    ];

    // Should require manual approval despite high confidence
    expect($autoApprovalService->shouldAutoApprove($budgetImpactSuggestion, $this->globalSettings))->toBeFalse();
});

test('staged mode complete flow: pause, review, resume, and create', function () {
    $workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
        'title' => 'Design New Dashboard',
        'description' => 'Create design mockups for analytics dashboard with charts and metrics.',
        'status' => WorkOrderStatus::Active,
        'pm_copilot_mode' => PMCopilotMode::Staged,
        'acceptance_criteria' => [
            'Responsive design',
            'Data visualization components',
        ],
    ]);

    // Step 1: Start workflow in staged mode
    $state = $this->orchestrator->invokePMCopilot($workOrder, $this->agent);
    $workflow = new PMCopilotWorkflow($this->orchestrator, $this->approvalService);
    $workflow->setCurrentState($state);
    $workflow->run();

    $state->refresh();

    // Should pause at checkpoint
    expect($state->isPaused())->toBeTrue();
    expect($state->current_node)->toBe('checkpoint_deliverables');

    // Verify InboxItem was created
    $inboxItem = InboxItem::where('approvable_type', AgentWorkflowState::class)
        ->where('approvable_id', $state->id)
        ->first();

    expect($inboxItem)->not->toBeNull();

    // Step 2: Approve deliverables and resume
    $approvalData = [
        'approved' => true,
        'approver_id' => $this->user->id,
        'approved_deliverables' => [
            ['title' => 'Dashboard Mockups', 'approved' => true],
            ['title' => 'Component Library', 'approved' => true],
        ],
    ];

    $workflow->resume($state, $approvalData);
    $state->refresh();

    expect($state->isPaused())->toBeFalse();
    expect($state->state_data['approval_data'])->toBe($approvalData);

    // Step 3: Continue running to completion
    $workflow->run();
    $state->refresh();

    expect($state->isCompleted())->toBeTrue();
    expect($state->state_data)->toHaveKey('task_breakdown');
});

test('Dispatcher Agent can invoke PM Copilot via AgentOrchestrator', function () {
    // Create a work order that would be created/routed by Dispatcher
    $workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
        'title' => 'Work Order From Dispatcher',
        'description' => 'This work order was created by the Dispatcher Agent after routing.',
        'status' => WorkOrderStatus::Draft,
        'pm_copilot_mode' => PMCopilotMode::Full,
    ]);

    // Dispatcher Agent creates a work order, then invokes PM Copilot
    // This simulates the cross-agent integration
    $orchestrator = app(AgentOrchestrator::class);

    // Invoke PM Copilot as Dispatcher would
    $state = $orchestrator->invokePMCopilot($workOrder);

    expect($state)->toBeInstanceOf(AgentWorkflowState::class);
    expect($state->workflow_class)->toBe(PMCopilotWorkflow::class);
    expect($state->team_id)->toBe($this->team->id);
    expect($state->state_data['input']['work_order_id'])->toBe($workOrder->id);

    // Verify workflow can proceed
    $workflow = new PMCopilotWorkflow($orchestrator, $this->approvalService);
    $workflow->setCurrentState($state);
    $workflow->run();

    $state->refresh();
    expect($state->isCompleted())->toBeTrue();
});

test('project insights generation with realistic project data', function () {
    $workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
        'title' => 'Main Work Order',
        'status' => WorkOrderStatus::Active,
        'due_date' => Carbon::now()->subDays(5), // Overdue
        'estimated_hours' => 40,
        'actual_hours' => 60, // 50% over estimate - scope creep
    ]);

    // Create overdue tasks
    Task::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'work_order_id' => $workOrder->id,
        'created_by_id' => $this->user->id,
        'title' => 'Critical Overdue Task',
        'status' => TaskStatus::InProgress,
        'due_date' => Carbon::now()->subDays(10),
    ]);

    // Create blocked tasks to form a bottleneck
    Task::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'work_order_id' => $workOrder->id,
        'created_by_id' => $this->user->id,
        'title' => 'Blocked Task 1',
        'status' => TaskStatus::Blocked,
        'is_blocked' => true,
        'blocker_reason' => BlockerReason::WaitingOnExternal,
        'blocker_details' => 'Waiting for API access from vendor',
    ]);

    Task::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'work_order_id' => $workOrder->id,
        'created_by_id' => $this->user->id,
        'title' => 'Blocked Task 2',
        'status' => TaskStatus::Blocked,
        'is_blocked' => true,
        'blocker_reason' => BlockerReason::WaitingOnExternal,
        'blocker_details' => 'Waiting for client approval',
    ]);

    // Create an overloaded team member
    $overloadedUser = User::factory()->create([
        'capacity_hours_per_week' => 40,
        'current_workload_hours' => 55, // Overloaded
    ]);
    $memberRole = $this->team->getRole('member');
    $this->team->users()->attach($overloadedUser, ['role_id' => $memberRole->id]);

    Task::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'work_order_id' => $workOrder->id,
        'created_by_id' => $this->user->id,
        'assigned_to_id' => $overloadedUser->id,
        'title' => 'Task for Overloaded User',
        'status' => TaskStatus::Todo,
        'estimated_hours' => 15,
    ]);

    // Generate insights
    $insightsService = new ProjectInsightsService();
    $insights = $insightsService->generateInsights($this->project);

    expect($insights)->toBeArray();
    expect(count($insights))->toBeGreaterThanOrEqual(3); // Should have overdue, bottleneck, and resource insights

    // Verify different insight types are generated
    $insightTypes = array_unique(array_map(fn ($i) => $i->type, $insights));
    expect($insightTypes)->toContain('overdue');
    expect($insightTypes)->toContain('bottleneck');

    // Verify severity levels are assigned
    $hasCriticalOrHigh = collect($insights)->contains(fn ($i) => in_array($i->severity, ['critical', 'high']));
    expect($hasCriticalOrHigh)->toBeTrue();
});

test('API endpoint triggers workflow and returns state', function () {
    $workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
        'title' => 'API Trigger Test Work Order',
        'description' => 'Testing the API trigger endpoint.',
        'pm_copilot_mode' => PMCopilotMode::Full,
    ]);

    $response = $this->actingAs($this->user)->post("/work/work-orders/{$workOrder->id}/pm-copilot/trigger");

    $response->assertSuccessful();

    $responseData = $response->json();
    expect($responseData)->toHaveKey('success');
    expect($responseData)->toHaveKey('workflow_state_id');

    // Verify workflow state was created
    $workflowState = AgentWorkflowState::find($responseData['workflow_state_id']);
    expect($workflowState)->not->toBeNull();
    expect($workflowState->workflow_class)->toBe(PMCopilotWorkflow::class);
});

test('approved suggestions create actual deliverables and tasks', function () {
    $workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
        'title' => 'Create Records Test',
        'description' => 'Test that approved suggestions create actual records.',
        'pm_copilot_mode' => PMCopilotMode::Staged,
    ]);

    // Create workflow state with suggestions
    $workflowState = AgentWorkflowState::create([
        'team_id' => $this->team->id,
        'ai_agent_id' => $this->agent->id,
        'workflow_class' => PMCopilotWorkflow::class,
        'current_node' => 'checkpoint_deliverables',
        'state_data' => [
            'input' => [
                'work_order_id' => $workOrder->id,
                'team_id' => $this->team->id,
                'pm_copilot_mode' => 'staged',
            ],
            'deliverable_suggestions' => [
                [
                    'title' => 'Test Deliverable to Create',
                    'description' => 'This should be created when approved',
                    'type' => 'document',
                    'acceptance_criteria' => ['Must exist in database'],
                    'confidence' => 'high',
                ],
            ],
        ],
        'paused_at' => now(),
        'approval_required' => true,
    ]);

    // Create inbox item
    $inboxItem = InboxItem::create([
        'team_id' => $this->team->id,
        'type' => InboxItemType::Approval,
        'title' => 'PM Copilot Suggestions',
        'content_preview' => 'Review generated deliverables',
        'full_content' => 'Full content',
        'source_type' => SourceType::AIAgent,
        'source_id' => 'agent-' . $this->agent->id,
        'source_name' => 'PM Copilot Agent',
        'approvable_type' => AgentWorkflowState::class,
        'approvable_id' => $workflowState->id,
        'urgency' => Urgency::Normal,
        'ai_confidence' => AIConfidence::High,
        'related_work_order_id' => $workOrder->id,
    ]);

    // Initial count
    $initialDeliverableCount = Deliverable::where('work_order_id', $workOrder->id)->count();

    // Approve via API
    $response = $this->actingAs($this->user)->post("/work/pm-copilot/suggestions/{$inboxItem->id}/approve", [
        'suggestion_type' => 'deliverable',
        'suggestion_index' => 0,
    ]);

    $response->assertStatus(200);

    // Verify deliverable was created
    $newCount = Deliverable::where('work_order_id', $workOrder->id)->count();
    expect($newCount)->toBe($initialDeliverableCount + 1);

    $createdDeliverable = Deliverable::where('work_order_id', $workOrder->id)
        ->where('title', 'Test Deliverable to Create')
        ->first();

    expect($createdDeliverable)->not->toBeNull();
    expect($createdDeliverable->status)->toBe(DeliverableStatus::Draft);
});

test('service integration: deliverable generator uses task breakdown correctly', function () {
    $workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
        'title' => 'Build Reporting Dashboard',
        'description' => 'Create a comprehensive reporting dashboard with charts and data export.',
        'status' => WorkOrderStatus::Active,
        'acceptance_criteria' => [
            'Interactive charts',
            'PDF export',
            'Date range filters',
        ],
    ]);

    // Create relevant playbook
    Playbook::factory()->create([
        'team_id' => $this->team->id,
        'created_by' => $this->user->id,
        'created_by_name' => $this->user->name,
        'name' => 'Dashboard Development',
        'type' => PlaybookType::Template,
        'tags' => ['dashboard', 'frontend', 'reporting'],
        'content' => [
            'tasks' => [
                ['title' => 'Design mockups', 'estimated_hours' => 8],
                ['title' => 'Implement charts', 'estimated_hours' => 16],
                ['title' => 'Add export functionality', 'estimated_hours' => 8],
            ],
        ],
    ]);

    // Step 1: Generate deliverables
    $deliverableService = new DeliverableGeneratorService();
    $deliverableAlternatives = $deliverableService->generateAlternatives($workOrder);

    expect($deliverableAlternatives)->toBeArray();
    expect(count($deliverableAlternatives))->toBeGreaterThanOrEqual(2);

    // Convert first alternative to array format for task breakdown
    $deliverables = array_map(fn ($d) => $d->toArray(), $deliverableAlternatives);

    // Step 2: Generate task breakdown based on deliverables
    $taskService = new TaskBreakdownService();
    $taskAlternatives = $taskService->generateBreakdown($workOrder, $deliverables);

    expect($taskAlternatives)->toBeArray();
    expect(count($taskAlternatives))->toBeGreaterThanOrEqual(2);

    // Verify tasks are generated with hour estimates
    $firstAlternative = $taskAlternatives[0];
    expect($firstAlternative)->toHaveKey('tasks');
    expect(count($firstAlternative['tasks']))->toBeGreaterThan(0);

    foreach ($firstAlternative['tasks'] as $task) {
        expect($task->estimatedHours)->toBeGreaterThan(0);
        expect($task->position)->toBeGreaterThan(0);
    }
});

test('workflow handles missing playbook gracefully', function () {
    // Work order without any matching playbooks
    $workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
        'title' => 'Unique Task Without Template',
        'description' => 'This is a completely unique task with no matching playbook.',
        'status' => WorkOrderStatus::Active,
        'pm_copilot_mode' => PMCopilotMode::Full,
    ]);

    // Ensure no playbooks exist for this team that would match
    Playbook::where('team_id', $this->team->id)->delete();

    $state = $this->orchestrator->invokePMCopilot($workOrder, $this->agent);
    $workflow = new PMCopilotWorkflow($this->orchestrator, $this->approvalService);
    $workflow->setCurrentState($state);
    $workflow->run();

    $state->refresh();

    // Should still complete successfully
    expect($state->isCompleted())->toBeTrue();

    // Should still generate alternatives (with potentially lower confidence)
    expect($state->state_data)->toHaveKey('deliverable_alternatives');
    expect($state->state_data)->toHaveKey('task_breakdown');
});
