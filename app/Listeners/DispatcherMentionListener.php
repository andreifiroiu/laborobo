<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\AgentType;
use App\Events\MessageCreated;
use App\Jobs\ProcessDispatcherMention;
use App\Models\AIAgent;
use Illuminate\Support\Facades\Log;

/**
 * Listens for MessageCreated events and triggers the Dispatcher Agent
 * when explicitly tagged via @dispatcher mention.
 *
 * Only responds when a MessageMention exists with mentionable_type pointing
 * to an AIAgent of type WorkRouting (dispatcher).
 */
class DispatcherMentionListener
{
    /**
     * Handle the MessageCreated event.
     *
     * Checks if the message contains a mention to the dispatcher agent
     * and dispatches the ProcessDispatcherMention job if found.
     */
    public function handle(MessageCreated $event): void
    {
        $message = $event->message;
        $thread = $event->thread;

        // Check if message has any mentions pointing to an AIAgent
        $dispatcherMention = $message->mentions()
            ->where('mentionable_type', AIAgent::class)
            ->get()
            ->first(function ($mention) {
                $agent = $mention->mentionable;

                return $agent instanceof AIAgent
                    && $agent->type === AgentType::WorkRouting;
            });

        if ($dispatcherMention === null) {
            return;
        }

        $dispatcherAgent = $dispatcherMention->mentionable;

        Log::info('Dispatcher mention detected', [
            'message_id' => $message->id,
            'thread_id' => $thread->id,
            'agent_id' => $dispatcherAgent->id,
        ]);

        // Dispatch the job for async processing
        ProcessDispatcherMention::dispatch($message, $thread, $dispatcherAgent);
    }
}
