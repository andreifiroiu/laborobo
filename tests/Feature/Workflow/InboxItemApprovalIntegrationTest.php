<?php

declare(strict_types=1);

use App\Enums\InboxItemType;
use App\Enums\TaskStatus;
use App\Enums\Urgency;
use App\Enums\WorkOrderStatus;
use App\Models\InboxItem;
use App\Models\Party;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\WorkflowTransitionService;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->team = $this->user->createTeam(['name' => 'Test Team']);
    $this->user->current_team_id = $this->team->id;
    $this->user->save();

    $this->reviewer = User::factory()->create();

    $this->party = Party::factory()->create(['team_id' => $this->team->id]);
    $this->project = Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $this->party->id,
        'owner_id' => $this->reviewer->id,
    ]);
    $this->workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
        'accountable_id' => $this->reviewer->id,
        'status' => WorkOrderStatus::Active,
    ]);

    $this->service = new WorkflowTransitionService;
});

test('InboxItem created when Task enters InReview', function () {
    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
        'status' => TaskStatus::InProgress,
        'due_date' => now()->addDays(10),
    ]);

    // Transition task to InReview
    $this->service->transition(
        item: $task,
        actor: $this->user,
        toStatus: TaskStatus::InReview,
    );

    // Verify InboxItem was created
    $inboxItem = InboxItem::where('approvable_type', Task::class)
        ->where('approvable_id', $task->id)
        ->first();

    expect($inboxItem)->not->toBeNull();
    expect($inboxItem->type)->toBe(InboxItemType::Approval);
    expect($inboxItem->team_id)->toBe($this->team->id);
    expect($inboxItem->title)->toContain('Task ready for review');
    expect($inboxItem->title)->toContain($task->title);
    expect($inboxItem->related_task_id)->toBe($task->id);
    expect($inboxItem->related_work_order_id)->toBe($this->workOrder->id);
    expect($inboxItem->related_project_id)->toBe($this->project->id);
});

test('InboxItem created when WorkOrder enters InReview', function () {
    // Update work order to Active status first
    $workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
        'accountable_id' => $this->reviewer->id,
        'status' => WorkOrderStatus::Active,
        'due_date' => now()->addDays(5),
    ]);

    // Transition work order to InReview
    $this->service->transition(
        item: $workOrder,
        actor: $this->user,
        toStatus: WorkOrderStatus::InReview,
    );

    // Verify InboxItem was created
    $inboxItem = InboxItem::where('approvable_type', WorkOrder::class)
        ->where('approvable_id', $workOrder->id)
        ->first();

    expect($inboxItem)->not->toBeNull();
    expect($inboxItem->type)->toBe(InboxItemType::Approval);
    expect($inboxItem->team_id)->toBe($this->team->id);
    expect($inboxItem->title)->toContain('Work Order ready for review');
    expect($inboxItem->title)->toContain($workOrder->title);
    expect($inboxItem->related_work_order_id)->toBe($workOrder->id);
    expect($inboxItem->related_project_id)->toBe($this->project->id);
    expect($inboxItem->related_task_id)->toBeNull();
});

test('InboxItem includes correct reviewer and context from RACI', function () {
    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
        'reviewer_id' => $this->reviewer->id,
        'status' => TaskStatus::InProgress,
        'description' => 'This is a test task description.',
    ]);

    // Transition task to InReview
    $this->service->transition(
        item: $task,
        actor: $this->user,
        toStatus: TaskStatus::InReview,
    );

    // Verify InboxItem was created with correct reviewer
    $inboxItem = InboxItem::where('approvable_type', Task::class)
        ->where('approvable_id', $task->id)
        ->first();

    expect($inboxItem)->not->toBeNull();
    expect($inboxItem->reviewer_id)->toBe($this->reviewer->id);
    expect($inboxItem->source_name)->toBe($this->user->name);
    expect($inboxItem->content_preview)->toContain($task->title);
    expect($inboxItem->full_content)->toContain('This is a test task description.');
    expect($inboxItem->related_project_name)->toBe($this->project->name);
    expect($inboxItem->related_work_order_title)->toBe($this->workOrder->title);
});

test('InboxItem resolved on approval with approved_at timestamp', function () {
    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
        'status' => TaskStatus::InProgress,
    ]);

    // Transition task to InReview - creates InboxItem
    $this->service->transition(
        item: $task,
        actor: $this->user,
        toStatus: TaskStatus::InReview,
    );

    // Verify InboxItem exists
    $inboxItem = InboxItem::where('approvable_type', Task::class)
        ->where('approvable_id', $task->id)
        ->first();
    expect($inboxItem)->not->toBeNull();

    // Approve the task - should resolve InboxItem
    $task->refresh();
    $this->service->transition(
        item: $task,
        actor: $this->user,
        toStatus: TaskStatus::Approved,
    );

    // Verify InboxItem was soft deleted with approved_at timestamp
    $inboxItem->refresh();
    expect($inboxItem->approved_at)->not->toBeNull();
    expect($inboxItem->rejected_at)->toBeNull();
    expect($inboxItem->deleted_at)->not->toBeNull();

    // Should not appear in normal queries
    expect(InboxItem::where('id', $inboxItem->id)->first())->toBeNull();

    // Should appear with trashed
    expect(InboxItem::withTrashed()->where('id', $inboxItem->id)->first())->not->toBeNull();
});

test('InboxItem resolved on rejection with rejected_at timestamp', function () {
    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
        'status' => TaskStatus::InProgress,
    ]);

    // Transition task to InReview - creates InboxItem
    $this->service->transition(
        item: $task,
        actor: $this->user,
        toStatus: TaskStatus::InReview,
    );

    // Verify InboxItem exists
    $inboxItem = InboxItem::where('approvable_type', Task::class)
        ->where('approvable_id', $task->id)
        ->first();
    expect($inboxItem)->not->toBeNull();

    // Reject the task - should resolve InboxItem
    $task->refresh();
    $this->service->transition(
        item: $task,
        actor: $this->user,
        toStatus: TaskStatus::RevisionRequested,
        comment: 'Please fix the issues.',
    );

    // Verify InboxItem was soft deleted with rejected_at timestamp
    $inboxItem->refresh();
    expect($inboxItem->rejected_at)->not->toBeNull();
    expect($inboxItem->approved_at)->toBeNull();
    expect($inboxItem->deleted_at)->not->toBeNull();
});

test('InboxItem urgency set based on due date proximity', function () {
    // Task due in 1 day - should be urgent
    $urgentTask = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
        'status' => TaskStatus::InProgress,
        'due_date' => now()->addDay(),
    ]);

    $this->service->transition(
        item: $urgentTask,
        actor: $this->user,
        toStatus: TaskStatus::InReview,
    );

    $urgentInboxItem = InboxItem::where('approvable_id', $urgentTask->id)
        ->where('approvable_type', Task::class)
        ->first();
    expect($urgentInboxItem->urgency)->toBe(Urgency::Urgent);

    // Task due in 5 days - should be high
    $highTask = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
        'status' => TaskStatus::InProgress,
        'due_date' => now()->addDays(5),
    ]);

    $this->service->transition(
        item: $highTask,
        actor: $this->user,
        toStatus: TaskStatus::InReview,
    );

    $highInboxItem = InboxItem::where('approvable_id', $highTask->id)
        ->where('approvable_type', Task::class)
        ->first();
    expect($highInboxItem->urgency)->toBe(Urgency::High);

    // Task due in 14 days - should be normal
    $normalTask = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
        'status' => TaskStatus::InProgress,
        'due_date' => now()->addDays(14),
    ]);

    $this->service->transition(
        item: $normalTask,
        actor: $this->user,
        toStatus: TaskStatus::InReview,
    );

    $normalInboxItem = InboxItem::where('approvable_id', $normalTask->id)
        ->where('approvable_type', Task::class)
        ->first();
    expect($normalInboxItem->urgency)->toBe(Urgency::Normal);
});
