<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TriggerEntityType;
use App\Models\AgentChain;
use App\Models\AgentTrigger;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AgentTrigger>
 */
class AgentTriggerFactory extends Factory
{
    protected $model = AgentTrigger::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'name' => fake()->words(3, true) . ' Trigger',
            'entity_type' => fake()->randomElement(TriggerEntityType::cases()),
            'status_from' => null,
            'status_to' => 'active',
            'agent_chain_id' => AgentChain::factory(),
            'trigger_conditions' => [],
            'enabled' => true,
            'priority' => fake()->numberBetween(0, 100),
        ];
    }

    /**
     * Indicate that the trigger is disabled.
     */
    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'enabled' => false,
        ]);
    }

    /**
     * Configure the trigger for work orders.
     */
    public function forWorkOrders(): static
    {
        return $this->state(fn (array $attributes) => [
            'entity_type' => TriggerEntityType::WorkOrder,
        ]);
    }

    /**
     * Configure the trigger for tasks.
     */
    public function forTasks(): static
    {
        return $this->state(fn (array $attributes) => [
            'entity_type' => TriggerEntityType::Task,
        ]);
    }

    /**
     * Configure the trigger for deliverables.
     */
    public function forDeliverables(): static
    {
        return $this->state(fn (array $attributes) => [
            'entity_type' => TriggerEntityType::Deliverable,
        ]);
    }
}
