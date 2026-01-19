<?php

declare(strict_types=1);

use App\Models\CommunicationThread;
use App\Models\Message;
use App\Models\MessageReaction;
use App\Models\Party;
use App\Models\Project;
use App\Models\Task;
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
        'created_by_id' => $this->user->id,
    ]);
    $this->task = Task::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'project_id' => $this->project->id,
    ]);
});

test('GET /work/tasks/{id}/communications returns thread and messages', function () {
    $thread = CommunicationThread::create([
        'team_id' => $this->team->id,
        'threadable_type' => Task::class,
        'threadable_id' => $this->task->id,
    ]);

    $message = $thread->addMessage($this->user, 'Test message content', 'note', 'human');

    $response = $this->actingAs($this->user)
        ->getJson("/work/tasks/{$this->task->id}/communications");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'thread' => ['id', 'messageCount', 'lastActivity'],
            'messages' => [
                '*' => [
                    'id',
                    'authorId',
                    'authorName',
                    'authorType',
                    'timestamp',
                    'content',
                    'type',
                ],
            ],
        ])
        ->assertJsonPath('thread.id', (string) $thread->id)
        ->assertJsonPath('messages.0.content', 'Test message content');
});

test('POST /work/tasks/{id}/communications creates message with mentions parsed', function () {
    // Use the current user's name as a mention target
    $this->user->update(['name' => 'JohnDoe']);

    $response = $this->actingAs($this->user)
        ->post("/work/tasks/{$this->task->id}/communications", [
            'content' => 'Hello @JohnDoe please review this task @T-' . $this->task->id,
            'type' => 'message',
        ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('messages', [
        'content' => 'Hello @JohnDoe please review this task @T-' . $this->task->id,
        'type' => 'message',
    ]);

    $this->task->refresh();
    expect($this->task->communicationThread)->not->toBeNull();
    expect($this->task->communicationThread->messages)->toHaveCount(1);

    // Check that mentions were parsed and stored
    $message = $this->task->communicationThread->messages->first();
    expect($message->mentions)->toHaveCount(2); // User mention + Task mention
});

test('PATCH /work/communications/messages/{id} edits message within 10-minute window', function () {
    $thread = CommunicationThread::create([
        'team_id' => $this->team->id,
        'threadable_type' => Task::class,
        'threadable_id' => $this->task->id,
    ]);

    $message = $thread->addMessage($this->user, 'Original content', 'note', 'human');

    $response = $this->actingAs($this->user)
        ->patchJson("/work/communications/messages/{$message->id}", [
            'content' => 'Updated content',
        ]);

    $response->assertStatus(200);

    $message->refresh();
    expect($message->content)->toBe('Updated content');
    expect($message->edited_at)->not->toBeNull();
});

test('DELETE /work/communications/messages/{id} soft-deletes within 10-minute window', function () {
    $thread = CommunicationThread::create([
        'team_id' => $this->team->id,
        'threadable_type' => Task::class,
        'threadable_id' => $this->task->id,
    ]);

    $message = $thread->addMessage($this->user, 'Message to delete', 'note', 'human');
    $messageId = $message->id;

    $response = $this->actingAs($this->user)
        ->deleteJson("/work/communications/messages/{$message->id}");

    $response->assertStatus(200);

    $this->assertSoftDeleted('messages', ['id' => $messageId]);
});

test('POST /work/communications/messages/{id}/reactions adds reaction', function () {
    $thread = CommunicationThread::create([
        'team_id' => $this->team->id,
        'threadable_type' => Task::class,
        'threadable_id' => $this->task->id,
    ]);

    $message = $thread->addMessage($this->user, 'Test message', 'note', 'human');

    $response = $this->actingAs($this->user)
        ->postJson("/work/communications/messages/{$message->id}/reactions", [
            'emoji' => 'thumbs_up',
        ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['reactions']);

    $this->assertDatabaseHas('message_reactions', [
        'message_id' => $message->id,
        'user_id' => $this->user->id,
        'emoji' => 'thumbs_up',
    ]);
});

test('DELETE /work/communications/messages/{id}/reactions/{emoji} removes reaction', function () {
    $thread = CommunicationThread::create([
        'team_id' => $this->team->id,
        'threadable_type' => Task::class,
        'threadable_id' => $this->task->id,
    ]);

    $message = $thread->addMessage($this->user, 'Test message', 'note', 'human');

    MessageReaction::create([
        'message_id' => $message->id,
        'user_id' => $this->user->id,
        'emoji' => 'heart',
    ]);

    $response = $this->actingAs($this->user)
        ->deleteJson("/work/communications/messages/{$message->id}/reactions/heart");

    $response->assertStatus(200);

    $this->assertDatabaseMissing('message_reactions', [
        'message_id' => $message->id,
        'user_id' => $this->user->id,
        'emoji' => 'heart',
    ]);
});
