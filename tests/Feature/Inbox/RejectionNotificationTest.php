<?php

declare(strict_types=1);

use App\Enums\InboxItemType;
use App\Enums\SourceType;
use App\Enums\TaskStatus;
use App\Enums\Urgency;
use App\Models\InboxItem;
use App\Models\NotificationPreference;
use App\Models\Party;
use App\Models\Project;
use App\Models\StatusTransition;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkOrder;
use App\Notifications\RejectionFeedbackNotification;
use Illuminate\Support\Facades\Notification;

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

test('rejecting inbox item sends notification to submitter', function () {
    Notification::fake();

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

    $feedback = 'Please fix the formatting issues.';

    $this->actingAs($this->reviewer)
        ->post(route('inbox.reject', $originalInboxItem), [
            'feedback' => $feedback,
        ])
        ->assertRedirect();

    // Assert notification was sent to submitter
    Notification::assertSentTo(
        $this->submitter,
        RejectionFeedbackNotification::class,
        function ($notification) use ($task, $feedback) {
            $data = $notification->toArray($this->submitter);

            return $data['item_id'] === $task->id
                && $data['feedback'] === $feedback
                && $data['reviewer_name'] === $this->reviewer->name;
        }
    );
});

test('notification respects email preferences - email sent when email_blockers enabled', function () {
    Notification::fake();

    // Enable email_blockers preference
    NotificationPreference::create([
        'team_id' => $this->team->id,
        'user_id' => $this->submitter->id,
        'email_blockers' => true,
    ]);

    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->submitter->id,
        'assigned_to_id' => $this->submitter->id,
        'status' => TaskStatus::InReview,
    ]);

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
    ]);

    $this->actingAs($this->reviewer)
        ->post(route('inbox.reject', $originalInboxItem), [
            'feedback' => 'Please revise.',
        ])
        ->assertRedirect();

    // Notification sent with mail channel
    Notification::assertSentTo(
        $this->submitter,
        RejectionFeedbackNotification::class,
        function ($notification) {
            $channels = $notification->via($this->submitter);

            return in_array('mail', $channels) && in_array('database', $channels);
        }
    );
});

test('notification respects email preferences - no email when email_blockers disabled', function () {
    Notification::fake();

    // Disable email_blockers preference
    NotificationPreference::create([
        'team_id' => $this->team->id,
        'user_id' => $this->submitter->id,
        'email_blockers' => false,
    ]);

    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->submitter->id,
        'assigned_to_id' => $this->submitter->id,
        'status' => TaskStatus::InReview,
    ]);

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
    ]);

    $this->actingAs($this->reviewer)
        ->post(route('inbox.reject', $originalInboxItem), [
            'feedback' => 'Please revise.',
        ])
        ->assertRedirect();

    // Notification sent only to database channel
    Notification::assertSentTo(
        $this->submitter,
        RejectionFeedbackNotification::class,
        function ($notification) {
            $channels = $notification->via($this->submitter);

            return ! in_array('mail', $channels) && in_array('database', $channels);
        }
    );
});

test('notification not sent for AI agent submissions', function () {
    Notification::fake();

    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->submitter->id,
        'assigned_to_id' => $this->submitter->id,
        'status' => TaskStatus::InReview,
    ]);

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

    $this->actingAs($this->reviewer)
        ->post(route('inbox.reject', $originalInboxItem), [
            'feedback' => 'Please fix the issues.',
        ])
        ->assertRedirect();

    // No notification should be sent for AI agent submissions
    Notification::assertNothingSent();
});

test('notification contains correct item details', function () {
    Notification::fake();

    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->submitter->id,
        'assigned_to_id' => $this->submitter->id,
        'title' => 'Important Task',
        'status' => TaskStatus::InReview,
    ]);

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
    ]);

    $feedback = 'Detailed feedback about what needs to be fixed.';

    $this->actingAs($this->reviewer)
        ->post(route('inbox.reject', $originalInboxItem), [
            'feedback' => $feedback,
        ])
        ->assertRedirect();

    Notification::assertSentTo(
        $this->submitter,
        RejectionFeedbackNotification::class,
        function ($notification) use ($task, $feedback) {
            $data = $notification->toArray($this->submitter);

            return $data['type'] === 'rejection_feedback'
                && $data['item_type'] === 'task'
                && $data['item_id'] === $task->id
                && $data['item_title'] === 'Important Task'
                && $data['reviewer_id'] === $this->reviewer->id
                && $data['reviewer_name'] === $this->reviewer->name
                && $data['feedback'] === $feedback
                && $data['work_order_id'] === $this->workOrder->id
                && $data['project_id'] === $this->project->id;
        }
    );
});
