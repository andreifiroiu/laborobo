<?php

declare(strict_types=1);

namespace App\Agents\Tools;

use App\Contracts\Tools\ToolInterface;
use App\Enums\Priority;
use App\Enums\WorkOrderStatus;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use App\Models\WorkOrder;
use Carbon\Carbon;
use InvalidArgumentException;

/**
 * Tool for creating draft work orders with routing recommendations.
 *
 * Creates a work order with Draft status, populated with extracted data
 * from message analysis and routing decisions.
 */
class CreateDraftWorkOrderTool implements ToolInterface
{
    /**
     * Get the unique identifier name for this tool.
     */
    public function name(): string
    {
        return 'create-draft-work-order';
    }

    /**
     * Get a human-readable description of what this tool does.
     */
    public function description(): string
    {
        return 'Creates a draft work order with extracted requirements and assigns the top-ranked candidate as responsible. Includes routing reasoning in metadata.';
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
     * @throws InvalidArgumentException If required parameters are missing or invalid
     */
    public function execute(array $params): array
    {
        $this->validateParams($params);

        $teamId = $params['team_id'];
        $projectId = $params['project_id'];
        $title = $params['title'];
        $description = $params['description'] ?? null;
        $priority = $params['priority'] ?? 'medium';
        $dueDate = $params['due_date'] ?? null;
        $estimatedHours = $params['estimated_hours'] ?? null;
        $acceptanceCriteria = $params['acceptance_criteria'] ?? [];
        $responsibleId = $params['responsible_id'] ?? null;
        $accountableId = $params['accountable_id'] ?? null;
        $routingReasoning = $params['routing_reasoning'] ?? [];
        $createdById = $params['created_by_id'] ?? null;

        // Validate team exists
        $team = Team::find($teamId);
        if ($team === null) {
            throw new InvalidArgumentException("Team with ID {$teamId} not found");
        }

        // Validate project exists and belongs to team
        $project = Project::where('id', $projectId)
            ->where('team_id', $teamId)
            ->first();
        if ($project === null) {
            throw new InvalidArgumentException("Project with ID {$projectId} not found or does not belong to team");
        }

        // Validate responsible user if provided
        if ($responsibleId !== null) {
            $responsible = User::find($responsibleId);
            if ($responsible === null) {
                throw new InvalidArgumentException("User with ID {$responsibleId} not found");
            }
        }

        // Validate accountable user if provided
        if ($accountableId !== null) {
            $accountable = User::find($accountableId);
            if ($accountable === null) {
                throw new InvalidArgumentException("User with ID {$accountableId} not found");
            }
        }

        // Use created_by_id as accountable_id if not explicitly provided
        if ($accountableId === null && $createdById !== null) {
            $accountableId = $createdById;
        }

        // Parse priority enum
        $priorityEnum = $this->parsePriority($priority);

        // Parse due date if provided
        $parsedDueDate = $dueDate !== null ? Carbon::parse($dueDate) : null;

        // Create the draft work order
        $workOrder = WorkOrder::create([
            'team_id' => $teamId,
            'project_id' => $projectId,
            'title' => $title,
            'description' => $description,
            'status' => WorkOrderStatus::Draft,
            'priority' => $priorityEnum,
            'due_date' => $parsedDueDate,
            'estimated_hours' => $estimatedHours,
            'acceptance_criteria' => is_array($acceptanceCriteria) ? $acceptanceCriteria : [],
            'responsible_id' => $responsibleId,
            'accountable_id' => $accountableId,
            'created_by_id' => $createdById,
        ]);

        // Store routing reasoning in a separate metadata mechanism or activity log
        // Since WorkOrder doesn't have a metadata field, we'll return it in the result
        // The calling code can store this in the appropriate location

        return [
            'success' => true,
            'work_order' => [
                'id' => $workOrder->id,
                'title' => $workOrder->title,
                'description' => $workOrder->description,
                'status' => $workOrder->status->value,
                'priority' => $workOrder->priority?->value,
                'due_date' => $workOrder->due_date?->toDateString(),
                'estimated_hours' => $workOrder->estimated_hours,
                'acceptance_criteria' => $workOrder->acceptance_criteria,
                'responsible_id' => $workOrder->responsible_id,
                'accountable_id' => $workOrder->accountable_id,
                'project_id' => $workOrder->project_id,
                'team_id' => $workOrder->team_id,
                'created_at' => $workOrder->created_at->toIso8601String(),
            ],
            'routing_reasoning' => $routingReasoning,
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
            'team_id' => [
                'type' => 'integer',
                'description' => 'The ID of the team to create the work order for',
                'required' => true,
            ],
            'project_id' => [
                'type' => 'integer',
                'description' => 'The ID of the project to link the work order to',
                'required' => true,
            ],
            'title' => [
                'type' => 'string',
                'description' => 'The title of the work order',
                'required' => true,
            ],
            'description' => [
                'type' => 'string',
                'description' => 'Detailed description of the work to be done',
                'required' => false,
            ],
            'priority' => [
                'type' => 'string',
                'description' => 'Priority level: low, medium, high, or urgent (default: medium)',
                'required' => false,
            ],
            'due_date' => [
                'type' => 'string',
                'description' => 'Due date in YYYY-MM-DD format',
                'required' => false,
            ],
            'estimated_hours' => [
                'type' => 'number',
                'description' => 'Estimated hours to complete the work',
                'required' => false,
            ],
            'acceptance_criteria' => [
                'type' => 'array',
                'description' => 'Array of acceptance criteria for the work',
                'required' => false,
            ],
            'responsible_id' => [
                'type' => 'integer',
                'description' => 'User ID of the person responsible for the work (top-ranked candidate)',
                'required' => false,
            ],
            'accountable_id' => [
                'type' => 'integer',
                'description' => 'User ID of the person accountable for the work',
                'required' => false,
            ],
            'routing_reasoning' => [
                'type' => 'object',
                'description' => 'JSON object containing the reasoning for routing decisions',
                'required' => false,
            ],
            'created_by_id' => [
                'type' => 'integer',
                'description' => 'User ID of the person creating the work order',
                'required' => false,
            ],
        ];
    }

    /**
     * Validate required parameters.
     *
     * @param  array<string, mixed>  $params
     *
     * @throws InvalidArgumentException If required parameters are missing
     */
    private function validateParams(array $params): void
    {
        $required = ['team_id', 'project_id', 'title'];

        foreach ($required as $param) {
            if (! isset($params[$param]) || $params[$param] === null || $params[$param] === '') {
                throw new InvalidArgumentException("{$param} is required");
            }
        }
    }

    /**
     * Parse priority string to enum.
     */
    private function parsePriority(string $priority): Priority
    {
        return match (strtolower($priority)) {
            'low' => Priority::Low,
            'medium' => Priority::Medium,
            'high' => Priority::High,
            'urgent' => Priority::Urgent,
            default => Priority::Medium,
        };
    }
}
