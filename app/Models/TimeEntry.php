<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TimeTrackingMode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TimeEntry extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'team_id',
        'user_id',
        'task_id',
        'hours',
        'date',
        'mode',
        'note',
        'is_billable',
        'started_at',
        'stopped_at',
    ];

    protected $casts = [
        'date' => 'date',
        'hours' => 'decimal:2',
        'mode' => TimeTrackingMode::class,
        'is_billable' => 'boolean',
        'started_at' => 'datetime',
        'stopped_at' => 'datetime',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function scopeForTeam(Builder $query, int $teamId): Builder
    {
        return $query->where('team_id', $teamId);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForDate(Builder $query, $date): Builder
    {
        return $query->whereDate('date', $date);
    }

    public function scopeRunningForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId)
            ->whereNotNull('started_at')
            ->whereNull('stopped_at');
    }

    public function scopeBillable(Builder $query): Builder
    {
        return $query->where('is_billable', true);
    }

    public function scopeNonBillable(Builder $query): Builder
    {
        return $query->where('is_billable', false);
    }

    public static function startTimer(Task $task, User $user, bool $isBillable = true): self
    {
        return self::create([
            'team_id' => $task->team_id,
            'user_id' => $user->id,
            'task_id' => $task->id,
            'hours' => 0,
            'date' => now()->toDateString(),
            'mode' => TimeTrackingMode::Timer,
            'is_billable' => $isBillable,
            'started_at' => now(),
        ]);
    }

    public function stopTimer(): void
    {
        $this->stopped_at = now();
        $this->hours = $this->started_at->diffInMinutes($this->stopped_at) / 60;
        $this->save();

        // Update task actual hours
        $this->task->recalculateActualHours();
    }
}
