<?php

declare(strict_types=1);

use App\Enums\AIConfidence;
use App\Enums\BlockerReason;
use App\Enums\DeliverableStatus;
use App\Enums\ProjectStatus;
use App\Enums\TaskStatus;
use App\Enums\WorkOrderStatus;
use App\Models\Deliverable;
use App\Models\Party;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\ProjectInsightsService;
use App\ValueObjects\ProjectInsight;
use Carbon\Carbon;

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
        'status' => ProjectStatus::Active,
    ]);

    $this->service = new ProjectInsightsService();
});

test('service flags overdue items correctly', function () {
    $workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
        'title' => 'Active Work Order',
        'status' => WorkOrderStatus::Active,
        'due_date' => Carbon::now()->subDays(10), // 10 days overdue
    ]);

    // Create critically overdue task (7+ days)
    Task::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'work_order_id' => $workOrder->id,
        'created_by_id' => $this->user->id,
        'title' => 'Critically Overdue Task',
        'status' => TaskStatus::InProgress,
        'due_date' => Carbon::now()->subDays(8),
    ]);

    // Create medium overdue task (1-2 days)
    Task::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'work_order_id' => $workOrder->id,
        'created_by_id' => $this->user->id,
        'title' => 'Recently Overdue Task',
        'status' => TaskStatus::Todo,
        'due_date' => Carbon::now()->subDays(1),
    ]);

    // Create a deliverable with a past expected date (not delivered)
    Deliverable::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'work_order_id' => $workOrder->id,
        'title' => 'Overdue Deliverable',
        'status' => DeliverableStatus::Draft,
        'delivered_date' => Carbon::now()->subDays(5),
    ]);

    $insights = $this->service->generateInsights($this->project);

    expect($insights)->toBeArray();
    expect(count($insights))->toBeGreaterThanOrEqual(1);

    // Check that overdue insights were generated
    $overdueInsights = array_filter(
        $insights,
        fn (ProjectInsight $i) => $i->type === ProjectInsight::TYPE_OVERDUE
    );

    expect(count($overdueInsights))->toBeGreaterThanOrEqual(1);

    // Check that at least one insight is marked as critical (for the 8-day overdue task)
    $criticalInsights = array_filter(
        $overdueInsights,
        fn (ProjectInsight $i) => $i->severity === ProjectInsight::SEVERITY_CRITICAL
    );

    expect(count($criticalInsights))->toBeGreaterThanOrEqual(1);

    // Verify insight structure
    $firstOverdueInsight = array_values($overdueInsights)[0];
    expect($firstOverdueInsight)->toBeInstanceOf(ProjectInsight::class);
    expect($firstOverdueInsight->title)->toBeString();
    expect($firstOverdueInsight->description)->toBeString();
    expect($firstOverdueInsight->suggestion)->toBeString();
});

test('bottleneck identification from blocked tasks', function () {
    $workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
        'title' => 'Work Order with Blocked Tasks',
        'status' => WorkOrderStatus::Active,
    ]);

    // Create multiple blocked tasks with the same reason
    Task::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'work_order_id' => $workOrder->id,
        'created_by_id' => $this->user->id,
        'title' => 'Blocked Task 1',
        'status' => TaskStatus::InProgress,
        'is_blocked' => true,
        'blocker_reason' => BlockerReason::WaitingOnExternal,
        'blocker_details' => 'Waiting for client approval',
    ]);

    Task::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'work_order_id' => $workOrder->id,
        'created_by_id' => $this->user->id,
        'title' => 'Blocked Task 2',
        'status' => TaskStatus::Todo,
        'is_blocked' => true,
        'blocker_reason' => BlockerReason::WaitingOnExternal,
        'blocker_details' => 'Waiting for vendor response',
    ]);

    // Create a blocked task with different reason
    Task::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'work_order_id' => $workOrder->id,
        'created_by_id' => $this->user->id,
        'title' => 'Blocked Task 3',
        'status' => TaskStatus::InProgress,
        'is_blocked' => true,
        'blocker_reason' => BlockerReason::TechnicalIssue,
        'blocker_details' => 'Server configuration issue',
    ]);

    $insights = $this->service->generateInsights($this->project);

    expect($insights)->toBeArray();

    // Filter to bottleneck insights
    $bottleneckInsights = array_filter(
        $insights,
        fn (ProjectInsight $i) => $i->type === ProjectInsight::TYPE_BOTTLENECK
    );

    expect(count($bottleneckInsights))->toBeGreaterThanOrEqual(1);

    // Check that insights are grouped by blocker reason
    $externalBlockerInsight = array_filter(
        $bottleneckInsights,
        fn (ProjectInsight $i) => str_contains($i->title, 'External') || str_contains($i->description, 'Waiting on External')
    );

    expect(count($externalBlockerInsight))->toBeGreaterThanOrEqual(1);

    // Verify affected items are included
    $firstBottleneckInsight = array_values($bottleneckInsights)[0];
    expect($firstBottleneckInsight->affectedItems)->toBeArray();
    expect(count($firstBottleneckInsight->affectedItems))->toBeGreaterThanOrEqual(1);
});

test('resource reallocation suggestions generated', function () {
    // Create additional team members with varying capacity
    $overloadedUser = User::factory()->create([
        'capacity_hours_per_week' => 40,
        'current_workload_hours' => 50, // 125% utilization
    ]);
    $memberRole = $this->team->getRole('member');
    $this->team->users()->attach($overloadedUser, ['role_id' => $memberRole->id]);

    $availableUser = User::factory()->create([
        'capacity_hours_per_week' => 40,
        'current_workload_hours' => 10, // 25% utilization
    ]);
    $this->team->users()->attach($availableUser, ['role_id' => $memberRole->id]);

    $workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
        'title' => 'Work Order',
        'status' => WorkOrderStatus::Active,
    ]);

    // Create tasks assigned to the overloaded user
    Task::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'work_order_id' => $workOrder->id,
        'created_by_id' => $this->user->id,
        'assigned_to_id' => $overloadedUser->id,
        'title' => 'Heavy Task 1',
        'status' => TaskStatus::Todo,
        'estimated_hours' => 20,
    ]);

    $insights = $this->service->generateInsights($this->project);

    expect($insights)->toBeArray();

    // Filter to resource insights
    $resourceInsights = array_filter(
        $insights,
        fn (ProjectInsight $i) => $i->type === ProjectInsight::TYPE_RESOURCE
    );

    expect(count($resourceInsights))->toBeGreaterThanOrEqual(1);

    // Check that the insight mentions the overloaded member
    $overloadedInsight = array_filter(
        $resourceInsights,
        fn (ProjectInsight $i) => str_contains($i->title, 'Overloaded') || str_contains($i->description, 'overloaded')
    );

    expect(count($overloadedInsight))->toBeGreaterThanOrEqual(1);

    // Verify suggestion mentions redistribution
    $firstResourceInsight = array_values($overloadedInsight)[0];
    expect($firstResourceInsight->suggestion)->toBeString();
    expect(strlen($firstResourceInsight->suggestion))->toBeGreaterThan(10);
});

test('scope creep detection based on estimates vs actual', function () {
    // Create work orders that have exceeded their estimates
    $workOrder1 = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
        'title' => 'Work Order Over Budget',
        'status' => WorkOrderStatus::Active,
        'estimated_hours' => 10,
        'actual_hours' => 15, // 50% over estimate
    ]);

    $workOrder2 = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
        'title' => 'Another Over Budget WO',
        'status' => WorkOrderStatus::InReview,
        'estimated_hours' => 20,
        'actual_hours' => 30, // 50% over estimate
    ]);

    // Create a work order within estimate (should not trigger scope creep)
    WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
        'title' => 'On Budget WO',
        'status' => WorkOrderStatus::Active,
        'estimated_hours' => 10,
        'actual_hours' => 9, // Under estimate
    ]);

    // Create tasks that are over estimate
    Task::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'work_order_id' => $workOrder1->id,
        'created_by_id' => $this->user->id,
        'title' => 'Over Estimate Task 1',
        'status' => TaskStatus::InProgress,
        'estimated_hours' => 5,
        'actual_hours' => 8, // 60% over estimate
    ]);

    Task::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'work_order_id' => $workOrder1->id,
        'created_by_id' => $this->user->id,
        'title' => 'Over Estimate Task 2',
        'status' => TaskStatus::Done,
        'estimated_hours' => 3,
        'actual_hours' => 5, // 67% over estimate
    ]);

    Task::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'work_order_id' => $workOrder2->id,
        'created_by_id' => $this->user->id,
        'title' => 'Over Estimate Task 3',
        'status' => TaskStatus::InProgress,
        'estimated_hours' => 4,
        'actual_hours' => 7, // 75% over estimate
    ]);

    $insights = $this->service->generateInsights($this->project);

    expect($insights)->toBeArray();

    // Filter to scope creep insights
    $scopeCreepInsights = array_filter(
        $insights,
        fn (ProjectInsight $i) => $i->type === ProjectInsight::TYPE_SCOPE_CREEP
    );

    expect(count($scopeCreepInsights))->toBeGreaterThanOrEqual(1);

    // Verify the insight captures the variance
    $firstScopeInsight = array_values($scopeCreepInsights)[0];
    expect($firstScopeInsight->title)->toContain('Scope Creep');
    expect($firstScopeInsight->description)->toContain('%');

    // Verify affected items include the over-budget work orders
    expect($firstScopeInsight->affectedItems)->toBeArray();
    expect(count($firstScopeInsight->affectedItems))->toBeGreaterThanOrEqual(1);
});

test('project insight value object serializes correctly', function () {
    $insight = new ProjectInsight(
        type: ProjectInsight::TYPE_OVERDUE,
        severity: ProjectInsight::SEVERITY_HIGH,
        title: 'Overdue Tasks',
        description: '5 tasks are overdue',
        affectedItems: [
            ['id' => 1, 'type' => 'task', 'title' => 'Task 1'],
            ['id' => 2, 'type' => 'task', 'title' => 'Task 2'],
        ],
        suggestion: 'Review and prioritize these tasks',
        confidence: AIConfidence::High
    );

    $array = $insight->toArray();

    expect($array)->toBeArray();
    expect($array['type'])->toBe('overdue');
    expect($array['severity'])->toBe('high');
    expect($array['title'])->toBe('Overdue Tasks');
    expect($array['description'])->toBe('5 tasks are overdue');
    expect($array['affectedItems'])->toHaveCount(2);
    expect($array['suggestion'])->toBe('Review and prioritize these tasks');
    expect($array['confidence'])->toBe('high');

    // Test isUrgent
    expect($insight->isUrgent())->toBeTrue();

    // Test hasActionableSuggestion
    expect($insight->hasActionableSuggestion())->toBeTrue();

    // Test getTypeLabel
    expect($insight->getTypeLabel())->toBe('Overdue Items');
});

test('service returns empty insights for healthy project', function () {
    $workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
        'title' => 'Healthy Work Order',
        'status' => WorkOrderStatus::Active,
        'due_date' => Carbon::now()->addDays(10), // Due in the future
        'estimated_hours' => 10,
        'actual_hours' => 8, // Under estimate
    ]);

    // Create task that's on track
    Task::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'work_order_id' => $workOrder->id,
        'created_by_id' => $this->user->id,
        'assigned_to_id' => $this->user->id,
        'title' => 'On Track Task',
        'status' => TaskStatus::InProgress,
        'due_date' => Carbon::now()->addDays(5),
        'is_blocked' => false,
        'estimated_hours' => 4,
        'actual_hours' => 2,
    ]);

    $insights = $this->service->generateInsights($this->project);

    // For a healthy project, we may still get resource insights
    // but should not have overdue, bottleneck, or scope creep issues
    $criticalInsights = array_filter(
        $insights,
        fn (ProjectInsight $i) => $i->severity === ProjectInsight::SEVERITY_CRITICAL
    );

    expect(count($criticalInsights))->toBe(0);

    // Should not have overdue insights
    $overdueInsights = array_filter(
        $insights,
        fn (ProjectInsight $i) => $i->type === ProjectInsight::TYPE_OVERDUE
    );
    expect(count($overdueInsights))->toBe(0);

    // Should not have bottleneck insights
    $bottleneckInsights = array_filter(
        $insights,
        fn (ProjectInsight $i) => $i->type === ProjectInsight::TYPE_BOTTLENECK
    );
    expect(count($bottleneckInsights))->toBe(0);

    // Should not have scope creep insights
    $scopeCreepInsights = array_filter(
        $insights,
        fn (ProjectInsight $i) => $i->type === ProjectInsight::TYPE_SCOPE_CREEP
    );
    expect(count($scopeCreepInsights))->toBe(0);
});
