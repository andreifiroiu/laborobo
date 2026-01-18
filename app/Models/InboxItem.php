<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AIConfidence;
use App\Enums\InboxItemType;
use App\Enums\QAValidation;
use App\Enums\SourceType;
use App\Enums\Urgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        'related_task_id',
        'related_project_id',
        'related_project_name',
        'approvable_type',
        'approvable_id',
        'urgency',
        'ai_confidence',
        'qa_validation',
        'reviewer_id',
        'approved_at',
        'rejected_at',
    ];

    protected $casts = [
        'type' => InboxItemType::class,
        'urgency' => Urgency::class,
        'source_type' => SourceType::class,
        'ai_confidence' => AIConfidence::class,
        'qa_validation' => QAValidation::class,
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function relatedWorkOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class, 'related_work_order_id');
    }

    public function relatedTask(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'related_task_id');
    }

    public function relatedProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'related_project_id');
    }

    /**
     * Get the approvable model (Task or WorkOrder) associated with this item.
     */
    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the reviewer assigned to this inbox item.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function scopeForTeam($query, int $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    public function scopeByType($query, InboxItemType $type)
    {
        return $query->where('type', $type);
    }

    public function scopeApprovals($query)
    {
        return $query->where('type', InboxItemType::Approval);
    }

    public function scopePending($query)
    {
        return $query->whereNull('approved_at')
            ->whereNull('rejected_at')
            ->whereNull('deleted_at');
    }

    public function scopeUrgent($query)
    {
        return $query->whereIn('urgency', [Urgency::Urgent, Urgency::High]);
    }

    /**
     * Find a pending approval inbox item for the given model.
     */
    public static function findPendingApprovalFor(Model $approvable): ?self
    {
        return static::query()
            ->where('approvable_type', get_class($approvable))
            ->where('approvable_id', $approvable->id)
            ->where('type', InboxItemType::Approval)
            ->pending()
            ->first();
    }

    /**
     * Mark this inbox item as approved.
     */
    public function markAsApproved(): void
    {
        $this->approved_at = now();
        $this->save();
        $this->delete();
    }

    /**
     * Mark this inbox item as rejected.
     */
    public function markAsRejected(): void
    {
        $this->rejected_at = now();
        $this->save();
        $this->delete();
    }

    public function getWaitingHoursAttribute(): int
    {
        return (int) $this->created_at->diffInHours(now());
    }
}
