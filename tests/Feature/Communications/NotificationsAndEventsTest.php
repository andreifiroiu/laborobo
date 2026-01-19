<?php

declare(strict_types=1);

use App\Events\MessageCreated;
use App\Events\ReactionAdded;
use App\Models\CommunicationThread;
use App\Models\Message;
use App\Models\MessageReaction;
use App\Models\Party;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkOrder;
use App\Notifications\MentionNotification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->user = User::factory()->create(['name' => 'TestUser']);
    $this->team = $this->user->createTeam(['name' => 'Test Team']);
    $this->user->current_team_id = $this->team->id;
    $this->user->save();

    $this->party = Party::factory()->create(['team_id' => $this->team->id]);
    $this->project = Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $this->party->id,
        'owner_id' => $this->user->id,
    ]);
    $this->workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
    ]);
    $this->task = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'project_id' => $this->project->id,
    ]);
});

test('MentionNotification dispatched when user is mentioned', function () {
    Notification::fake();

    // Create a user that can be mentioned (name must exist for mention parsing)
    $mentionedUser = User::factory()->create(['name' => 'MentionedUser']);

    $this->actingAs($this->user)
        ->post("/work/tasks/{$this->task->id}/communications", [
            'content' => 'Hello @MentionedUser please review this',
            'type' => 'message',
        ]);

    Notification::assertSentTo(
        $mentionedUser,
        MentionNotification::class
    );
});

test('MentionNotification includes correct work item link', function () {
    Notification::fake();

    $mentionedUser = User::factory()->create(['name' => 'LinkTestUser']);

    $this->actingAs($this->user)
        ->post("/work/tasks/{$this->task->id}/communications", [
            'content' => 'Hello @LinkTestUser please check this task',
            'type' => 'message',
        ]);

    Notification::assertSentTo(
        $mentionedUser,
        MentionNotification::class,
        function (MentionNotification $notification) {
            $data = $notification->toArray($this->user);
            expect($data['link'])->toContain('tasks');
            expect($data['link'])->toContain((string) $this->task->id);
            return true;
        }
    );
});

test('MessageCreated event is dispatched on message creation', function () {
    Event::fake([MessageCreated::class]);

    $this->actingAs($this->user)
        ->post("/work/tasks/{$this->task->id}/communications", [
            'content' => 'Test message for event',
            'type' => 'note',
        ]);

    Event::assertDispatched(MessageCreated::class, function (MessageCreated $event) {
        expect($event->message)->toBeInstanceOf(Message::class);
        expect($event->message->content)->toBe('Test message for event');
        expect($event->thread)->toBeInstanceOf(CommunicationThread::class);
        return true;
    });
});

test('ReactionAdded event is dispatched when reaction added', function () {
    Event::fake([ReactionAdded::class]);

    $thread = CommunicationThread::create([
        'team_id' => $this->team->id,
        'threadable_type' => Task::class,
        'threadable_id' => $this->task->id,
    ]);

    $message = $thread->addMessage($this->user, 'Test message', 'note', 'human');

    $this->actingAs($this->user)
        ->postJson("/work/communications/messages/{$message->id}/reactions", [
            'emoji' => 'thumbs_up',
        ]);

    Event::assertDispatched(ReactionAdded::class, function (ReactionAdded $event) use ($message) {
        expect($event->reaction)->toBeInstanceOf(MessageReaction::class);
        expect($event->reaction->emoji)->toBe('thumbs_up');
        expect($event->message->id)->toBe($message->id);
        return true;
    });
});
