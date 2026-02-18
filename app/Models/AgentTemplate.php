<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AgentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgentTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'type',
        'description',
        'default_instructions',
        'default_tools',
        'default_permissions',
        'is_active',
        'default_ai_provider',
        'default_ai_model',
    ];

    protected $casts = [
        'type' => AgentType::class,
        'default_tools' => 'array',
        'default_permissions' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the AI agents created from this template.
     */
    public function agents(): HasMany
    {
        return $this->hasMany(AIAgent::class, 'template_id');
    }

    /**
     * Scope to only active templates.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
