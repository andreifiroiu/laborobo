<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSkill extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'skill_name',
        'proficiency',
    ];

    protected $casts = [
        'proficiency' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getProficiencyLabelAttribute(): string
    {
        return match($this->proficiency) {
            1 => 'Basic',
            2 => 'Intermediate',
            3 => 'Advanced',
            default => 'Unknown',
        };
    }
}
