<?php

declare(strict_types=1);

namespace App\Services;

use App\Agents\Workflows\TaskExecutionWorkflow;
use App\Models\AgentWorkflowState;
use App\Models\AIAgent;
use App\Models\Task;
use App\Models\Team;
use App\Models\WorkOrder;
use App\Services\AI\LLMService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

/**
 * LLM-powered task delegation service.
 *
 * Analyzes tasks and suggests optimal assignees (AI agents or team members),
 * and triggers TaskExecutionWorkflow when a task is assigned to an AI agent.
 */
class TaskDelegationService
{
    public function __construct(
        private readonly LLMService $llmService,
    ) {}

    /**
     * Use LLM to suggest assignees for a set of tasks.
     *
     * @param  Collection<int, Task>  $tasks
     * @return array<int, array{task_id: int, assignee_type: string, assignee_id: int, assignee_name: string, reasoning: string}>
     */
    public function delegateTasks(WorkOrder $workOrder, Collection $tasks, int $teamId): array
    {
        // Load team members
        $teamMembers = $workOrder->project?->team?->users?->push($workOrder->project->team->owner)
            ->unique('id')
            ->filter()
            ->values() ?? collect();

        // Load enabled AI agents for this team
        $agents = AIAgent::whereHas('configurations', fn ($q) => $q->where('team_id', $teamId)->where('enabled', true))->get();

        $prompt = $this->buildDelegationPrompt($workOrder, $tasks, $teamMembers, $agents);

        try {
            $response = $this->llmService->complete(
                systemPrompt: 'You are a PM assistant. Analyze tasks and assign to the best available resource. Respond with valid JSON only.',
                userPrompt: $prompt,
                teamId: $teamId,
            );

            if ($response === null) {
                return [];
            }

            $decoded = json_decode($response->content, true);

            if (! is_array($decoded) || ! isset($decoded['assignments'])) {
                return [];
            }

            return $decoded['assignments'];
        } catch (\Throwable $e) {
            Log::warning('Task delegation LLM call failed', [
                'work_order_id' => $workOrder->id,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Start a TaskExecutionWorkflow for a task assigned to an AI agent.
     */
    public function startTaskExecution(Task $task, AIAgent $agent, Team $team): AgentWorkflowState
    {
        /** @var TaskExecutionWorkflow $workflow */
        $workflow = App::make(TaskExecutionWorkflow::class);

        $workflowState = $workflow->start([
            'task_id' => $task->id,
            'agent_id' => $agent->id,
            'team_id' => $team->id,
            'work_order_id' => $task->work_order_id,
        ], $team, $agent);

        $workflow->run();

        return $workflowState;
    }

    /**
     * Build the LLM prompt for task delegation.
     *
     * @param  Collection<int, Task>  $tasks
     * @param  Collection<int, mixed>  $teamMembers
     * @param  Collection<int, AIAgent>  $agents
     */
    private function buildDelegationPrompt(
        WorkOrder $workOrder,
        Collection $tasks,
        Collection $teamMembers,
        Collection $agents,
    ): string {
        $membersJson = $teamMembers->map(fn ($u) => [
            'id' => $u->id,
            'name' => $u->name,
        ])->values()->toJson();

        $agentsJson = $agents->map(fn (AIAgent $a) => [
            'id' => $a->id,
            'name' => $a->name,
            'type' => $a->type->value,
            'description' => $a->description,
        ])->values()->toJson();

        $tasksJson = $tasks->map(fn (Task $t) => [
            'id' => $t->id,
            'title' => $t->title,
            'description' => $t->description,
            'estimated_hours' => $t->estimated_hours,
            'checklist_items' => $t->checklist_items ?? [],
        ])->values()->toJson();

        return <<<PROMPT
## Work Order
Title: {$workOrder->title}
Description: {$workOrder->description}

## Available Team Members
{$membersJson}

## Available AI Agents
{$agentsJson}

## Tasks to Assign
{$tasksJson}

## Instructions
Analyze each task and assign it to the best available resource (team member or AI agent).
Consider task complexity, required skills, and agent capabilities.

Respond with this exact JSON structure:
{
  "assignments": [
    {
      "task_id": <integer>,
      "assignee_type": "agent" or "user",
      "assignee_id": <integer>,
      "assignee_name": "<string>",
      "reasoning": "<string>"
    }
  ]
}
PROMPT;
    }
}
