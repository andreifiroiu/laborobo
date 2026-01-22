<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DocumentType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'team_id',
        'uploaded_by_id',
        'documentable_type',
        'documentable_id',
        'folder_id',
        'name',
        'type',
        'file_url',
        'file_size',
    ];

    protected $casts = [
        'team_id' => 'integer',
        'uploaded_by_id' => 'integer',
        'folder_id' => 'integer',
        'type' => DocumentType::class,
    ];

    /**
     * Get the team that owns this document.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the user who uploaded this document.
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
    }

    /**
     * Get the parent entity (polymorphic).
     */
    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the folder this document belongs to.
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    /**
     * Get the communication thread for this document (thread-level comments).
     */
    public function thread(): MorphOne
    {
        return $this->morphOne(CommunicationThread::class, 'threadable');
    }

    /**
     * Get the annotations for this document.
     */
    public function annotations(): HasMany
    {
        return $this->hasMany(DocumentAnnotation::class);
    }

    /**
     * Get the share links for this document.
     */
    public function shareLinks(): HasMany
    {
        return $this->hasMany(DocumentShareLink::class);
    }

    /**
     * Scope to filter documents by team.
     */
    public function scopeForTeam(Builder $query, int $teamId): Builder
    {
        return $query->where('team_id', $teamId);
    }

    /**
     * Scope to filter documents by folder.
     */
    public function scopeInFolder(Builder $query, ?int $folderId): Builder
    {
        if ($folderId === null) {
            return $query->whereNull('folder_id');
        }

        return $query->where('folder_id', $folderId);
    }

    /**
     * Scope to filter documents without a folder (root level).
     */
    public function scopeWithoutFolder(Builder $query): Builder
    {
        return $query->whereNull('folder_id');
    }

    /**
     * Get or create the communication thread for this document.
     */
    public function getOrCreateThread(): CommunicationThread
    {
        $thread = $this->thread;

        if ($thread === null) {
            $thread = $this->thread()->create([
                'team_id' => $this->team_id,
            ]);
        }

        return $thread;
    }
}
