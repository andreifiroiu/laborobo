<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AuthorType;
use App\Enums\MessageType;
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
    ];

    protected $casts = [
        'author_type' => AuthorType::class,
        'type' => MessageType::class,
        'edited_at' => 'datetime',
    ];

    public function communicationThread(): BelongsTo
    {
        return $this->belongsTo(CommunicationThread::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
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
}
