<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Message;
use App\Models\MessageReaction;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReactionAdded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly MessageReaction $reaction,
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
            'reaction' => [
                'id' => (string) $this->reaction->id,
                'emoji' => $this->reaction->emoji,
                'userId' => (string) $this->reaction->user_id,
            ],
            'messageId' => (string) $this->message->id,
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
        return 'reaction.added';
    }
}
