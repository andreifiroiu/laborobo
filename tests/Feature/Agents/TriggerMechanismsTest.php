<?php

declare(strict_types=1);

use App\Agents\Workflows\PMCopilotWorkflow;
use App\Enums\AgentType;
use App\Events\WorkOrderCreated;
use App\Jobs\ProcessPMCopilotTrigger;
use App\Listeners\TriggerPMCopilotOnWorkOrderCreated;
use App\Models\AgentConfiguration;
use App\Models\AgentWorkflowState;
use App\Models\AIAgent;
use App\Models\Deliverable;
use App\Models\GlobalAISettings;
use App\Models\Party;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\AgentOrchestrator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

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
});

test('WorkOrderCreated event triggers PM Copilot when auto-suggest is enabled', function () {
    Queue::fake();

    // Enable PM Copilot auto-suggest in GlobalAISettings
    $globalSettings = GlobalAISettings::create([
        'team_id' => $this->team->id,
        'total_monthly_budget' => 1000.00,
        'current_month_spend' => 0.00,
        'pm_copilot_auto_suggest' => true,
    ]);

    $workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
        'title' => 'New Work Order',
        'description' => 'A work order that should trigger PM Copilot',
    ]);

    // Trigger the event
    $listener = new TriggerPMCopilotOnWorkOrderCreated();
    $listener->handle(new WorkOrderCreated($workOrder));

    // Verify the job was dispatched
    Queue::assertPushed(ProcessPMCopilotTrigger::class, function ($job) use ($workOrder) {
        return $job->workOrder->id === $workOrder->id;
    });
});

test('WorkOrderCreated event does not trigger PM Copilot when auto-suggest is disabled', function () {
    Queue::fake();

    // Disable PM Copilot auto-suggest in GlobalAISettings
    $globalSettings = GlobalAISettings::create([
        'team_id' => $this->team->id,
        'total_monthly_budget' => 1000.00,
        'current_month_spend' => 0.00,
        'pm_copilot_auto_suggest' => false,
    ]);

    $workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
        'title' => 'New Work Order',
        'description' => 'A work order that should not trigger PM Copilot',
    ]);

    // Trigger the event
    $listener = new TriggerPMCopilotOnWorkOrderCreated();
    $listener->handle(new WorkOrderCreated($workOrder));

    // Verify the job was NOT dispatched
    Queue::assertNotPushed(ProcessPMCopilotTrigger::class);
});

test('manual trigger via AgentOrchestrator invokePMCopilot starts workflow', function () {
    $globalSettings = GlobalAISettings::create([
        'team_id' => $this->team->id,
        'total_monthly_budget' => 1000.00,
        'current_month_spend' => 0.00,
        'pm_copilot_auto_suggest' => false,
    ]);

    $workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
        'title' => 'Manual Trigger Work Order',
        'description' => 'A work order for manual PM Copilot trigger',
        'acceptance_criteria' => ['Criteria 1', 'Criteria 2'],
    ]);

    $orchestrator = app(AgentOrchestrator::class);

    // Invoke PM Copilot manually
    $state = $orchestrator->invokePMCopilot($workOrder, $this->agent);

    expect($state)->toBeInstanceOf(AgentWorkflowState::class);
    expect($state->workflow_class)->toBe(PMCopilotWorkflow::class);
    expect($state->team_id)->toBe($this->team->id);
    expect($state->state_data['input']['work_order_id'])->toBe($workOrder->id);
});

test('PM Copilot does not trigger for work orders that already have deliverables', function () {
    Queue::fake();

    $globalSettings = GlobalAISettings::create([
        'team_id' => $this->team->id,
        'total_monthly_budget' => 1000.00,
        'current_month_spend' => 0.00,
        'pm_copilot_auto_suggest' => true,
    ]);

    $workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
        'title' => 'Work Order With Deliverables',
        'description' => 'This work order already has deliverables',
    ]);

    // Create an existing deliverable for the work order
    Deliverable::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $workOrder->id,
        'title' => 'Existing Deliverable',
    ]);

    // Trigger the event
    $listener = new TriggerPMCopilotOnWorkOrderCreated();
    $listener->handle(new WorkOrderCreated($workOrder));

    // Verify the job was NOT dispatched because work order already has deliverables
    Queue::assertNotPushed(ProcessPMCopilotTrigger::class);
});

test('PM Copilot does not trigger for work orders that already have tasks', function () {
    Queue::fake();

    $globalSettings = GlobalAISettings::create([
        'team_id' => $this->team->id,
        'total_monthly_budget' => 1000.00,
        'current_month_spend' => 0.00,
        'pm_copilot_auto_suggest' => true,
    ]);

    $workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
        'title' => 'Work Order With Tasks',
        'description' => 'This work order already has tasks',
    ]);

    // Create an existing task for the work order
    Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $workOrder->id,
        'created_by_id' => $this->user->id,
        'title' => 'Existing Task',
    ]);

    // Trigger the event
    $listener = new TriggerPMCopilotOnWorkOrderCreated();
    $listener->handle(new WorkOrderCreated($workOrder));

    // Verify the job was NOT dispatched because work order already has tasks
    Queue::assertNotPushed(ProcessPMCopilotTrigger::class);
});

test('auto-suggest setting defaults to false for new teams', function () {
    $newUser = User::factory()->create();
    $newTeam = $newUser->createTeam(['name' => 'New Team']);

    // Get or create settings for the team
    $settings = GlobalAISettings::forTeam($newTeam);

    // Verify default value is false (opt-in behavior)
    expect($settings->pm_copilot_auto_suggest)->toBeFalse();
});
