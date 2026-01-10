<?php

namespace Database\Factories;

use App\Enums\DeliverableStatus;
use App\Enums\DeliverableType;
use App\Models\Deliverable;
use App\Models\Project;
use App\Models\Team;
use App\Models\WorkOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Deliverable>
 */
class DeliverableFactory extends Factory
{
    protected $model = Deliverable::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(DeliverableType::cases());
        $status = fake()->randomElement(DeliverableStatus::cases());

        return [
            'team_id' => Team::factory(),
            'work_order_id' => WorkOrder::factory(),
            'project_id' => Project::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'type' => $type,
            'status' => $status,
            'version' => fake()->randomElement(['0.1', '0.5', '1.0', '1.1', '2.0']),
            'created_date' => fake()->dateTimeBetween('-1 month', 'now'),
            'delivered_date' => $status === DeliverableStatus::Delivered ? fake()->dateTimeBetween('-1 week', 'now') : null,
            'file_url' => '/files/' . fake()->slug() . '.' . $this->getExtensionForType($type),
            'acceptance_criteria' => [
                fake()->sentence(),
                fake()->sentence(),
            ],
        ];
    }

    private function getExtensionForType(DeliverableType $type): string
    {
        return match ($type) {
            DeliverableType::Document => fake()->randomElement(['pdf', 'docx']),
            DeliverableType::Design => fake()->randomElement(['fig', 'psd', 'ai']),
            DeliverableType::Report => 'pdf',
            DeliverableType::Code => 'zip',
            DeliverableType::Other => fake()->randomElement(['zip', 'pdf']),
        };
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DeliverableStatus::Draft,
            'delivered_date' => null,
        ]);
    }

    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DeliverableStatus::Delivered,
            'delivered_date' => fake()->dateTimeBetween('-1 week', 'now'),
        ]);
    }
}
