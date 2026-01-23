<?php

declare(strict_types=1);

use App\Agents\Tools\CreateDeliverableTool;
use App\Agents\Tools\CreateTaskTool;
use App\Agents\Tools\GetProjectInsightsTool;
use App\Enums\BlockerReason;
use App\Enums\DeliverableStatus;
use App\Enums\TaskStatus;
use App\Enums\WorkOrderStatus;
use App\Models\Deliverable;
use App\Models\Party;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkOrder;
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
    ]);

    $this->workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'title' => 'Test Work Order',
        'description' => 'A work order for testing',
        'status' => WorkOrderStatus::Active,
    ]);
});

// CreateDeliverableTool Tests

test('CreateDeliverableTool creates deliverable with valid params', function () {
    $tool = new CreateDeliverableTool();

    $result = $tool->execute([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'title' => 'Project Report',
        'description' => 'A comprehensive project status report',
        'type' => 'report',
        'acceptance_criteria' => [
            'Contains executive summary',
            'Includes timeline analysis',
            'Has budget breakdown',
        ],
    ]);

    expect($result['success'])->toBeTrue();
    expect($result['deliverable'])->toBeArray();
    expect($result['deliverable']['title'])->toBe('Project Report');
    expect($result['deliverable']['status'])->toBe('draft');
    expect($result['deliverable']['type'])->toBe('report');
    expect($result['confidence'])->toBeString();

    // Verify deliverable exists in database
    $deliverable = Deliverable::find($result['deliverable']['id']);
    expect($deliverable)->not->toBeNull();
    expect($deliverable->title)->toBe('Project Report');
    expect($deliverable->status)->toBe(DeliverableStatus::Draft);
    expect($deliverable->team_id)->toBe($this->team->id);
    expect($deliverable->work_order_id)->toBe($this->workOrder->id);
});

test('CreateDeliverableTool validates required fields', function () {
    $tool = new CreateDeliverableTool();

    // Missing team_id
    expect(fn () => $tool->execute([
        'work_order_id' => $this->workOrder->id,
        'title' => 'Test Deliverable',
    ]))->toThrow(InvalidArgumentException::class, 'team_id is required');

    // Missing work_order_id
    expect(fn () => $tool->execute([
        'team_id' => $this->team->id,
        'title' => 'Test Deliverable',
    ]))->toThrow(InvalidArgumentException::class, 'work_order_id is required');

    // Missing title
    expect(fn () => $tool->execute([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
    ]))->toThrow(InvalidArgumentException::class, 'title is required');
});

// CreateTaskTool Tests

test('CreateTaskTool creates task with estimates and position', function () {
    $tool = new CreateTaskTool();

    $result = $tool->execute([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'title' => 'Implement Login Feature',
        'description' => 'Build the user login functionality',
        'estimated_hours' => 8.5,
        'position_in_work_order' => 1,
    ]);

    expect($result['success'])->toBeTrue();
    expect($result['task'])->toBeArray();
    expect($result['task']['title'])->toBe('Implement Login Feature');
    expect($result['task']['status'])->toBe('todo');
    expect($result['task']['estimated_hours'])->toBe('8.50');
    expect($result['task']['position_in_work_order'])->toBe(1);
    expect($result['confidence'])->toBeString();

    // Verify task exists in database
    $task = Task::find($result['task']['id']);
    expect($task)->not->toBeNull();
    expect($task->title)->toBe('Implement Login Feature');
    expect($task->status)->toBe(TaskStatus::Todo);
    expect((float) $task->estimated_hours)->toBe(8.5);
    expect($task->position_in_work_order)->toBe(1);
});

test('CreateTaskTool handles checklist items from playbook', function () {
    $tool = new CreateTaskTool();

    $checklistItems = [
        ['id' => 'item-1', 'text' => 'Review requirements', 'completed' => false],
        ['id' => 'item-2', 'text' => 'Write unit tests', 'completed' => false],
        ['id' => 'item-3', 'text' => 'Implement feature', 'completed' => false],
        ['id' => 'item-4', 'text' => 'Code review', 'completed' => false],
    ];

    $result = $tool->execute([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'title' => 'Complete Feature Task',
        'checklist_items' => $checklistItems,
        'dependencies' => [1, 2],
    ]);

    expect($result['success'])->toBeTrue();
    expect($result['task']['checklist_items'])->toBe($checklistItems);
    expect($result['task']['dependencies'])->toBe([1, 2]);
    // Verify default estimated_hours is 0 when not provided
    expect($result['task']['estimated_hours'])->toBe('0.00');

    // Verify task in database has checklist items
    $task = Task::find($result['task']['id']);
    expect($task->checklist_items)->toBeArray();
    expect(count($task->checklist_items))->toBe(4);
    expect($task->checklist_items[0]['text'])->toBe('Review requirements');
    expect($task->dependencies)->toContain(1);
    expect($task->dependencies)->toContain(2);
});

// GetProjectInsightsTool Tests

test('GetProjectInsightsTool returns overdue items', function () {
    // Create overdue task
    Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'project_id' => $this->project->id,
        'title' => 'Overdue Task',
        'due_date' => Carbon::now()->subDays(5),
        'status' => TaskStatus::InProgress,
    ]);

    // Create overdue work order
    WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'title' => 'Overdue Work Order',
        'due_date' => Carbon::now()->subDays(3),
        'status' => WorkOrderStatus::Active,
    ]);

    $tool = new GetProjectInsightsTool();

    $result = $tool->execute([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
    ]);

    expect($result['success'])->toBeTrue();
    expect($result['insights'])->toBeArray();
    expect($result['insights']['overdue_items'])->toBeArray();
    expect(count($result['insights']['overdue_items']['tasks']))->toBeGreaterThanOrEqual(1);
    expect(count($result['insights']['overdue_items']['work_orders']))->toBeGreaterThanOrEqual(1);
});

test('GetProjectInsightsTool identifies blocked tasks', function () {
    // Create blocked task using BlockerReason enum
    Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'project_id' => $this->project->id,
        'title' => 'Blocked Task',
        'status' => TaskStatus::Blocked,
        'is_blocked' => true,
        'blocker_reason' => BlockerReason::MissingInformation,
        'blocker_details' => 'Waiting for client feedback',
    ]);

    $tool = new GetProjectInsightsTool();

    $result = $tool->execute([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
    ]);

    expect($result['success'])->toBeTrue();
    expect($result['insights']['bottlenecks'])->toBeArray();
    expect(count($result['insights']['bottlenecks']['blocked_tasks']))->toBeGreaterThanOrEqual(1);

    // Verify blocked task details
    $blockedTask = collect($result['insights']['bottlenecks']['blocked_tasks'])->first();
    expect($blockedTask['title'])->toBe('Blocked Task');
    expect($blockedTask['is_blocked'])->toBeTrue();
});
