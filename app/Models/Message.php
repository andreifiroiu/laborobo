<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AuthorType;
use App\Enums\DraftStatus;
use App\Enums\MessageType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'communication_thread_id',
        'author_id',
        'author_type',
        'content',
        'type',
        'edited_at',
        'draft_status',
        'draft_metadata',
        'approved_at',
        'approved_by',
        'rejected_at',
        'rejection_reason',
    ];

    protected $casts = [
        'author_type' => AuthorType::class,
        'type' => MessageType::class,
        'edited_at' => 'datetime',
        'draft_status' => DraftStatus::class,
        'draft_metadata' => 'array',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function communicationThread(): BelongsTo
    {
        return $this->belongsTo(CommunicationThread::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function mentions(): HasMany
    {
        return $this->hasMany(MessageMention::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(MessageAttachment::class);
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(MessageReaction::class);
    }

    /**
     * Check if this message is a draft awaiting approval.
     */
    public function isDraft(): bool
    {
        return $this->draft_status === DraftStatus::Draft;
    }

    /**
     * Check if this message has been approved.
     */
    public function isApproved(): bool
    {
        return $this->draft_status === DraftStatus::Approved;
    }

    /**
     * Check if this message has been rejected.
     */
    public function isRejected(): bool
    {
        return $this->draft_status === DraftStatus::Rejected;
    }

    /**
     * Check if this message has been sent.
     */
    public function isSent(): bool
    {
        return $this->draft_status === DraftStatus::Sent;
    }

    /**
     * Scope to get all draft messages.
     */
    public function scopeDrafts(Builder $query): Builder
    {
        return $query->where('draft_status', DraftStatus::Draft);
    }

    /**
     * Scope to get messages pending approval (drafts).
     */
    public function scopePendingApproval(Builder $query): Builder
    {
        return $query->where('draft_status', DraftStatus::Draft)
            ->whereNull('approved_at')
            ->whereNull('rejected_at');
    }

    /**
     * Mark this message as approved by a user.
     */
    public function markAsApproved(User $approver): void
    {
        $this->update([
            'draft_status' => DraftStatus::Approved,
            'approved_at' => now(),
            'approved_by' => $approver->id,
        ]);
    }

    /**
     * Mark this message as rejected with a reason.
     */
    public function markAsRejected(string $reason): void
    {
        $this->update([
            'draft_status' => DraftStatus::Rejected,
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Mark this message as sent after delivery.
     */
    public function markAsSent(): void
    {
        $this->update([
            'draft_status' => DraftStatus::Sent,
        ]);
    }
}
