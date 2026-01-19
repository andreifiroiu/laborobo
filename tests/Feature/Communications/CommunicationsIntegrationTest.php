<?php

declare(strict_types=1);

use App\Enums\MessageType;
use App\Models\CommunicationThread;
use App\Models\Message;
use App\Models\Party;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkOrder;
use App\Notifications\MentionNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');

    $this->user = User::factory()->create(['name' => 'TestAuthor']);
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

test('end-to-end message creation with mentions triggers notification to mentioned user', function () {
    Notification::fake();

    // Create a user to be mentioned
    $mentionedUser = User::factory()->create(['name' => 'MentionTarget']);

    // Create message with mention
    $response = $this->actingAs($this->user)
        ->post("/work/tasks/{$this->task->id}/communications", [
            'content' => 'Hey @MentionTarget can you review this task?',
            'type' => 'message',
        ]);

    $response->assertRedirect();

    // Verify message was created
    $this->assertDatabaseHas('messages', [
        'content' => 'Hey @MentionTarget can you review this task?',
    ]);

    // Verify mention was stored
    $message = Message::where('content', 'Hey @MentionTarget can you review this task?')->first();
    expect($message->mentions)->toHaveCount(1);
    expect($message->mentions->first()->mentionable_id)->toBe($mentionedUser->id);
    expect($message->mentions->first()->mentionable_type)->toBe(User::class);

    // Verify notification was sent
    Notification::assertSentTo($mentionedUser, MentionNotification::class);

    // Verify author did not receive notification
    Notification::assertNotSentTo($this->user, MentionNotification::class);
});

test('file attachment uploads successfully and can be retrieved', function () {
    $file = UploadedFile::fake()->create('document.pdf', 1024, 'application/pdf');

    $response = $this->actingAs($this->user)
        ->post("/work/tasks/{$this->task->id}/communications", [
            'content' => 'Please review the attached document',
            'type' => 'note',
            'attachments' => [$file],
        ]);

    $response->assertRedirect();

    // Verify message was created
    $message = Message::where('content', 'Please review the attached document')->first();
    expect($message)->not->toBeNull();

    // Verify attachment was created
    expect($message->attachments)->toHaveCount(1);
    $attachment = $message->attachments->first();
    expect($attachment->name)->toBe('document.pdf');
    expect($attachment->mime_type)->toBe('application/pdf');
    expect($attachment->file_size)->toBe(1024 * 1024); // 1024KB in bytes

    // Verify file was stored
    Storage::disk('public')->assertExists($attachment->file_url);

    // Verify attachment appears in API response
    $apiResponse = $this->actingAs($this->user)
        ->getJson("/work/tasks/{$this->task->id}/communications");

    $apiResponse->assertStatus(200);
    $messageData = collect($apiResponse->json('messages'))->firstWhere('id', (string) $message->id);
    expect($messageData['attachments'])->toHaveCount(1);
    expect($messageData['attachments'][0]['name'])->toBe('document.pdf');
});

test('consolidated view with multiple filter combinations returns correct results', function () {
    // Create threads for different work item types
    $projectThread = CommunicationThread::create([
        'team_id' => $this->team->id,
        'threadable_type' => Project::class,
        'threadable_id' => $this->project->id,
    ]);

    $taskThread = CommunicationThread::create([
        'team_id' => $this->team->id,
        'threadable_type' => Task::class,
        'threadable_id' => $this->task->id,
    ]);

    // Create messages and then update their created_at using raw DB update
    $msg1 = Message::create([
        'communication_thread_id' => $projectThread->id,
        'author_id' => $this->user->id,
        'author_type' => 'human',
        'content' => 'Project question about timeline',
        'type' => MessageType::Question,
    ]);
    DB::table('messages')->where('id', $msg1->id)
        ->update(['created_at' => now()->subDays(5)]);

    $msg2 = Message::create([
        'communication_thread_id' => $projectThread->id,
        'author_id' => $this->user->id,
        'author_type' => 'human',
        'content' => 'Project decision made',
        'type' => MessageType::Decision,
    ]);
    DB::table('messages')->where('id', $msg2->id)
        ->update(['created_at' => now()->subDays(2)]);

    $msg3 = Message::create([
        'communication_thread_id' => $taskThread->id,
        'author_id' => $this->user->id,
        'author_type' => 'human',
        'content' => 'Task question about implementation',
        'type' => MessageType::Question,
    ]);
    DB::table('messages')->where('id', $msg3->id)
        ->update(['created_at' => now()->subDays(1)]);

    // Test combined filter: project type + question message type
    $response = $this->actingAs($this->user)
        ->get(route('communications.index', [
            'type' => 'project',
            'message_type' => 'question',
        ]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('communications/index')
        ->has('messages.data', 1)
        ->where('messages.data.0.content', 'Project question about timeline')
    );

    // Test date range filter with type filter (from 3 days ago to now)
    $response = $this->actingAs($this->user)
        ->get(route('communications.index', [
            'type' => 'project',
            'from' => now()->subDays(3)->format('Y-m-d'),
            'to' => now()->format('Y-m-d'),
        ]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('communications/index')
        ->has('messages.data', 1)
        ->where('messages.data.0.content', 'Project decision made')
    );

    // Test search + type filter
    $response = $this->actingAs($this->user)
        ->get(route('communications.index', [
            'search' => 'implementation',
            'type' => 'task',
        ]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('communications/index')
        ->has('messages.data', 1)
        ->where('messages.data.0.content', 'Task question about implementation')
    );
});

test('message edit preserves and re-parses mentions', function () {
    $mentionedUser = User::factory()->create(['name' => 'OriginalMention']);
    $newMentionedUser = User::factory()->create(['name' => 'NewMention']);

    $thread = CommunicationThread::create([
        'team_id' => $this->team->id,
        'threadable_type' => Task::class,
        'threadable_id' => $this->task->id,
    ]);

    // Create message with initial mention
    $message = $thread->addMessage($this->user, 'Hey @OriginalMention check this', 'note', 'human');
    $message->mentions()->create([
        'mentionable_type' => User::class,
        'mentionable_id' => $mentionedUser->id,
    ]);

    expect($message->mentions)->toHaveCount(1);
    expect($message->mentions->first()->mentionable_id)->toBe($mentionedUser->id);

    // Edit message with new mention
    $response = $this->actingAs($this->user)
        ->patchJson("/work/communications/messages/{$message->id}", [
            'content' => 'Hey @NewMention please review instead',
        ]);

    $response->assertStatus(200);

    // Verify mentions were updated
    $message->refresh();
    expect($message->content)->toBe('Hey @NewMention please review instead');
    expect($message->edited_at)->not->toBeNull();
    expect($message->mentions)->toHaveCount(1);
    expect($message->mentions->first()->mentionable_id)->toBe($newMentionedUser->id);
});

test('edit rejected for message outside 10-minute window', function () {
    $thread = CommunicationThread::create([
        'team_id' => $this->team->id,
        'threadable_type' => Task::class,
        'threadable_id' => $this->task->id,
    ]);

    // Create message normally first
    $message = Message::create([
        'communication_thread_id' => $thread->id,
        'author_id' => $this->user->id,
        'author_type' => 'human',
        'content' => 'Old message',
        'type' => MessageType::Note,
    ]);

    // Update created_at using raw DB to simulate old message
    DB::table('messages')->where('id', $message->id)
        ->update(['created_at' => now()->subMinutes(15)]);

    $response = $this->actingAs($this->user)
        ->patchJson("/work/communications/messages/{$message->id}", [
            'content' => 'Edited content',
        ]);

    $response->assertStatus(422);
    $response->assertJson([
        'error' => 'Messages can only be edited within 10 minutes of creation.',
    ]);

    // Verify message was not modified
    $message->refresh();
    expect($message->content)->toBe('Old message');
    expect($message->edited_at)->toBeNull();
});

test('delete rejected for message outside 10-minute window', function () {
    $thread = CommunicationThread::create([
        'team_id' => $this->team->id,
        'threadable_type' => Task::class,
        'threadable_id' => $this->task->id,
    ]);

    // Create message normally first
    $message = Message::create([
        'communication_thread_id' => $thread->id,
        'author_id' => $this->user->id,
        'author_type' => 'human',
        'content' => 'Old message that cannot be deleted',
        'type' => MessageType::Note,
    ]);

    // Update created_at using raw DB to simulate old message
    DB::table('messages')->where('id', $message->id)
        ->update(['created_at' => now()->subMinutes(15)]);

    $response = $this->actingAs($this->user)
        ->deleteJson("/work/communications/messages/{$message->id}");

    $response->assertStatus(422);
    $response->assertJson([
        'error' => 'Messages can only be deleted within 10 minutes of creation.',
    ]);

    // Verify message was not deleted
    $this->assertDatabaseHas('messages', ['id' => $message->id]);
    expect(Message::find($message->id))->not->toBeNull();
});

test('work order communications follow same pattern as task communications', function () {
    // Create message for work order
    $response = $this->actingAs($this->user)
        ->post("/work/work-orders/{$this->workOrder->id}/communications", [
            'content' => 'Work order update message',
            'type' => 'status_update',
        ]);

    $response->assertRedirect();

    // Verify thread was created for work order
    $this->workOrder->refresh();
    expect($this->workOrder->communicationThread)->not->toBeNull();

    // Verify message was created
    $this->assertDatabaseHas('messages', [
        'content' => 'Work order update message',
        'type' => 'status_update',
    ]);

    // Verify API retrieves work order communications
    $apiResponse = $this->actingAs($this->user)
        ->getJson("/work/work-orders/{$this->workOrder->id}/communications");

    $apiResponse->assertStatus(200);
    $apiResponse->assertJsonPath('messages.0.content', 'Work order update message');
    $apiResponse->assertJsonPath('messages.0.type', 'status_update');
});

test('blocked file extensions are rejected during upload', function () {
    $blockedFile = UploadedFile::fake()->create('malicious.exe', 100);

    $response = $this->actingAs($this->user)
        ->post("/work/tasks/{$this->task->id}/communications", [
            'content' => 'Check this file',
            'type' => 'note',
            'attachments' => [$blockedFile],
        ]);

    $response->assertSessionHasErrors('attachments');

    // Verify no message was created with this content
    $this->assertDatabaseMissing('messages', [
        'content' => 'Check this file',
    ]);
});
