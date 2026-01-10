<?php

namespace App\Models;

use App\Enums\ContactEngagementType;
use App\Enums\CommunicationPreference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'party_id',
        'name',
        'email',
        'phone',
        'title',
        'role',
        'engagement_type',
        'communication_preference',
        'timezone',
        'notes',
        'status',
        'tags',
    ];

    protected $casts = [
        'engagement_type' => ContactEngagementType::class,
        'communication_preference' => CommunicationPreference::class,
        'tags' => 'array',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function scopeForTeam($query, int $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    public function scopeForParty($query, int $partyId)
    {
        return $query->where('party_id', $partyId);
    }

    public function scopeOfStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
