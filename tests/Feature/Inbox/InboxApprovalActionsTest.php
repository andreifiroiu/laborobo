<?php

declare(strict_types=1);

use App\Enums\InboxItemType;
use App\Enums\SourceType;
use App\Enums\TaskStatus;
use App\Enums\Urgency;
use App\Models\InboxItem;
use App\Models\Party;
use App\Models\Project;
use App\Models\StatusTransition;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkOrder;

beforeEach(function () {
    // Owner who creates the team
    $this->owner = User::factory()->create();
    $this->team = $this->owner->createTeam(['name' => 'Test Team']);
    $this->owner->current_team_id = $this->team->id;
    $this->owner->save();

    // Submitter (separate from reviewer)
    $this->submitter = User::factory()->create();
    $this->submitter->current_team_id = $this->team->id;
    $this->submitter->save();

    // The owner acts as reviewer in these tests
    $this->reviewer = $this->owner;

    $this->party = Party::factory()->create(['team_id' => $this->team->id]);
    $this->project = Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $this->party->id,
        'owner_id' => $this->owner->id,
    ]);
    $this->workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->owner->id,
        'accountable_id' => $this->owner->id,
    ]);
});

test('approving inbox item transitions task to approved status', function () {
    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->submitter->id,
        'assigned_to_id' => $this->submitter->id,
        'status' => TaskStatus::InReview,
    ]);

    // Create transition record for the review submission (by submitter)
    StatusTransition::create([
        'transitionable_type' => Task::class,
        'transitionable_id' => $task->id,
        'user_id' => $this->submitter->id,
        'from_status' => 'in_progress',
        'to_status' => 'in_review',
        'created_at' => now(),
    ]);

    $inboxItem = InboxItem::factory()->create([
        'team_id' => $this->team->id,
        'type' => InboxItemType::Approval,
        'title' => 'Task ready for review',
        'approvable_type' => Task::class,
        'approvable_id' => $task->id,
        'source_id' => "user-{$this->submitter->id}",
        'source_name' => $this->submitter->name,
        'source_type' => SourceType::Human,
        'urgency' => Urgency::Normal,
    ]);

    $this->actingAs($this->reviewer)
        ->post(route('inbox.approve', $inboxItem))
        ->assertRedirect();

    $task->refresh();
    expect($task->status)->toBe(TaskStatus::Approved);

    // Inbox item should be soft-deleted
    expect($inboxItem->fresh()->trashed())->toBeTrue();
});

test('rejecting inbox item transitions task to in progress via revision requested', function () {
    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->submitter->id,
        'assigned_to_id' => $this->submitter->id,
        'status' => TaskStatus::InReview,
    ]);

    // Create transition record for the review submission (by submitter)
    StatusTransition::create([
        'transitionable_type' => Task::class,
        'transitionable_id' => $task->id,
        'user_id' => $this->submitter->id,
        'from_status' => 'in_progress',
        'to_status' => 'in_review',
        'created_at' => now(),
    ]);

    $inboxItem = InboxItem::factory()->create([
        'team_id' => $this->team->id,
        'type' => InboxItemType::Approval,
        'title' => 'Task ready for review',
        'approvable_type' => Task::class,
        'approvable_id' => $task->id,
        'source_id' => "user-{$this->submitter->id}",
        'source_name' => $this->submitter->name,
        'source_type' => SourceType::Human,
        'urgency' => Urgency::Normal,
    ]);

    $this->actingAs($this->reviewer)
        ->post(route('inbox.reject', $inboxItem), [
            'feedback' => 'Please fix the formatting issues.',
        ])
        ->assertRedirect();

    $task->refresh();
    // After RevisionRequested, it auto-transitions to InProgress
    expect($task->status)->toBe(TaskStatus::InProgress);

    // Inbox item should be soft-deleted
    expect($inboxItem->fresh()->trashed())->toBeTrue();
});

test('approving inbox item with null approvable just archives it', function () {
    $inboxItem = InboxItem::factory()->create([
        'team_id' => $this->team->id,
        'type' => InboxItemType::Flag,
        'title' => 'General notification',
        'approvable_type' => null,
        'approvable_id' => null,
        'source_id' => "user-{$this->owner->id}",
        'source_name' => $this->owner->name,
        'source_type' => SourceType::Human,
        'urgency' => Urgency::Normal,
    ]);

    $this->actingAs($this->reviewer)
        ->post(route('inbox.approve', $inboxItem))
        ->assertRedirect();

    // Inbox item should be soft-deleted
    expect($inboxItem->fresh()->trashed())->toBeTrue();
});

test('bulk approve transitions multiple tasks', function () {
    $tasks = [];
    $inboxItems = [];

    for ($i = 0; $i < 3; $i++) {
        $task = Task::factory()->create([
            'team_id' => $this->team->id,
            'work_order_id' => $this->workOrder->id,
            'project_id' => $this->project->id,
            'created_by_id' => $this->submitter->id,
            'assigned_to_id' => $this->submitter->id,
            'status' => TaskStatus::InReview,
        ]);

        // Create transition record for each task (by submitter)
        StatusTransition::create([
            'transitionable_type' => Task::class,
            'transitionable_id' => $task->id,
            'user_id' => $this->submitter->id,
            'from_status' => 'in_progress',
            'to_status' => 'in_review',
            'created_at' => now(),
        ]);

        $tasks[] = $task;

        $inboxItems[] = InboxItem::factory()->create([
            'team_id' => $this->team->id,
            'type' => InboxItemType::Approval,
            'title' => "Task {$i} ready for review",
            'approvable_type' => Task::class,
            'approvable_id' => $task->id,
            'source_id' => "user-{$this->submitter->id}",
            'source_name' => $this->submitter->name,
            'source_type' => SourceType::Human,
            'urgency' => Urgency::Normal,
        ]);
    }

    $this->actingAs($this->reviewer)
        ->post(route('inbox.bulk'), [
            'itemIds' => array_map(fn ($item) => $item->id, $inboxItems),
            'action' => 'approve',
        ])
        ->assertRedirect();

    foreach ($tasks as $task) {
        $task->refresh();
        expect($task->status)->toBe(TaskStatus::Approved);
    }

    foreach ($inboxItems as $item) {
        expect($item->fresh()->trashed())->toBeTrue();
    }
});
