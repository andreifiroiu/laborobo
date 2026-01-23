<?php

declare(strict_types=1);

use App\Enums\CommunicationType;
use App\Enums\DraftStatus;
use App\Enums\ProjectStatus;
use App\Jobs\ClientCommunicationDeliveryJob;
use App\Models\CommunicationThread;
use App\Models\Message;
use App\Models\Party;
use App\Models\Project;
use App\Models\User;
use App\Notifications\ClientCommunicationNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->team = $this->user->createTeam(['name' => 'Test Team']);
    $this->user->current_team_id = $this->team->id;
    $this->user->save();

    $this->party = Party::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'Acme Corp',
        'contact_name' => 'Jane Smith',
        'contact_email' => 'jane@acme.com',
        'preferred_language' => 'en',
    ]);

    $this->project = Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $this->party->id,
        'owner_id' => $this->user->id,
        'name' => 'Website Redesign',
        'status' => ProjectStatus::Active,
    ]);

    $this->thread = CommunicationThread::factory()->create([
        'team_id' => $this->team->id,
        'threadable_type' => Project::class,
        'threadable_id' => $this->project->id,
    ]);

    $this->draft = Message::factory()
        ->aiAgent()
        ->approved($this->user)
        ->forThread($this->thread)
        ->withDraftMetadata([
            'communication_type' => CommunicationType::StatusUpdate->value,
            'entity_type' => 'Project',
            'entity_id' => $this->project->id,
        ])
        ->create([
            'content' => 'Dear Jane, We are pleased to inform you that your website redesign project is progressing well.',
        ]);
});

test('ClientCommunicationNotification via() returns mail and database channels', function () {
    $notification = new ClientCommunicationNotification($this->draft, $this->party);

    // The notification should return both channels for a party with email
    $channels = $notification->via($this->party);

    expect($channels)->toContain('mail');
    expect($channels)->toContain('database');
});

test('ClientCommunicationNotification toMail() returns properly formatted MailMessage', function () {
    $notification = new ClientCommunicationNotification($this->draft, $this->party);

    $mailMessage = $notification->toMail($this->party);

    expect($mailMessage)->toBeInstanceOf(MailMessage::class);

    // Get the mail data to check formatting
    $mailData = $mailMessage->toArray();

    // Subject should be based on communication type
    expect($mailData['subject'])->toContain('Status Update');

    // Check that the mail includes greeting, content, and action
    expect($mailData['greeting'])->toContain('Jane Smith');
    expect($mailData['introLines'])->not->toBeEmpty();
});

test('on-demand notification sends to Party email', function () {
    Notification::fake();

    $recipientEmail = $this->party->routeNotificationForMail();

    // Verify the email is correctly retrieved
    expect($recipientEmail)->toBe('jane@acme.com');

    // Send using on-demand notification
    $notification = new ClientCommunicationNotification($this->draft, $this->party);

    Notification::route('mail', $recipientEmail)->notify($notification);

    Notification::assertSentOnDemand(
        ClientCommunicationNotification::class,
        function ($notification, $channels, $notifiable) use ($recipientEmail) {
            return $notifiable->routes['mail'] === $recipientEmail;
        }
    );
});

test('notification toArray() stores correct data for database channel', function () {
    $notification = new ClientCommunicationNotification($this->draft, $this->party);

    $data = $notification->toArray($this->party);

    expect($data)->toHaveKey('message_id', $this->draft->id);
    expect($data)->toHaveKey('party_id', $this->party->id);
    expect($data)->toHaveKey('communication_type', CommunicationType::StatusUpdate->value);
    expect($data)->toHaveKey('work_item_title', 'Website Redesign');
});

test('Party routeNotificationForMail returns null when no email is available', function () {
    $partyWithoutEmail = Party::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'No Email Client',
        'contact_name' => 'John Doe',
        'contact_email' => null,
        'email' => null,
    ]);

    $email = $partyWithoutEmail->routeNotificationForMail();

    expect($email)->toBeNull();
});

test('ClientCommunicationDeliveryJob sends notification and marks message as sent', function () {
    Notification::fake();

    // Create a fresh approved message
    $approvedMessage = Message::factory()
        ->aiAgent()
        ->approved($this->user)
        ->forThread($this->thread)
        ->withDraftMetadata([
            'communication_type' => CommunicationType::StatusUpdate->value,
            'entity_type' => 'Project',
            'entity_id' => $this->project->id,
        ])
        ->create([
            'content' => 'Test message content for delivery.',
        ]);

    // Run the job synchronously
    $job = new ClientCommunicationDeliveryJob($approvedMessage, $this->party);
    $job->handle();

    // Refresh the message from database
    $approvedMessage->refresh();

    // Verify message is marked as sent
    expect($approvedMessage->draft_status)->toBe(DraftStatus::Sent);

    // Verify notification was sent
    Notification::assertSentOnDemand(ClientCommunicationNotification::class);
});
