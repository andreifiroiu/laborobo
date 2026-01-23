<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\DraftStatus;
use App\Models\Message;
use App\Models\Party;
use App\Notifications\ClientCommunicationNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Queue job for delivering approved client communications.
 *
 * Sends the approved draft to the Party contact via email notification
 * and updates the message status to Sent upon successful delivery.
 */
class ClientCommunicationDeliveryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    public function __construct(
        public readonly Message $message,
        public readonly Party $party,
    ) {}

    /**
     * Execute the job.
     *
     * Sends the notification to the Party contact and updates the message status.
     */
    public function handle(): void
    {
        Log::info('Processing client communication delivery', [
            'message_id' => $this->message->id,
            'party_id' => $this->party->id,
        ]);

        // Verify the message is approved before sending
        if ($this->message->draft_status !== DraftStatus::Approved) {
            Log::warning('Attempted to deliver non-approved message', [
                'message_id' => $this->message->id,
                'draft_status' => $this->message->draft_status?->value,
            ]);

            return;
        }

        // Get the recipient email using the Party's routing method
        $recipientEmail = $this->party->routeNotificationForMail();

        if ($recipientEmail === null) {
            Log::warning('No email address for Party, skipping delivery', [
                'message_id' => $this->message->id,
                'party_id' => $this->party->id,
            ]);

            return;
        }

        // Send the notification using on-demand delivery
        // This allows sending to external contacts who are not application users
        $notification = new ClientCommunicationNotification($this->message, $this->party);
        Notification::route('mail', $recipientEmail)->notify($notification);

        Log::info('Client communication delivered', [
            'message_id' => $this->message->id,
            'recipient_email' => $recipientEmail,
        ]);

        // Mark the message as sent
        $this->message->markAsSent();
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ClientCommunicationDeliveryJob failed', [
            'message_id' => $this->message->id,
            'party_id' => $this->party->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
