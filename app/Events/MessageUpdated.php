<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Message $message,
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        if (! $this->shouldBroadcast()) {
            return [];
        }

        $threadId = $this->message->communication_thread_id;

        return [
            new Channel("thread.{$threadId}"),
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
                'content' => $this->message->content,
                'editedAt' => $this->message->edited_at?->toIso8601String(),
            ],
            'threadId' => (string) $this->message->communication_thread_id,
        ];
    }

    /**
     * Determine if this event should broadcast.
     */
    private function shouldBroadcast(): bool
    {
        $driver = config('broadcasting.default');

        return $driver !== null && $driver !== 'null' && $driver !== 'log';
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'message.updated';
    }
}
