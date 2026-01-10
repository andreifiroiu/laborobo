<?php

namespace App\Models;

use App\Enums\DeliverableStatus;
use App\Enums\DeliverableType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Deliverable extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'team_id',
        'work_order_id',
        'project_id',
        'title',
        'description',
        'type',
        'status',
        'version',
        'created_date',
        'delivered_date',
        'file_url',
        'acceptance_criteria',
    ];

    protected $casts = [
        'type' => DeliverableType::class,
        'status' => DeliverableStatus::class,
        'created_date' => 'date',
        'delivered_date' => 'date',
        'acceptance_criteria' => 'array',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function scopeForTeam($query, int $teamId)
    {
        return $query->where('team_id', $teamId);
    }
}
