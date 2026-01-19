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
    // Owner who creates the team (acts as reviewer)
    $this->owner = User::factory()->create();
    $this->team = $this->owner->createTeam(['name' => 'Test Team']);
    $this->owner->current_team_id = $this->team->id;
    $this->owner->save();

    // Submitter who creates work
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

test('rejection creates feedback inbox item for submitter', function () {
    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->submitter->id,
        'assigned_to_id' => $this->submitter->id,
        'status' => TaskStatus::InReview,
    ]);

    // Create transition record for the review submission
    StatusTransition::create([
        'transitionable_type' => Task::class,
        'transitionable_id' => $task->id,
        'user_id' => $this->submitter->id,
        'from_status' => 'in_progress',
        'to_status' => 'in_review',
        'created_at' => now(),
    ]);

    $originalInboxItem = InboxItem::factory()->create([
        'team_id' => $this->team->id,
        'type' => InboxItemType::Approval,
        'title' => 'Task ready for review',
        'approvable_type' => Task::class,
        'approvable_id' => $task->id,
        'source_id' => "user-{$this->submitter->id}",
        'source_name' => $this->submitter->name,
        'source_type' => SourceType::Human,
        'urgency' => Urgency::Normal,
        'related_work_order_id' => $this->workOrder->id,
        'related_work_order_title' => $this->workOrder->title,
        'related_project_id' => $this->project->id,
        'related_project_name' => $this->project->name,
        'related_task_id' => $task->id,
    ]);

    $feedback = 'Please fix the formatting issues and add more documentation.';

    $this->actingAs($this->reviewer)
        ->post(route('inbox.reject', $originalInboxItem), [
            'feedback' => $feedback,
        ])
        ->assertRedirect();

    // Find the feedback inbox item created for the submitter
    $feedbackItem = InboxItem::where('type', InboxItemType::Flag)
        ->where('reviewer_id', $this->submitter->id)
        ->where('team_id', $this->team->id)
        ->first();

    expect($feedbackItem)->not->toBeNull();
    expect($feedbackItem->title)->toContain('Revision requested');
    expect($feedbackItem->full_content)->toContain($feedback);
    expect($feedbackItem->full_content)->toContain($this->reviewer->name);
    expect($feedbackItem->source_type)->toBe(SourceType::Human);
    expect($feedbackItem->source_id)->toBe("user-{$this->reviewer->id}");

    // Verify related fields are copied
    expect($feedbackItem->related_work_order_id)->toBe($this->workOrder->id);
    expect($feedbackItem->related_project_id)->toBe($this->project->id);
    expect($feedbackItem->related_task_id)->toBe($task->id);
});

test('rejection from ai agent does not create feedback inbox item', function () {
    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->submitter->id,
        'assigned_to_id' => $this->submitter->id,
        'status' => TaskStatus::InReview,
    ]);

    // Create transition record for the review submission
    StatusTransition::create([
        'transitionable_type' => Task::class,
        'transitionable_id' => $task->id,
        'user_id' => $this->submitter->id,
        'from_status' => 'in_progress',
        'to_status' => 'in_review',
        'created_at' => now(),
    ]);

    // AI agent submitted the work
    $originalInboxItem = InboxItem::factory()->create([
        'team_id' => $this->team->id,
        'type' => InboxItemType::Approval,
        'title' => 'Task ready for review',
        'approvable_type' => Task::class,
        'approvable_id' => $task->id,
        'source_id' => 'agent-123',
        'source_name' => 'Test AI Agent',
        'source_type' => SourceType::AIAgent,
        'urgency' => Urgency::Normal,
    ]);

    $feedbackCountBefore = InboxItem::where('type', InboxItemType::Flag)->count();

    $this->actingAs($this->reviewer)
        ->post(route('inbox.reject', $originalInboxItem), [
            'feedback' => 'Please fix the issues.',
        ])
        ->assertRedirect();

    // No new feedback inbox item should be created for AI agent
    $feedbackCountAfter = InboxItem::where('type', InboxItemType::Flag)->count();
    expect($feedbackCountAfter)->toBe($feedbackCountBefore);
});

test('self-rejection does not create feedback item for same user', function () {
    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->owner->id,
        'assigned_to_id' => $this->owner->id,
        'status' => TaskStatus::InReview,
    ]);

    // Create transition record for the review submission
    StatusTransition::create([
        'transitionable_type' => Task::class,
        'transitionable_id' => $task->id,
        'user_id' => $this->owner->id,
        'from_status' => 'in_progress',
        'to_status' => 'in_review',
        'created_at' => now(),
    ]);

    // Owner submitted and will also reject (edge case via direct API call)
    $originalInboxItem = InboxItem::factory()->create([
        'team_id' => $this->team->id,
        'type' => InboxItemType::Approval,
        'title' => 'Task ready for review',
        'approvable_type' => Task::class,
        'approvable_id' => $task->id,
        'source_id' => "user-{$this->owner->id}",
        'source_name' => $this->owner->name,
        'source_type' => SourceType::Human,
        'urgency' => Urgency::Normal,
    ]);

    $feedbackCountBefore = InboxItem::where('type', InboxItemType::Flag)->count();

    $this->actingAs($this->owner)
        ->post(route('inbox.reject', $originalInboxItem), [
            'feedback' => 'I need to redo this myself.',
        ])
        ->assertRedirect();

    // No new feedback inbox item should be created when reviewer is the submitter
    $feedbackCountAfter = InboxItem::where('type', InboxItemType::Flag)->count();
    expect($feedbackCountAfter)->toBe($feedbackCountBefore);
});
