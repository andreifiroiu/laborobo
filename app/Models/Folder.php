<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Folder extends Model
{
    use HasFactory;

    /**
     * Maximum nesting depth allowed for folders.
     */
    public const MAX_DEPTH = 3;

    protected $fillable = [
        'team_id',
        'project_id',
        'parent_id',
        'name',
        'created_by_id',
    ];

    protected $casts = [
        'team_id' => 'integer',
        'project_id' => 'integer',
        'parent_id' => 'integer',
        'created_by_id' => 'integer',
    ];

    /**
     * Get the team that owns this folder.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the project this folder belongs to (if project-scoped).
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the parent folder (for nested folders).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Folder::class, 'parent_id');
    }

    /**
     * Get the child folders.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Folder::class, 'parent_id');
    }

    /**
     * Get all documents in this folder.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /**
     * Get the user who created this folder.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Scope to filter folders by team.
     */
    public function scopeForTeam(Builder $query, int $teamId): Builder
    {
        return $query->where('team_id', $teamId);
    }

    /**
     * Scope to filter root folders (no parent).
     */
    public function scopeRoot(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope to filter project-scoped folders.
     */
    public function scopeForProject(Builder $query, int $projectId): Builder
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * Scope to filter team-scoped folders (not tied to a project).
     */
    public function scopeTeamScoped(Builder $query): Builder
    {
        return $query->whereNull('project_id');
    }

    /**
     * Check if this folder is project-scoped.
     */
    public function isProjectScoped(): bool
    {
        return $this->project_id !== null;
    }

    /**
     * Check if this folder is team-scoped (not tied to a project).
     */
    public function isTeamScoped(): bool
    {
        return $this->project_id === null;
    }

    /**
     * Calculate the depth of this folder in the hierarchy.
     * Root folders are at depth 1.
     */
    public function depth(): int
    {
        $depth = 1;
        $folder = $this;

        while ($folder->parent_id !== null) {
            $folder = $folder->parent;
            $depth++;

            // Safety check to prevent infinite loops
            if ($depth > self::MAX_DEPTH + 1) {
                break;
            }
        }

        return $depth;
    }

    /**
     * Check if this folder can have children (not at max depth).
     */
    public function canHaveChildren(): bool
    {
        return $this->depth() < self::MAX_DEPTH;
    }

    /**
     * Get all ancestor folders (from parent to root).
     *
     * @return \Illuminate\Support\Collection<int, Folder>
     */
    public function ancestors(): \Illuminate\Support\Collection
    {
        $ancestors = collect();
        $folder = $this->parent;

        while ($folder !== null) {
            $ancestors->push($folder);
            $folder = $folder->parent;

            // Safety check
            if ($ancestors->count() > self::MAX_DEPTH) {
                break;
            }
        }

        return $ancestors;
    }

    /**
     * Get the breadcrumb path from root to this folder.
     *
     * @return \Illuminate\Support\Collection<int, Folder>
     */
    public function breadcrumbs(): \Illuminate\Support\Collection
    {
        return $this->ancestors()->reverse()->push($this);
    }
}
