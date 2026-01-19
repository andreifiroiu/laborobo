<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MessageMention extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'message_id',
        'mentionable_type',
        'mentionable_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function mentionable(): MorphTo
    {
        return $this->morphTo();
    }
}
