<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AuthorType;
use App\Enums\DraftStatus;
use App\Enums\MessageType;
use App\Models\CommunicationThread;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    protected $model = Message::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'communication_thread_id' => CommunicationThread::factory(),
            'author_id' => User::factory(),
            'author_type' => AuthorType::Human,
            'content' => fake()->paragraphs(2, true),
            'type' => MessageType::Note,
        ];
    }

    /**
     * Create a message authored by an AI agent.
     */
    public function aiAgent(): static
    {
        return $this->state(fn () => [
            'author_type' => AuthorType::AiAgent,
        ]);
    }

    /**
     * Create a draft message.
     */
    public function draft(): static
    {
        return $this->state(fn () => [
            'draft_status' => DraftStatus::Draft,
        ]);
    }

    /**
     * Create an approved draft message.
     */
    public function approved(?User $approver = null): static
    {
        return $this->state(function () use ($approver) {
            return [
                'draft_status' => DraftStatus::Approved,
                'approved_at' => now(),
                'approved_by' => $approver?->id ?? User::factory()->create()->id,
            ];
        });
    }

    /**
     * Create a rejected draft message.
     */
    public function rejected(string $reason = 'Test rejection'): static
    {
        return $this->state(fn () => [
            'draft_status' => DraftStatus::Rejected,
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Create a sent message.
     */
    public function sent(): static
    {
        return $this->state(fn () => [
            'draft_status' => DraftStatus::Sent,
        ]);
    }

    /**
     * Set draft metadata.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function withDraftMetadata(array $metadata): static
    {
        return $this->state(fn () => [
            'draft_metadata' => $metadata,
        ]);
    }

    /**
     * Create a status update message.
     */
    public function statusUpdate(): static
    {
        return $this->state(fn () => [
            'type' => MessageType::StatusUpdate,
        ]);
    }

    /**
     * Set the message type.
     */
    public function ofType(MessageType $type): static
    {
        return $this->state(fn () => [
            'type' => $type,
        ]);
    }

    /**
     * Set the communication thread.
     */
    public function forThread(CommunicationThread $thread): static
    {
        return $this->state(fn () => [
            'communication_thread_id' => $thread->id,
        ]);
    }
}
