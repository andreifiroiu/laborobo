<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CommunicationThread extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'threadable_type',
        'threadable_id',
        'message_count',
        'last_activity',
    ];

    protected $casts = [
        'last_activity' => 'datetime',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function threadable(): MorphTo
    {
        return $this->morphTo();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function scopeForTeam($query, int $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    public function addMessage(User $author, string $content, string $type = 'note', string $authorType = 'human'): Message
    {
        $message = $this->messages()->create([
            'author_id' => $author->id,
            'author_type' => $authorType,
            'content' => $content,
            'type' => $type,
        ]);

        $this->increment('message_count');
        $this->update(['last_activity' => now()]);

        return $message;
    }
}
