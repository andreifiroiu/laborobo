<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentShareAccess extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_share_link_id',
        'accessed_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'document_share_link_id' => 'integer',
        'accessed_at' => 'datetime',
    ];

    /**
     * Get the share link this access record belongs to.
     */
    public function shareLink(): BelongsTo
    {
        return $this->belongsTo(DocumentShareLink::class, 'document_share_link_id');
    }

    /**
     * Scope to filter access records by share link.
     */
    public function scopeForShareLink(Builder $query, int $shareLinkId): Builder
    {
        return $query->where('document_share_link_id', $shareLinkId);
    }

    /**
     * Scope to order by most recent access first.
     */
    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderByDesc('accessed_at');
    }

    /**
     * Scope to filter access records within a date range.
     */
    public function scopeAccessedBetween(Builder $query, \DateTimeInterface $start, \DateTimeInterface $end): Builder
    {
        return $query->whereBetween('accessed_at', [$start, $end]);
    }
}
