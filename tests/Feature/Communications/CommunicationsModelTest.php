<?php

declare(strict_types=1);

use App\Models\CommunicationThread;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\MessageMention;
use App\Models\MessageReaction;
use App\Models\Party;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Models\WorkOrder;

beforeEach(function () {
    $this->user = User::factory()->create();
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
    ]);
    $this->task = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'project_id' => $this->project->id,
    ]);
});

test('message mention polymorphic relationship resolves user mention', function () {
    $thread = CommunicationThread::create([
        'team_id' => $this->team->id,
        'threadable_type' => Project::class,
        'threadable_id' => $this->project->id,
    ]);

    $message = $thread->addMessage($this->user, 'Test message with @mention');

    $mention = MessageMention::create([
        'message_id' => $message->id,
        'mentionable_type' => User::class,
        'mentionable_id' => $this->user->id,
    ]);

    expect($mention->mentionable)->toBeInstanceOf(User::class);
    expect($mention->mentionable->id)->toBe($this->user->id);
    expect($mention->message)->toBeInstanceOf(Message::class);
    expect($mention->message->id)->toBe($message->id);
});

test('message mention polymorphic relationship resolves work item mention', function () {
    $thread = CommunicationThread::create([
        'team_id' => $this->team->id,
        'threadable_type' => Project::class,
        'threadable_id' => $this->project->id,
    ]);

    $message = $thread->addMessage($this->user, 'Test message with @WO-123');

    $mention = MessageMention::create([
        'message_id' => $message->id,
        'mentionable_type' => WorkOrder::class,
        'mentionable_id' => $this->workOrder->id,
    ]);

    expect($mention->mentionable)->toBeInstanceOf(WorkOrder::class);
    expect($mention->mentionable->id)->toBe($this->workOrder->id);
});

test('message attachment belongs to message', function () {
    $thread = CommunicationThread::create([
        'team_id' => $this->team->id,
        'threadable_type' => Project::class,
        'threadable_id' => $this->project->id,
    ]);

    $message = $thread->addMessage($this->user, 'Test message with attachment');

    $attachment = MessageAttachment::create([
        'message_id' => $message->id,
        'name' => 'document.pdf',
        'file_url' => 'attachments/document.pdf',
        'file_size' => 1024,
        'mime_type' => 'application/pdf',
    ]);

    expect($attachment->message)->toBeInstanceOf(Message::class);
    expect($attachment->message->id)->toBe($message->id);
    expect($message->attachments)->toHaveCount(1);
    expect($message->attachments->first()->name)->toBe('document.pdf');
});

test('message reaction unique constraint prevents duplicate user emoji on same message', function () {
    $thread = CommunicationThread::create([
        'team_id' => $this->team->id,
        'threadable_type' => Project::class,
        'threadable_id' => $this->project->id,
    ]);

    $message = $thread->addMessage($this->user, 'Test message for reactions');

    MessageReaction::create([
        'message_id' => $message->id,
        'user_id' => $this->user->id,
        'emoji' => 'thumbs_up',
    ]);

    expect(fn () => MessageReaction::create([
        'message_id' => $message->id,
        'user_id' => $this->user->id,
        'emoji' => 'thumbs_up',
    ]))->toThrow(Illuminate\Database\QueryException::class);

    // Same user can add different emoji
    $reaction2 = MessageReaction::create([
        'message_id' => $message->id,
        'user_id' => $this->user->id,
        'emoji' => 'heart',
    ]);

    expect($reaction2->id)->not->toBeNull();
    expect($message->reactions)->toHaveCount(2);
});

test('message soft delete functionality works correctly', function () {
    $thread = CommunicationThread::create([
        'team_id' => $this->team->id,
        'threadable_type' => Project::class,
        'threadable_id' => $this->project->id,
    ]);

    $message = $thread->addMessage($this->user, 'Test message to be deleted');
    $messageId = $message->id;

    $message->delete();

    $this->assertSoftDeleted('messages', ['id' => $messageId]);
    expect(Message::find($messageId))->toBeNull();
    expect(Message::withTrashed()->find($messageId))->not->toBeNull();
});

test('task has communication thread morphOne relationship', function () {
    $thread = CommunicationThread::create([
        'team_id' => $this->team->id,
        'threadable_type' => Task::class,
        'threadable_id' => $this->task->id,
    ]);

    $this->task->refresh();

    expect($this->task->communicationThread)->toBeInstanceOf(CommunicationThread::class);
    expect($this->task->communicationThread->id)->toBe($thread->id);
    expect($thread->threadable)->toBeInstanceOf(Task::class);
    expect($thread->threadable->id)->toBe($this->task->id);
});
