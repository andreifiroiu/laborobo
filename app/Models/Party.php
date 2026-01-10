<?php

namespace App\Models;

use App\Enums\PartyType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Party extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'team_id',
        'name',
        'type',
        'contact_name',
        'contact_email',
        'email',
        'phone',
        'website',
        'address',
        'notes',
        'tags',
        'status',
        'primary_contact_id',
        'last_activity',
    ];

    protected $casts = [
        'type' => PartyType::class,
        'tags' => 'array',
        'last_activity' => 'datetime',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function primaryContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'primary_contact_id');
    }

    public function scopeForTeam($query, int $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    public function scopeOfType($query, PartyType $type)
    {
        return $query->where('type', $type->value);
    }
}
