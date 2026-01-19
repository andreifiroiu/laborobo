<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $messageId,
        public readonly int $threadId,
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

        return [
            new Channel("thread.{$this->threadId}"),
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
            'messageId' => (string) $this->messageId,
            'threadId' => (string) $this->threadId,
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
        return 'message.deleted';
    }
}
