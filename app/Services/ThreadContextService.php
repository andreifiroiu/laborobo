<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AuthorType;
use App\Models\AIAgent;
use App\Models\CommunicationThread;
use App\Models\Message;
use App\Models\User;
use App\Models\WorkOrder;

/**
 * Service for retrieving and formatting message thread context.
 *
 * Gets all messages from a CommunicationThread, includes message author
 * information (User or AIAgent via author_type), orders by created_at
 * for chronological context, and formats for agent system prompt injection.
 */
class ThreadContextService
{
    /**
     * Get the full thread context with all messages.
     *
     * Returns an array containing messages with author information,
     * ordered chronologically (oldest first).
     *
     * @return array{
     *     thread_id: int,
     *     threadable_type: string,
     *     threadable_id: int,
     *     message_count: int,
     *     messages: array<int, array{
     *         id: int,
     *         content: string,
     *         author_id: int,
     *         author_type: string,
     *         author_name: string,
     *         type: string,
     *         created_at: string
     *     }>
     * }
     */
    public function getThreadContext(CommunicationThread $thread): array
    {
        $messages = $thread->messages()
            ->orderBy('created_at', 'asc')
            ->get();

        $formattedMessages = $messages->map(function (Message $message) {
            return [
                'id' => $message->id,
                'content' => $message->content,
                'author_id' => $message->author_id,
                'author_type' => $message->author_type->value,
                'author_name' => $this->getAuthorName($message),
                'type' => $message->type->value,
                'created_at' => $message->created_at->toIso8601String(),
            ];
        })->toArray();

        return [
            'thread_id' => $thread->id,
            'threadable_type' => $thread->threadable_type,
            'threadable_id' => $thread->threadable_id,
            'message_count' => $thread->message_count,
            'messages' => $formattedMessages,
        ];
    }

    /**
     * Format the thread context for agent system prompt injection.
     *
     * Creates a human-readable string representation of the thread
     * suitable for including in an agent's system prompt.
     */
    public function formatForSystemPrompt(CommunicationThread $thread): string
    {
        $context = $this->getThreadContext($thread);
        $lines = [];

        // Add threadable context header
        $lines[] = $this->formatThreadableContext($thread);
        $lines[] = '';
        $lines[] = '## Message Thread';
        $lines[] = '';

        // Format each message
        foreach ($context['messages'] as $message) {
            $timestamp = date('Y-m-d H:i', strtotime($message['created_at']));
            $authorLabel = $message['author_type'] === AuthorType::AiAgent->value
                ? "[AI] {$message['author_name']}"
                : $message['author_name'];

            $lines[] = "**{$authorLabel}** ({$timestamp}):";
            $lines[] = $message['content'];
            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    /**
     * Get the author name for a message.
     *
     * Resolves the author based on author_type (User or AIAgent).
     */
    private function getAuthorName(Message $message): string
    {
        if ($message->author_type === AuthorType::AiAgent) {
            $agent = AIAgent::find($message->author_id);

            return $agent?->name ?? 'Unknown AI Agent';
        }

        $user = User::find($message->author_id);

        return $user?->name ?? 'Unknown User';
    }

    /**
     * Format the threadable (e.g., WorkOrder) context.
     *
     * Provides context about what the thread is attached to.
     */
    private function formatThreadableContext(CommunicationThread $thread): string
    {
        $lines = ['## Context'];

        if ($thread->threadable_type === WorkOrder::class) {
            $workOrder = WorkOrder::with(['project', 'assignedTo', 'responsible'])
                ->find($thread->threadable_id);

            if ($workOrder !== null) {
                $lines[] = "- **Work Order**: {$workOrder->title}";
                $lines[] = "- **Status**: {$workOrder->status->value}";

                if ($workOrder->project !== null) {
                    $lines[] = "- **Project**: {$workOrder->project->name}";
                }

                if ($workOrder->description !== null) {
                    $lines[] = "- **Description**: {$workOrder->description}";
                }

                if ($workOrder->responsible !== null) {
                    $lines[] = "- **Responsible**: {$workOrder->responsible->name}";
                }

                if ($workOrder->due_date !== null) {
                    $lines[] = "- **Due Date**: {$workOrder->due_date->format('Y-m-d')}";
                }

                if ($workOrder->estimated_hours !== null) {
                    $lines[] = "- **Estimated Hours**: {$workOrder->estimated_hours}";
                }

                if (! empty($workOrder->acceptance_criteria)) {
                    $lines[] = '- **Acceptance Criteria**:';
                    foreach ($workOrder->acceptance_criteria as $criterion) {
                        $lines[] = "  - {$criterion}";
                    }
                }
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Get only the most recent messages from a thread.
     *
     * Useful when thread context needs to be limited for token constraints.
     *
     * @return array<int, array{
     *     id: int,
     *     content: string,
     *     author_id: int,
     *     author_type: string,
     *     author_name: string,
     *     type: string,
     *     created_at: string
     * }>
     */
    public function getRecentMessages(CommunicationThread $thread, int $limit = 10): array
    {
        $messages = $thread->messages()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->reverse() // Reverse to get chronological order
            ->values();

        return $messages->map(function (Message $message) {
            return [
                'id' => $message->id,
                'content' => $message->content,
                'author_id' => $message->author_id,
                'author_type' => $message->author_type->value,
                'author_name' => $this->getAuthorName($message),
                'type' => $message->type->value,
                'created_at' => $message->created_at->toIso8601String(),
            ];
        })->toArray();
    }

    /**
     * Estimate token count for thread context.
     *
     * Uses a rough approximation of 4 characters per token.
     */
    public function estimateTokenCount(CommunicationThread $thread): int
    {
        $formattedContext = $this->formatForSystemPrompt($thread);

        return (int) ceil(strlen($formattedContext) / 4);
    }
}
