<?php

namespace App\Models;

use App\Enums\InboxItemType;
use App\Enums\Urgency;
use App\Enums\SourceType;
use App\Enums\AIConfidence;
use App\Enums\QAValidation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InboxItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'team_id',
        'type',
        'title',
        'content_preview',
        'full_content',
        'source_id',
        'source_name',
        'source_type',
        'related_work_order_id',
        'related_work_order_title',
        'related_project_id',
        'related_project_name',
        'urgency',
        'ai_confidence',
        'qa_validation',
    ];

    protected $casts = [
        'type' => InboxItemType::class,
        'urgency' => Urgency::class,
        'source_type' => SourceType::class,
        'ai_confidence' => AIConfidence::class,
        'qa_validation' => QAValidation::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function relatedWorkOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class, 'related_work_order_id');
    }

    public function relatedProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'related_project_id');
    }

    // Scopes
    public function scopeForTeam($query, int $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeUrgent($query)
    {
        return $query->whereIn('urgency', ['urgent', 'high']);
    }

    // Helper: Calculate waiting hours
    public function getWaitingHoursAttribute(): int
    {
        return $this->created_at->diffInHours(now());
    }
}
