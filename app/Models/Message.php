<?php

namespace App\Models;

use App\Enums\AuthorType;
use App\Enums\MessageType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'communication_thread_id',
        'author_id',
        'author_type',
        'content',
        'type',
    ];

    protected $casts = [
        'author_type' => AuthorType::class,
        'type' => MessageType::class,
    ];

    public function communicationThread(): BelongsTo
    {
        return $this->belongsTo(CommunicationThread::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
