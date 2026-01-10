<?php

namespace App\Models;

use App\Enums\TimeTrackingMode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'user_id',
        'task_id',
        'hours',
        'date',
        'mode',
        'note',
        'started_at',
        'stopped_at',
    ];

    protected $casts = [
        'date' => 'date',
        'hours' => 'decimal:2',
        'mode' => TimeTrackingMode::class,
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

    public function scopeForTeam($query, int $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }

    public static function startTimer(Task $task, User $user): self
    {
        return self::create([
            'team_id' => $task->team_id,
            'user_id' => $user->id,
            'task_id' => $task->id,
            'hours' => 0,
            'date' => now()->toDateString(),
            'mode' => TimeTrackingMode::Timer,
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
