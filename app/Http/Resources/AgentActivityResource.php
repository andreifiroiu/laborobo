<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\AgentActivityLog
 */
class AgentActivityResource extends JsonResource
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
            'run_type' => $this->run_type,
            'input' => $this->input,
            'output' => $this->output,
            'tokens_used' => $this->tokens_used,
            'cost' => $this->cost,
            'approval_status' => $this->approval_status?->value,
            'approval_status_label' => $this->approval_status?->label(),
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at?->toISOString(),
            'error' => $this->error,
            'tool_calls' => $this->tool_calls ?? [],
            'tool_call_count' => $this->tool_call_count,
            'context_accessed' => $this->context_accessed ?? [],
            'workflow_state_id' => $this->workflow_state_id,
            'duration_ms' => $this->duration_ms,
            'agent' => $this->whenLoaded('agent', fn () => [
                'id' => $this->agent->id,
                'code' => $this->agent->code,
                'name' => $this->agent->name,
            ]),
            'approver' => $this->whenLoaded('approver', fn () => [
                'id' => $this->approver->id,
                'name' => $this->approver->name,
            ]),
            'workflow_state' => $this->whenLoaded('workflowState', fn () => new AgentWorkflowStateResource($this->workflowState)),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
