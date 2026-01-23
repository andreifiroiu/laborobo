<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectUserRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'user_id',
        'internal_rate',
        'billing_rate',
        'effective_date',
    ];

    protected $casts = [
        'internal_rate' => 'decimal:2',
        'billing_rate' => 'decimal:2',
        'effective_date' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForProject(Builder $query, int $projectId): Builder
    {
        return $query->where('project_id', $projectId);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Get the most recent rate that was effective at or before the given date.
     */
    public function scopeEffectiveAt(Builder $query, mixed $date): Builder
    {
        return $query->where('effective_date', '<=', $date)
            ->orderByDesc('effective_date');
    }
}
