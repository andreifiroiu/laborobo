<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeliverableVersion extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'deliverable_id',
        'version_number',
        'file_url',
        'file_name',
        'file_size',
        'mime_type',
        'notes',
        'uploaded_by_id',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'version_number' => 'integer',
    ];

    /**
     * Get the deliverable that owns this version.
     */
    public function deliverable(): BelongsTo
    {
        return $this->belongsTo(Deliverable::class);
    }

    /**
     * Get the user who uploaded this version.
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
    }

    /**
     * Scope to filter versions by deliverable.
     */
    public function scopeForDeliverable(Builder $query, int $deliverableId): Builder
    {
        return $query->where('deliverable_id', $deliverableId);
    }

    /**
     * Scope to order versions by version number descending (latest first).
     */
    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('version_number');
    }
}
