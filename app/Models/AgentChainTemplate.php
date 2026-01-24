<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgentChainTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'chain_definition',
        'category',
        'is_system',
    ];

    protected $casts = [
        'chain_definition' => 'array',
        'is_system' => 'boolean',
    ];

    /**
     * Get the chains that were created from this template.
     */
    public function chains(): HasMany
    {
        return $this->hasMany(AgentChain::class, 'agent_chain_template_id');
    }

    /**
     * Scope to filter system templates.
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope to filter by category.
     */
    public function scopeInCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
