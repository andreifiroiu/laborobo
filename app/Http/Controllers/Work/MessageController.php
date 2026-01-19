<?php

declare(strict_types=1);

namespace App\Http\Controllers\Work;

use App\Events\MessageDeleted;
use App\Events\MessageUpdated;
use App\Events\ReactionAdded;
use App\Events\ReactionRemoved;
use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\MessageReaction;
use App\Services\MentionParserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MessageController extends Controller
{
    private const EDIT_WINDOW_MINUTES = 10;

    public function __construct(
        private readonly MentionParserService $mentionParser
    ) {}

    /**
     * Update a message (edit).
     */
    public function update(Request $request, Message $message): JsonResponse
    {
        $user = $request->user();

        // Validate user owns the message
        if ($message->author_id !== $user->id) {
            return response()->json([
                'error' => 'You can only edit your own messages.',
            ], 403);
        }

        // Validate within 10-minute window
        if ($message->created_at->diffInMinutes(now()) > self::EDIT_WINDOW_MINUTES) {
            return response()->json([
                'error' => 'Messages can only be edited within 10 minutes of creation.',
            ], 422);
        }

        $validated = $request->validate([
            'content' => 'required|string|max:10000',
        ]);

        // Update the message
        $message->update([
            'content' => $validated['content'],
            'edited_at' => now(),
        ]);

        // Re-parse and update mentions
        $message->mentions()->delete();
        $mentions = $this->mentionParser->parse($validated['content']);
        foreach ($mentions as $mention) {
            $message->mentions()->create([
                'mentionable_type' => $mention['class'],
                'mentionable_id' => $mention['id'],
            ]);
        }

        $message->load(['author', 'mentions.mentionable', 'attachments', 'reactions']);

        // Dispatch MessageUpdated event for WebSocket readiness
        MessageUpdated::dispatch($message);

        return response()->json([
            'message' => $this->formatMessage($message, $user->id),
        ]);
    }

    /**
     * Delete a message (soft delete).
     */
    public function destroy(Request $request, Message $message): JsonResponse
    {
        $user = $request->user();

        // Validate user owns the message
        if ($message->author_id !== $user->id) {
            return response()->json([
                'error' => 'You can only delete your own messages.',
            ], 403);
        }

        // Validate within 10-minute window
        if ($message->created_at->diffInMinutes(now()) > self::EDIT_WINDOW_MINUTES) {
            return response()->json([
                'error' => 'Messages can only be deleted within 10 minutes of creation.',
            ], 422);
        }

        $messageId = $message->id;
        $threadId = $message->communication_thread_id;

        $message->delete();

        // Dispatch MessageDeleted event for WebSocket readiness
        MessageDeleted::dispatch($messageId, $threadId);

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Add a reaction to a message.
     */
    public function addReaction(Request $request, Message $message): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'emoji' => 'required|string|max:50',
        ]);

        // Check if reaction already exists
        $existingReaction = MessageReaction::where('message_id', $message->id)
            ->where('user_id', $user->id)
            ->where('emoji', $validated['emoji'])
            ->first();

        if ($existingReaction !== null) {
            return response()->json([
                'error' => 'You have already reacted with this emoji.',
            ], 422);
        }

        $reaction = MessageReaction::create([
            'message_id' => $message->id,
            'user_id' => $user->id,
            'emoji' => $validated['emoji'],
        ]);

        // Dispatch ReactionAdded event for WebSocket readiness
        ReactionAdded::dispatch($reaction, $message);

        return response()->json([
            'reactions' => $this->getGroupedReactions($message, $user->id),
        ]);
    }

    /**
     * Remove a reaction from a message.
     */
    public function removeReaction(Request $request, Message $message, string $emoji): JsonResponse
    {
        $user = $request->user();

        $reaction = MessageReaction::where('message_id', $message->id)
            ->where('user_id', $user->id)
            ->where('emoji', $emoji)
            ->first();

        if ($reaction === null) {
            return response()->json([
                'error' => 'Reaction not found.',
            ], 404);
        }

        $threadId = $message->communication_thread_id;
        $userId = $user->id;

        $reaction->delete();

        // Dispatch ReactionRemoved event for WebSocket readiness
        ReactionRemoved::dispatch($emoji, $message->id, $threadId, $userId);

        return response()->json([
            'reactions' => $this->getGroupedReactions($message, $user->id),
        ]);
    }

    /**
     * Get reactions grouped by emoji with counts.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getGroupedReactions(Message $message, int $currentUserId): array
    {
        $message->load('reactions');

        return $message->reactions->groupBy('emoji')->map(function ($group, $emoji) use ($currentUserId) {
            return [
                'emoji' => $emoji,
                'count' => $group->count(),
                'hasReacted' => $group->contains('user_id', $currentUserId),
                'users' => $group->map(fn ($r) => [
                    'id' => (string) $r->user_id,
                ])->values()->all(),
            ];
        })->values()->all();
    }

    /**
     * Format a message for API response.
     *
     * @return array<string, mixed>
     */
    private function formatMessage(Message $msg, int $currentUserId): array
    {
        $canEditOrDelete = $msg->author_id === $currentUserId
            && $msg->created_at->diffInMinutes(now()) <= self::EDIT_WINDOW_MINUTES;

        $reactions = $msg->reactions->groupBy('emoji')->map(function ($group, $emoji) use ($currentUserId) {
            return [
                'emoji' => $emoji,
                'count' => $group->count(),
                'hasReacted' => $group->contains('user_id', $currentUserId),
                'users' => $group->map(fn ($r) => [
                    'id' => (string) $r->user_id,
                ])->values()->all(),
            ];
        })->values()->all();

        return [
            'id' => (string) $msg->id,
            'authorId' => (string) $msg->author_id,
            'authorName' => $msg->author?->name ?? 'Unknown',
            'authorType' => $msg->author_type->value,
            'timestamp' => $msg->created_at->toIso8601String(),
            'content' => $msg->content,
            'type' => $msg->type->value,
            'editedAt' => $msg->edited_at?->toIso8601String(),
            'canEdit' => $canEditOrDelete,
            'canDelete' => $canEditOrDelete,
            'mentions' => $msg->mentions->map(fn ($m) => [
                'id' => (string) $m->id,
                'type' => $m->mentionable_type,
                'entityId' => (string) $m->mentionable_id,
                'name' => $m->mentionable?->name ?? $m->mentionable?->title ?? 'Unknown',
            ])->all(),
            'attachments' => $msg->attachments->map(fn ($a) => [
                'id' => (string) $a->id,
                'name' => $a->name,
                'fileUrl' => Storage::disk('public')->url($a->file_url),
                'fileSize' => $a->file_size,
                'mimeType' => $a->mime_type,
            ])->all(),
            'reactions' => $reactions,
        ];
    }
}
