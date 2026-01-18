<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AIConfidence;
use App\Enums\InboxItemType;
use App\Enums\SourceType;
use App\Enums\Urgency;
use App\Models\InboxItem;
use App\Models\Team;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InboxItem>
 */
class InboxItemFactory extends Factory
{
    protected $model = InboxItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'type' => fake()->randomElement(InboxItemType::cases()),
            'title' => fake()->sentence(4),
            'content_preview' => fake()->paragraph(),
            'full_content' => fake()->paragraphs(3, true),
            'source_id' => 'user-'.fake()->numberBetween(1, 100),
            'source_name' => fake()->name(),
            'source_type' => fake()->randomElement(SourceType::cases()),
            'urgency' => fake()->randomElement(Urgency::cases()),
            'ai_confidence' => fake()->optional()->randomElement(AIConfidence::cases()),
        ];
    }

    public function approval(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => InboxItemType::Approval,
        ]);
    }

    public function agentDraft(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => InboxItemType::AgentDraft,
            'source_type' => SourceType::AIAgent,
        ]);
    }

    public function flag(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => InboxItemType::Flag,
        ]);
    }

    public function mention(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => InboxItemType::Mention,
        ]);
    }

    public function urgent(): static
    {
        return $this->state(fn (array $attributes) => [
            'urgency' => Urgency::Urgent,
        ]);
    }

    public function forWorkOrder(WorkOrder $workOrder): static
    {
        return $this->state(fn (array $attributes) => [
            'team_id' => $workOrder->team_id,
            'related_work_order_id' => $workOrder->id,
            'related_work_order_title' => $workOrder->title,
            'related_project_id' => $workOrder->project_id,
            'related_project_name' => $workOrder->project?->name,
            'approvable_type' => WorkOrder::class,
            'approvable_id' => $workOrder->id,
        ]);
    }

    public function withReviewer(User $reviewer): static
    {
        return $this->state(fn (array $attributes) => [
            'reviewer_id' => $reviewer->id,
        ]);
    }
}
