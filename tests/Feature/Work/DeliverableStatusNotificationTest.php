<?php

declare(strict_types=1);

use App\Enums\DeliverableStatus;
use App\Models\Deliverable;
use App\Models\Party;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use App\Models\WorkOrder;
use App\Notifications\DeliverableStatusChangedNotification;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->team = $this->user->createTeam(['name' => 'Test Team']);
    $this->user->current_team_id = $this->team->id;
    $this->user->save();

    $this->workOrderOwner = User::factory()->create();
    $this->workOrderAssignee = User::factory()->create();

    $this->party = Party::factory()->create(['team_id' => $this->team->id]);
    $this->project = Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $this->party->id,
        'owner_id' => $this->user->id,
    ]);
    $this->workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->workOrderOwner->id,
        'assigned_to_id' => $this->workOrderAssignee->id,
    ]);
    $this->deliverable = Deliverable::factory()->draft()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'project_id' => $this->project->id,
    ]);
});

test('notification is dispatched when status changes', function () {
    Notification::fake();

    $this->actingAs($this->user)
        ->patch(route('deliverables.update', $this->deliverable), [
            'status' => DeliverableStatus::InReview->value,
        ]);

    Notification::assertSentTo(
        [$this->workOrderOwner, $this->workOrderAssignee],
        DeliverableStatusChangedNotification::class
    );
});

test('notification is NOT dispatched when other fields change', function () {
    Notification::fake();

    $this->actingAs($this->user)
        ->patch(route('deliverables.update', $this->deliverable), [
            'title' => 'Updated Title',
            'description' => 'Updated description',
        ]);

    Notification::assertNothingSent();
});

test('notification includes correct data', function () {
    Notification::fake();

    $oldStatus = $this->deliverable->status;

    $this->actingAs($this->user)
        ->patch(route('deliverables.update', $this->deliverable), [
            'status' => DeliverableStatus::Approved->value,
        ]);

    Notification::assertSentTo(
        $this->workOrderOwner,
        DeliverableStatusChangedNotification::class,
        function (DeliverableStatusChangedNotification $notification, array $channels) use ($oldStatus) {
            $data = $notification->toArray($this->workOrderOwner);

            expect($data['deliverable_id'])->toBe($this->deliverable->id);
            expect($data['deliverable_title'])->toBe($this->deliverable->title);
            expect($data['old_status'])->toBe($oldStatus->value);
            expect($data['new_status'])->toBe(DeliverableStatus::Approved->value);
            expect($data['changed_by_user_id'])->toBe($this->user->id);
            expect($data['changed_by_user_name'])->toBe($this->user->name);
            expect($data['work_order_id'])->toBe($this->workOrder->id);
            expect($data['work_order_title'])->toBe($this->workOrder->title);
            expect($data['link'])->toContain('/work/deliverables/' . $this->deliverable->id);

            return true;
        }
    );
});

test('notification excludes change initiator from recipients', function () {
    Notification::fake();

    // Create a separate user who is both the change initiator and work order owner
    $ownerUser = User::factory()->create();
    $ownerTeam = $ownerUser->createTeam(['name' => 'Owner Team']);
    $ownerUser->current_team_id = $ownerTeam->id;
    $ownerUser->save();

    $assignee = User::factory()->create();

    $party = Party::factory()->create(['team_id' => $ownerTeam->id]);
    $project = Project::factory()->create([
        'team_id' => $ownerTeam->id,
        'party_id' => $party->id,
        'owner_id' => $ownerUser->id,
    ]);
    $workOrder = WorkOrder::factory()->create([
        'team_id' => $ownerTeam->id,
        'project_id' => $project->id,
        'created_by_id' => $ownerUser->id,
        'assigned_to_id' => $assignee->id,
    ]);
    $deliverable = Deliverable::factory()->draft()->create([
        'team_id' => $ownerTeam->id,
        'work_order_id' => $workOrder->id,
        'project_id' => $project->id,
    ]);

    // Owner makes the change - they should NOT receive notification
    $this->actingAs($ownerUser)
        ->patch(route('deliverables.update', $deliverable), [
            'status' => DeliverableStatus::InReview->value,
        ]);

    // Owner should NOT receive (they made the change)
    Notification::assertNotSentTo($ownerUser, DeliverableStatusChangedNotification::class);

    // Assignee should receive
    Notification::assertSentTo($assignee, DeliverableStatusChangedNotification::class);
});
