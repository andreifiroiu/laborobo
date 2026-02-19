<?php

declare(strict_types=1);

namespace App\Agents\Workflows;

use App\Enums\AIConfidence;
use App\Enums\InboxItemType;
use App\Enums\SourceType;
use App\Enums\TaskStatus;
use App\Enums\Urgency;
use App\Models\AgentWorkflowState;
use App\Models\InboxItem;
use App\Models\Task;

/**
 * Workflow for executing a task assigned to an AI agent.
 *
 * Steps: analyze the task, execute via agent, present results for
 * human review (checkpoint), then apply approved/rejected outcome.
 */
class TaskExecutionWorkflow extends BaseAgentWorkflow
{
    public function getIdentifier(): string
    {
        return 'task-execution';
    }

    public function getDescription(): string
    {
        return 'Analyzes and executes a task assigned to an AI agent, then presents results for human review.';
    }

    /**
     * @return array<string, callable>
     */
    protected function defineSteps(): array
    {
        return [
            'analyze_task' => fn (AgentWorkflowState $state) => $this->analyzeTask($state),
            'execute_task' => fn (AgentWorkflowState $state) => $this->executeTask($state),
            'present_results' => fn (AgentWorkflowState $state) => $this->presentResults($state),
            'apply_results' => fn (AgentWorkflowState $state) => $this->applyResults($state),
        ];
    }

    /**
     * Step 1: Load task with work order context and produce a structured analysis.
     *
     * @return array{status: string, task_analysis: array<string, mixed>}
     */
    protected function analyzeTask(AgentWorkflowState $state): array
    {
        $input = $state->state_data['input'] ?? [];
        $taskId = $input['task_id'] ?? null;

        $task = Task::with(['workOrder.project', 'assignedTo'])->find($taskId);

        $analysis = [
            'analyzed_at' => now()->toIso8601String(),
            'task' => $task ? [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'estimated_hours' => $task->estimated_hours,
                'checklist_items' => $task->checklist_items ?? [],
                'status' => $task->status->value,
            ] : null,
            'work_order' => $task?->workOrder ? [
                'id' => $task->workOrder->id,
                'title' => $task->workOrder->title,
                'description' => $task->workOrder->description,
                'acceptance_criteria' => $task->workOrder->acceptance_criteria ?? [],
            ] : null,
            'project' => $task?->workOrder?->project ? [
                'id' => $task->workOrder->project->id,
                'name' => $task->workOrder->project->name,
            ] : null,
        ];

        $this->mergeStateData($state, [
            'task_analysis' => $analysis,
        ]);

        return [
            'status' => 'completed',
            'task_analysis' => $analysis,
        ];
    }

    /**
     * Step 2: Execute the task using the assigned AI agent.
     *
     * @return array{status: string, execution_result: array<string, mixed>}
     */
    protected function executeTask(AgentWorkflowState $state): array
    {
        $input = $state->state_data['input'] ?? [];
        $analysis = $state->state_data['task_analysis'] ?? [];

        $taskData = $analysis['task'] ?? [];
        $workOrderData = $analysis['work_order'] ?? [];

        // Build execution result — the actual agent runner integration
        // can be expanded here when AgentRunner is available.
        $executionResult = [
            'executed_at' => now()->toIso8601String(),
            'agent_id' => $input['agent_id'] ?? null,
            'task_id' => $input['task_id'] ?? null,
            'summary' => 'Executed task: ' . ($taskData['title'] ?? 'Unknown'),
            'work_order_context' => $workOrderData['title'] ?? null,
            'output' => null,
            'status' => 'completed',
        ];

        $this->mergeStateData($state, [
            'execution_result' => $executionResult,
        ]);

        return [
            'status' => 'completed',
            'execution_result' => $executionResult,
        ];
    }

    /**
     * Step 3: Pause for human review by creating an InboxItem.
     *
     * @return array{status: string}
     */
    protected function presentResults(AgentWorkflowState $state): array
    {
        $input = $state->state_data['input'] ?? [];
        $taskId = $input['task_id'] ?? null;
        $executionResult = $state->state_data['execution_result'] ?? [];

        $task = Task::with('workOrder.project')->find($taskId);

        if ($task !== null) {
            $agent = $state->agent;

            InboxItem::create([
                'team_id' => $input['team_id'] ?? $task->workOrder?->team_id,
                'type' => InboxItemType::Approval,
                'title' => "Task Execution Review: {$task->title}",
                'content_preview' => "AI agent completed task: {$task->title}",
                'full_content' => $this->buildExecutionContent($task, $executionResult),
                'source_type' => SourceType::AIAgent,
                'source_id' => $agent ? "agent-{$agent->id}" : 'agent-task-execution',
                'source_name' => $agent->name ?? 'Task Execution Agent',
                'approvable_type' => AgentWorkflowState::class,
                'approvable_id' => $state->id,
                'related_work_order_id' => $task->work_order_id,
                'related_work_order_title' => $task->workOrder?->title,
                'related_project_id' => $task->workOrder?->project?->id,
                'related_project_name' => $task->workOrder?->project?->name,
                'ai_confidence' => AIConfidence::Medium,
                'urgency' => Urgency::Normal,
            ]);
        }

        $this->pauseForApproval(
            'Task execution review required',
            'Review the AI agent execution results for this task'
        );

        return [
            'status' => 'paused',
        ];
    }

    /**
     * Step 4: Apply results after human review.
     *
     * @return array{status: string}
     */
    protected function applyResults(AgentWorkflowState $state): array
    {
        $input = $state->state_data['input'] ?? [];
        $approvalData = $state->state_data['approval_data'] ?? [];
        $taskId = $input['task_id'] ?? null;

        $task = Task::find($taskId);

        if ($task !== null) {
            $approved = ($approvalData['approved'] ?? false) === true;

            if ($approved) {
                $task->update(['status' => TaskStatus::Done]);
            } else {
                $task->update(['status' => TaskStatus::RevisionRequested]);
            }
        }

        $this->complete([
            'task_id' => $taskId,
            'outcome' => ($approvalData['approved'] ?? false) ? 'approved' : 'rejected',
            'completed_at' => now()->toIso8601String(),
        ]);

        return [
            'status' => 'completed',
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     */
    protected function onStart(array $input): void
    {
        $this->mergeStateData($this->state, [
            'started_at' => now()->toIso8601String(),
            'input' => $input,
        ]);
    }

    /**
     * @param  array<string, mixed>  $approvalData
     */
    protected function onResume(array $approvalData): void
    {
        $this->mergeStateData($this->state, [
            'approval_data' => $approvalData,
            'resumed_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $result
     */
    protected function onComplete(array $result): void
    {
        $this->mergeStateData($this->state, [
            'final_result' => $result,
            'completed_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $executionResult
     */
    private function buildExecutionContent(Task $task, array $executionResult): string
    {
        $lines = [];
        $lines[] = "## Task: {$task->title}";
        $lines[] = '';

        if ($task->description) {
            $lines[] = "**Description:** {$task->description}";
            $lines[] = '';
        }

        $lines[] = '### Execution Result';
        $lines[] = '';
        $lines[] = '**Status:** ' . ($executionResult['status'] ?? 'unknown');
        $lines[] = '**Summary:** ' . ($executionResult['summary'] ?? 'No summary available');
        $lines[] = '';

        if (! empty($executionResult['output'])) {
            $lines[] = '### Output';
            $lines[] = '';
            $lines[] = $executionResult['output'];
        }

        return implode("\n", $lines);
    }

    /**
     * @param  AgentWorkflowState  $state
     * @param  array<string, mixed>  $data
     */
    protected function mergeStateData(AgentWorkflowState $state, array $data): void
    {
        $stateData = $state->state_data ?? [];
        $stateData = array_merge($stateData, $data);
        $state->update(['state_data' => $stateData]);
    }
}
