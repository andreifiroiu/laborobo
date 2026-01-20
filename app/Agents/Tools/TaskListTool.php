<?php

declare(strict_types=1);

namespace App\Agents\Tools;

use App\Contracts\Tools\ToolInterface;
use App\Models\Task;
use InvalidArgumentException;

/**
 * Tool for listing tasks associated with a project or work order.
 *
 * This tool allows agents to retrieve task information for planning,
 * status tracking, and task management operations.
 */
class TaskListTool implements ToolInterface
{
    /**
     * Get the unique identifier name for this tool.
     */
    public function name(): string
    {
        return 'task-list';
    }

    /**
     * Get a human-readable description of what this tool does.
     */
    public function description(): string
    {
        return 'Lists tasks for a specified work order or project. Returns task details including title, status, assignee, and due date.';
    }

    /**
     * Get the category this tool belongs to.
     */
    public function category(): string
    {
        return 'tasks';
    }

    /**
     * Execute the tool with the given parameters.
     *
     * @param  array<string, mixed>  $params  The parameters for tool execution
     * @return array<string, mixed> The result data from execution
     *
     * @throws InvalidArgumentException If neither work_order_id nor project_id is provided
     */
    public function execute(array $params): array
    {
        $workOrderId = $params['work_order_id'] ?? null;
        $projectId = $params['project_id'] ?? null;
        $status = $params['status'] ?? null;
        $limit = min($params['limit'] ?? 50, 100);

        if ($workOrderId === null && $projectId === null) {
            throw new InvalidArgumentException(
                'Either work_order_id or project_id must be provided'
            );
        }

        $query = Task::query()
            ->select([
                'id',
                'title',
                'description',
                'status',
                'due_date',
                'estimated_hours',
                'actual_hours',
                'assigned_to_id',
                'is_blocked',
                'created_at',
                'updated_at',
            ])
            ->with(['assignedTo:id,name,email']);

        if ($workOrderId !== null) {
            $query->where('work_order_id', $workOrderId);
        }

        if ($projectId !== null) {
            $query->where('project_id', $projectId);
        }

        if ($status !== null) {
            $query->where('status', $status);
        }

        $tasks = $query
            ->orderBy('due_date')
            ->limit($limit)
            ->get();

        return [
            'tasks' => $tasks->map(fn (Task $task) => [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'status' => $task->status?->value,
                'due_date' => $task->due_date?->toDateString(),
                'estimated_hours' => $task->estimated_hours,
                'actual_hours' => $task->actual_hours,
                'is_blocked' => $task->is_blocked,
                'assignee' => $task->assignedTo ? [
                    'id' => $task->assignedTo->id,
                    'name' => $task->assignedTo->name,
                ] : null,
            ])->toArray(),
            'count' => $tasks->count(),
            'filters_applied' => [
                'work_order_id' => $workOrderId,
                'project_id' => $projectId,
                'status' => $status,
                'limit' => $limit,
            ],
        ];
    }

    /**
     * Get the parameter definitions for this tool.
     *
     * @return array<string, array{type: string, description: string, required: bool}>
     */
    public function getParameters(): array
    {
        return [
            'work_order_id' => [
                'type' => 'integer',
                'description' => 'The ID of the work order to list tasks for',
                'required' => false,
            ],
            'project_id' => [
                'type' => 'integer',
                'description' => 'The ID of the project to list tasks for',
                'required' => false,
            ],
            'status' => [
                'type' => 'string',
                'description' => 'Filter tasks by status (todo, in-progress, in-review, approved, done, blocked, cancelled)',
                'required' => false,
            ],
            'limit' => [
                'type' => 'integer',
                'description' => 'Maximum number of tasks to return (default: 50, max: 100)',
                'required' => false,
            ],
        ];
    }
}
