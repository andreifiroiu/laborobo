<?php

declare(strict_types=1);

namespace App\Agents\Tools;

use App\Contracts\Tools\ToolInterface;
use App\Models\WorkOrder;
use InvalidArgumentException;

/**
 * Tool for retrieving work order details.
 *
 * This tool allows agents to get comprehensive information about a work order
 * including its status, tasks, deliverables, and related project/client data.
 */
class WorkOrderInfoTool implements ToolInterface
{
    /**
     * Get the unique identifier name for this tool.
     */
    public function name(): string
    {
        return 'work-order-info';
    }

    /**
     * Get a human-readable description of what this tool does.
     */
    public function description(): string
    {
        return 'Retrieves detailed information about a specific work order including status, tasks, deliverables, and related project information.';
    }

    /**
     * Get the category this tool belongs to.
     */
    public function category(): string
    {
        return 'work_orders';
    }

    /**
     * Execute the tool with the given parameters.
     *
     * @param  array<string, mixed>  $params  The parameters for tool execution
     * @return array<string, mixed> The result data from execution
     *
     * @throws InvalidArgumentException If work_order_id is not provided or work order not found
     */
    public function execute(array $params): array
    {
        $workOrderId = $params['work_order_id'] ?? null;
        $includeTaskSummary = $params['include_task_summary'] ?? true;
        $includeDeliverables = $params['include_deliverables'] ?? false;

        if ($workOrderId === null) {
            throw new InvalidArgumentException('work_order_id is required');
        }

        $workOrder = WorkOrder::query()
            ->with([
                'project:id,name,status',
                'assignedTo:id,name,email',
                'createdBy:id,name,email',
                'accountable:id,name,email',
                'responsible:id,name,email',
            ])
            ->find($workOrderId);

        if ($workOrder === null) {
            throw new InvalidArgumentException(
                "Work order with ID {$workOrderId} not found"
            );
        }

        $result = [
            'work_order' => [
                'id' => $workOrder->id,
                'title' => $workOrder->title,
                'description' => $workOrder->description,
                'status' => $workOrder->status?->value,
                'priority' => $workOrder->priority?->value,
                'due_date' => $workOrder->due_date?->toDateString(),
                'estimated_hours' => $workOrder->estimated_hours,
                'actual_hours' => $workOrder->actual_hours,
                'acceptance_criteria' => $workOrder->acceptance_criteria,
                'sop_attached' => $workOrder->sop_attached,
                'sop_name' => $workOrder->sop_name,
                'created_at' => $workOrder->created_at?->toDateTimeString(),
                'updated_at' => $workOrder->updated_at?->toDateTimeString(),
                'project' => $workOrder->project ? [
                    'id' => $workOrder->project->id,
                    'name' => $workOrder->project->name,
                    'status' => $workOrder->project->status?->value,
                ] : null,
                'assigned_to' => $workOrder->assignedTo ? [
                    'id' => $workOrder->assignedTo->id,
                    'name' => $workOrder->assignedTo->name,
                ] : null,
                'created_by' => $workOrder->createdBy ? [
                    'id' => $workOrder->createdBy->id,
                    'name' => $workOrder->createdBy->name,
                ] : null,
                'accountable' => $workOrder->accountable ? [
                    'id' => $workOrder->accountable->id,
                    'name' => $workOrder->accountable->name,
                ] : null,
                'responsible' => $workOrder->responsible ? [
                    'id' => $workOrder->responsible->id,
                    'name' => $workOrder->responsible->name,
                ] : null,
            ],
        ];

        // Add task summary if requested
        if ($includeTaskSummary) {
            $taskStats = $this->getTaskSummary($workOrder);
            $result['work_order']['task_summary'] = $taskStats;
        }

        // Add deliverables if requested
        if ($includeDeliverables) {
            $deliverables = $this->getDeliverables($workOrder);
            $result['work_order']['deliverables'] = $deliverables;
        }

        return $result;
    }

    /**
     * Get task summary statistics for a work order.
     *
     * @return array<string, mixed>
     */
    private function getTaskSummary(WorkOrder $workOrder): array
    {
        $tasks = $workOrder->tasks;

        $statusCounts = $tasks
            ->groupBy(fn ($task) => $task->status?->value ?? 'unknown')
            ->map(fn ($group) => $group->count());

        return [
            'total' => $tasks->count(),
            'completed' => ($statusCounts['done'] ?? 0) + ($statusCounts['approved'] ?? 0),
            'in_progress' => $statusCounts['in-progress'] ?? 0,
            'todo' => $statusCounts['todo'] ?? 0,
            'blocked' => $statusCounts['blocked'] ?? 0,
            'in_review' => $statusCounts['in-review'] ?? 0,
            'by_status' => $statusCounts->toArray(),
        ];
    }

    /**
     * Get deliverables for a work order.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getDeliverables(WorkOrder $workOrder): array
    {
        return $workOrder->deliverables
            ->map(fn ($deliverable) => [
                'id' => $deliverable->id,
                'name' => $deliverable->name,
                'status' => $deliverable->status?->value,
                'due_date' => $deliverable->due_date?->toDateString(),
            ])
            ->toArray();
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
                'description' => 'The ID of the work order to retrieve information for',
                'required' => true,
            ],
            'include_task_summary' => [
                'type' => 'boolean',
                'description' => 'Whether to include task summary statistics (default: true)',
                'required' => false,
            ],
            'include_deliverables' => [
                'type' => 'boolean',
                'description' => 'Whether to include deliverable details (default: false)',
                'required' => false,
            ],
        ];
    }
}
