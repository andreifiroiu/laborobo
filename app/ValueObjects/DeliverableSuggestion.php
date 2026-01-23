<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Enums\AIConfidence;
use App\Enums\DeliverableStatus;
use App\Enums\DeliverableType;
use App\Models\Deliverable;
use App\Models\WorkOrder;
use Carbon\Carbon;

/**
 * Immutable value object representing a suggested deliverable from PM Copilot.
 *
 * Contains all information needed to present a deliverable suggestion to users
 * and create an actual Deliverable model when approved.
 */
final readonly class DeliverableSuggestion
{
    /**
     * Create a new deliverable suggestion.
     *
     * @param  string  $title  The suggested title for the deliverable
     * @param  string|null  $description  Detailed description of the deliverable
     * @param  DeliverableType  $type  The type of deliverable (document, design, report, code, other)
     * @param  array<string>  $acceptanceCriteria  List of acceptance criteria
     * @param  AIConfidence  $confidence  Confidence level of this suggestion
     * @param  string|null  $reasoning  Explanation of why this suggestion was made
     * @param  int|null  $playbookId  ID of the playbook that influenced this suggestion
     */
    public function __construct(
        public string $title,
        public ?string $description,
        public DeliverableType $type,
        public array $acceptanceCriteria = [],
        public AIConfidence $confidence = AIConfidence::Medium,
        public ?string $reasoning = null,
        public ?int $playbookId = null,
    ) {}

    /**
     * Convert the suggestion to an array for serialization.
     *
     * @return array{
     *     title: string,
     *     description: string|null,
     *     type: string,
     *     acceptance_criteria: array<string>,
     *     confidence: string,
     *     reasoning: string|null,
     *     playbook_id: int|null
     * }
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type->value,
            'acceptance_criteria' => $this->acceptanceCriteria,
            'confidence' => $this->confidence->value,
            'reasoning' => $this->reasoning,
            'playbook_id' => $this->playbookId,
        ];
    }

    /**
     * Create a Deliverable model from this suggestion.
     *
     * Creates the deliverable with Draft status for human review before approval.
     *
     * @param  WorkOrder  $workOrder  The work order to link the deliverable to
     * @return Deliverable The created deliverable model
     */
    public function createDeliverable(WorkOrder $workOrder): Deliverable
    {
        return Deliverable::create([
            'team_id' => $workOrder->team_id,
            'work_order_id' => $workOrder->id,
            'project_id' => $workOrder->project_id,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'status' => DeliverableStatus::Draft,
            'acceptance_criteria' => $this->acceptanceCriteria,
            'version' => '1.0',
            'created_date' => Carbon::now(),
        ]);
    }

    /**
     * Create a DeliverableSuggestion from an array.
     *
     * Useful for parsing LLM responses or deserializing stored suggestions.
     *
     * @param  array{
     *     title: string,
     *     description?: string|null,
     *     type?: string,
     *     acceptance_criteria?: array<string>,
     *     confidence?: string,
     *     reasoning?: string|null,
     *     playbook_id?: int|null
     * }  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            title: $data['title'],
            description: $data['description'] ?? null,
            type: self::parseDeliverableType($data['type'] ?? 'other'),
            acceptanceCriteria: $data['acceptance_criteria'] ?? [],
            confidence: self::parseConfidence($data['confidence'] ?? 'medium'),
            reasoning: $data['reasoning'] ?? null,
            playbookId: $data['playbook_id'] ?? null,
        );
    }

    /**
     * Parse a string to DeliverableType enum.
     */
    private static function parseDeliverableType(string $type): DeliverableType
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
     * Parse a string to AIConfidence enum.
     */
    private static function parseConfidence(string $confidence): AIConfidence
    {
        return match (strtolower($confidence)) {
            'high' => AIConfidence::High,
            'low' => AIConfidence::Low,
            default => AIConfidence::Medium,
        };
    }
}
