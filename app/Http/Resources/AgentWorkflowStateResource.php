<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\AgentWorkflowState
 */
class AgentWorkflowStateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'team_id' => $this->team_id,
            'ai_agent_id' => $this->ai_agent_id,
            'workflow_class' => $this->workflow_class,
            'workflow_name' => class_basename($this->workflow_class),
            'current_node' => $this->current_node,
            'state_data' => $this->state_data ?? [],
            'paused_at' => $this->paused_at?->toISOString(),
            'resumed_at' => $this->resumed_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'pause_reason' => $this->pause_reason,
            'approval_required' => $this->approval_required,
            'status' => $this->getStatus(),
            'is_paused' => $this->isPaused(),
            'is_completed' => $this->isCompleted(),
            'is_running' => $this->isRunning(),
            'agent' => $this->whenLoaded('agent', fn () => [
                'id' => $this->agent->id,
                'code' => $this->agent->code,
                'name' => $this->agent->name,
            ]),
            'activity_logs_count' => $this->whenCounted('activityLogs'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * Get the current status of the workflow.
     */
    private function getStatus(): string
    {
        if ($this->isCompleted()) {
            return 'completed';
        }

        if ($this->isPaused()) {
            return 'paused';
        }

        return 'running';
    }
}
