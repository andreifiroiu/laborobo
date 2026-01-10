<?php

namespace App\Models;

use App\Enums\Priority;
use App\Enums\WorkOrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'team_id',
        'project_id',
        'assigned_to_id',
        'created_by_id',
        'party_contact_id',
        'title',
        'description',
        'status',
        'priority',
        'due_date',
        'estimated_hours',
        'actual_hours',
        'acceptance_criteria',
        'sop_attached',
        'sop_name',
    ];

    protected $casts = [
        'status' => WorkOrderStatus::class,
        'priority' => Priority::class,
        'due_date' => 'date',
        'estimated_hours' => 'decimal:2',
        'actual_hours' => 'decimal:2',
        'acceptance_criteria' => 'array',
        'sop_attached' => 'boolean',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function partyContact(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'party_contact_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function deliverables(): HasMany
    {
        return $this->hasMany(Deliverable::class);
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

    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('assigned_to_id', $userId);
    }

    public function scopeWithStatus($query, WorkOrderStatus $status)
    {
        return $query->where('status', $status);
    }

    public function getTasksCountAttribute(): int
    {
        return $this->tasks()->count();
    }

    public function getCompletedTasksCountAttribute(): int
    {
        return $this->tasks()->where('status', 'done')->count();
    }

    public function recalculateActualHours(): void
    {
        $this->actual_hours = $this->tasks()->sum('actual_hours');
        $this->save();

        // Also update parent project
        $this->project->recalculateActualHours();
    }
}
