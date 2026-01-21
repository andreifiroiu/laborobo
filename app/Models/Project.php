<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProjectStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'team_id',
        'party_id',
        'owner_id',
        'accountable_id',
        'responsible_id',
        'consulted_ids',
        'informed_ids',
        'name',
        'description',
        'status',
        'start_date',
        'target_end_date',
        'budget_hours',
        'actual_hours',
        'progress',
        'tags',
    ];

    protected $casts = [
        'status' => ProjectStatus::class,
        'start_date' => 'date',
        'target_end_date' => 'date',
        'budget_hours' => 'decimal:2',
        'actual_hours' => 'decimal:2',
        'tags' => 'array',
        'consulted_ids' => 'array',
        'informed_ids' => 'array',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Get the user who is accountable for this project.
     */
    public function accountable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accountable_id');
    }

    /**
     * Get the user who is responsible for this project.
     */
    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }

    public function workOrderLists(): HasMany
    {
        return $this->hasMany(WorkOrderList::class)->orderBy('position');
    }

    public function ungroupedWorkOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class)
            ->whereNull('work_order_list_id')
            ->orderBy('position_in_list');
    }

    public function tasks(): HasManyThrough
    {
        return $this->hasManyThrough(Task::class, WorkOrder::class);
    }

    public function deliverables(): HasManyThrough
    {
        return $this->hasManyThrough(Deliverable::class, WorkOrder::class);
    }

    public function communicationThread(): MorphOne
    {
        return $this->morphOne(CommunicationThread::class, 'threadable');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function scopeForTeam(Builder $query, int $teamId): Builder
    {
        return $query->where('team_id', $teamId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ProjectStatus::Active);
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('status', ProjectStatus::Archived);
    }

    public function scopeNotArchived(Builder $query): Builder
    {
        return $query->where('status', '!=', ProjectStatus::Archived);
    }

    /**
     * Scope to filter projects where the user has any RACI role.
     */
    public function scopeWhereUserHasRaciRole(Builder $query, int $userId, bool $excludeInformed = true): Builder
    {
        return $query->where(function (Builder $q) use ($userId, $excludeInformed) {
            $q->where('accountable_id', $userId)
                ->orWhere('responsible_id', $userId)
                ->orWhereJsonContains('consulted_ids', $userId);

            if (! $excludeInformed) {
                $q->orWhereJsonContains('informed_ids', $userId);
            }
        });
    }

    /**
     * Scope to filter projects where the user is accountable.
     */
    public function scopeWhereUserIsAccountable(Builder $query, int $userId): Builder
    {
        return $query->where('accountable_id', $userId);
    }

    /**
     * Scope to filter projects where the user is responsible.
     */
    public function scopeWhereUserIsResponsible(Builder $query, int $userId): Builder
    {
        return $query->where('responsible_id', $userId);
    }

    /**
     * Get the RACI roles the given user has for this project.
     *
     * @return array<string>
     */
    public function getUserRaciRoles(int $userId): array
    {
        $roles = [];

        if ($this->accountable_id === $userId) {
            $roles[] = 'accountable';
        }

        if ($this->responsible_id === $userId) {
            $roles[] = 'responsible';
        }

        if (is_array($this->consulted_ids) && in_array($userId, $this->consulted_ids, true)) {
            $roles[] = 'consulted';
        }

        if (is_array($this->informed_ids) && in_array($userId, $this->informed_ids, true)) {
            $roles[] = 'informed';
        }

        return $roles;
    }

    public function recalculateProgress(): void
    {
        $workOrders = $this->workOrders;

        if ($workOrders->isEmpty()) {
            $this->progress = 0;
            $this->save();
            return;
        }

        $totalTasks = 0;
        $completedTasks = 0;

        foreach ($workOrders as $workOrder) {
            $tasks = $workOrder->tasks;
            $totalTasks += $tasks->count();
            $completedTasks += $tasks->where('status', 'done')->count();
        }

        $this->progress = $totalTasks > 0 ? (int) round(($completedTasks / $totalTasks) * 100) : 0;
        $this->save();
    }

    public function recalculateActualHours(): void
    {
        $this->actual_hours = $this->tasks()->sum('tasks.actual_hours');
        $this->save();
    }
}
