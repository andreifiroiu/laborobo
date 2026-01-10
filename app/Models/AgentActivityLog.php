<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use Illuminate\Database\Eloquent\Model;

class AgentActivityLog extends Model
{
    protected $fillable = [
        'team_id',
        'ai_agent_id',
        'run_type',
        'input',
        'output',
        'tokens_used',
        'cost',
        'approval_status',
        'approved_by',
        'approved_at',
        'error',
    ];

    protected $casts = [
        'cost' => 'decimal:4',
        'approved_at' => 'datetime',
        'approval_status' => ApprovalStatus::class,
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function agent()
    {
        return $this->belongsTo(AIAgent::class, 'ai_agent_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopePending($query)
    {
        return $query->where('approval_status', ApprovalStatus::Pending);
    }
}
