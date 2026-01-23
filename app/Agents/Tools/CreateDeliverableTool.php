<?php

declare(strict_types=1);

namespace App\Agents\Tools;

use App\Contracts\Tools\ToolInterface;
use App\Enums\AIConfidence;
use App\Enums\DeliverableStatus;
use App\Enums\DeliverableType;
use App\Models\Deliverable;
use App\Models\Team;
use App\Models\WorkOrder;
use Carbon\Carbon;
use InvalidArgumentException;

/**
 * Tool for creating deliverables from agent recommendations.
 *
 * Creates a deliverable with Draft status by default, allowing for human review
 * before approval. Used by PM Copilot Agent for deliverable generation workflows.
 */
class CreateDeliverableTool implements ToolInterface
{
    /**
     * Get the unique identifier name for this tool.
     */
    public function name(): string
    {
        return 'create-deliverable';
    }

    /**
     * Get a human-readable description of what this tool does.
     */
    public function description(): string
    {
        return 'Creates a new deliverable for a work order with Draft status. Used for generating deliverable structures from work order analysis.';
    }

    /**
     * Get the category this tool belongs to.
     */
    public function category(): string
    {
        return 'deliverables';
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
        $workOrderId = $params['work_order_id'];
        $title = $params['title'];
        $description = $params['description'] ?? null;
        $type = $params['type'] ?? 'other';
        $acceptanceCriteria = $params['acceptance_criteria'] ?? [];

        // Validate team exists
        $team = Team::find($teamId);
        if ($team === null) {
            throw new InvalidArgumentException("Team with ID {$teamId} not found");
        }

        // Validate work order exists and belongs to team
        $workOrder = WorkOrder::where('id', $workOrderId)
            ->where('team_id', $teamId)
            ->first();
        if ($workOrder === null) {
            throw new InvalidArgumentException("Work order with ID {$workOrderId} not found or does not belong to team");
        }

        // Parse deliverable type
        $deliverableType = $this->parseDeliverableType($type);

        // Calculate confidence level based on input quality
        $confidence = $this->determineConfidence(
            hasDescription: ! empty($description),
            hasAcceptanceCriteria: ! empty($acceptanceCriteria),
            hasType: $type !== 'other'
        );

        // Create the deliverable with Draft status
        $deliverable = Deliverable::create([
            'team_id' => $teamId,
            'work_order_id' => $workOrderId,
            'project_id' => $workOrder->project_id,
            'title' => $title,
            'description' => $description,
            'type' => $deliverableType,
            'status' => DeliverableStatus::Draft,
            'acceptance_criteria' => is_array($acceptanceCriteria) ? $acceptanceCriteria : [],
            'version' => '1.0',
            'created_date' => Carbon::now(),
        ]);

        return [
            'success' => true,
            'deliverable' => [
                'id' => $deliverable->id,
                'title' => $deliverable->title,
                'description' => $deliverable->description,
                'type' => $deliverable->type?->value,
                'status' => $deliverable->status->value,
                'acceptance_criteria' => $deliverable->acceptance_criteria,
                'version' => $deliverable->version,
                'work_order_id' => $deliverable->work_order_id,
                'project_id' => $deliverable->project_id,
                'team_id' => $deliverable->team_id,
                'created_at' => $deliverable->created_at->toIso8601String(),
            ],
            'confidence' => $confidence->value,
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
                'description' => 'The ID of the team to create the deliverable for',
                'required' => true,
            ],
            'work_order_id' => [
                'type' => 'integer',
                'description' => 'The ID of the work order to link the deliverable to',
                'required' => true,
            ],
            'title' => [
                'type' => 'string',
                'description' => 'The title of the deliverable',
                'required' => true,
            ],
            'description' => [
                'type' => 'string',
                'description' => 'Detailed description of the deliverable',
                'required' => false,
            ],
            'type' => [
                'type' => 'string',
                'description' => 'Type of deliverable: document, design, report, code, or other (default: other)',
                'required' => false,
            ],
            'acceptance_criteria' => [
                'type' => 'array',
                'description' => 'Array of acceptance criteria for the deliverable',
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
        $required = ['team_id', 'work_order_id', 'title'];

        foreach ($required as $param) {
            if (! isset($params[$param]) || $params[$param] === null || $params[$param] === '') {
                throw new InvalidArgumentException("{$param} is required");
            }
        }
    }

    /**
     * Parse deliverable type string to enum.
     */
    private function parseDeliverableType(string $type): DeliverableType
    {
        return match (strtolower($type)) {
            'document' => DeliverableType::Document,
            'design' => DeliverableType::Design,
            'report' => DeliverableType::Report,
            'code' => DeliverableType::Code,
            default => DeliverableType::Other,
        };
    }

    /**
     * Determine confidence level based on input completeness.
     */
    private function determineConfidence(
        bool $hasDescription,
        bool $hasAcceptanceCriteria,
        bool $hasType
    ): AIConfidence {
        $score = 0;

        if ($hasDescription) {
            $score += 2;
        }

        if ($hasAcceptanceCriteria) {
            $score += 2;
        }

        if ($hasType) {
            $score += 1;
        }

        return match (true) {
            $score >= 4 => AIConfidence::High,
            $score >= 2 => AIConfidence::Medium,
            default => AIConfidence::Low,
        };
    }
}
