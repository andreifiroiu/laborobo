<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowCustomization extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'workflow_class',
        'customizations',
        'enabled',
    ];

    protected $casts = [
        'customizations' => 'array',
        'enabled' => 'boolean',
    ];

    /**
     * Get the team that owns this customization.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Scope to filter by team.
     */
    public function scopeForTeam($query, int $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    /**
     * Scope to filter enabled customizations.
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    /**
     * Find customization for a team and workflow class.
     */
    public static function forTeamAndWorkflow(int $teamId, string $workflowClass): ?self
    {
        return static::query()
            ->where('team_id', $teamId)
            ->where('workflow_class', $workflowClass)
            ->first();
    }

    /**
     * Get the disabled steps for this workflow.
     *
     * @return array<string>
     */
    public function getDisabledSteps(): array
    {
        return $this->customizations['disabled_steps'] ?? [];
    }

    /**
     * Get the custom parameters for this workflow.
     *
     * @return array<string, mixed>
     */
    public function getParameters(): array
    {
        return $this->customizations['parameters'] ?? [];
    }

    /**
     * Get the hooks for this workflow.
     *
     * @return array<string, string>
     */
    public function getHooks(): array
    {
        return $this->customizations['hooks'] ?? [];
    }

    /**
     * Check if a specific step is disabled.
     */
    public function isStepDisabled(string $stepName): bool
    {
        return in_array($stepName, $this->getDisabledSteps(), true);
    }

    /**
     * Get a specific parameter value.
     */
    public function getParameter(string $key, mixed $default = null): mixed
    {
        return $this->customizations['parameters'][$key] ?? $default;
    }
}
