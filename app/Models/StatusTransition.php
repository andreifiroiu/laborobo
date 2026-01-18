<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StatusTransition extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'transitionable_type',
        'transitionable_id',
        'user_id',
        'from_status',
        'to_status',
        'comment',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Get the parent transitionable model (Task or WorkOrder).
     */
    public function transitionable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who performed the transition.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
