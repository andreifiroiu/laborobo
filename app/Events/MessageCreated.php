<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\CommunicationThread;
use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Message $message,
        public readonly CommunicationThread $thread,
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        // Only broadcast if Reverb/broadcasting is configured
        if (! $this->shouldBroadcast()) {
            return [];
        }

        return [
            new Channel("thread.{$this->thread->id}"),
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'message' => [
                'id' => (string) $this->message->id,
                'authorId' => (string) $this->message->author_id,
                'authorName' => $this->message->author?->name ?? 'Unknown',
                'authorType' => $this->message->author_type->value,
                'content' => $this->message->content,
                'type' => $this->message->type->value,
                'timestamp' => $this->message->created_at->toIso8601String(),
            ],
            'threadId' => (string) $this->thread->id,
        ];
    }

    /**
     * Determine if this event should broadcast.
     */
    private function shouldBroadcast(): bool
    {
        // Check if broadcasting driver is configured (not set to 'null' or 'log')
        $driver = config('broadcasting.default');

        return $driver !== null && $driver !== 'null' && $driver !== 'log';
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'message.created';
    }
}
