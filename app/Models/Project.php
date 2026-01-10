<?php

namespace App\Models;

use App\Enums\ProjectStatus;
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

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
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

    public function scopeForTeam($query, int $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', ProjectStatus::Active);
    }

    public function scopeArchived($query)
    {
        return $query->where('status', ProjectStatus::Archived);
    }

    public function scopeNotArchived($query)
    {
        return $query->where('status', '!=', ProjectStatus::Archived);
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
