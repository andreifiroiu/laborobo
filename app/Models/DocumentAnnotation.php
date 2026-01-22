<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentAnnotation extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'page',
        'x_percent',
        'y_percent',
        'communication_thread_id',
        'created_by_id',
    ];

    protected $casts = [
        'document_id' => 'integer',
        'page' => 'integer',
        'x_percent' => 'decimal:2',
        'y_percent' => 'decimal:2',
        'communication_thread_id' => 'integer',
        'created_by_id' => 'integer',
    ];

    /**
     * Get the document this annotation belongs to.
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Get the communication thread for this annotation.
     */
    public function thread(): BelongsTo
    {
        return $this->belongsTo(CommunicationThread::class, 'communication_thread_id');
    }

    /**
     * Get the user who created this annotation.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Scope to filter annotations by document.
     */
    public function scopeForDocument(Builder $query, int $documentId): Builder
    {
        return $query->where('document_id', $documentId);
    }

    /**
     * Scope to filter annotations by page.
     */
    public function scopeForPage(Builder $query, ?int $page): Builder
    {
        if ($page === null) {
            return $query->whereNull('page');
        }

        return $query->where('page', $page);
    }

    /**
     * Check if this annotation is for an image (no page number).
     */
    public function isForImage(): bool
    {
        return $this->page === null;
    }

    /**
     * Check if this annotation is for a PDF (has page number).
     */
    public function isForPdf(): bool
    {
        return $this->page !== null;
    }
}
