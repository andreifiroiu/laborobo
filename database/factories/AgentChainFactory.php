<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AgentChain;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AgentChain>
 */
class AgentChainFactory extends Factory
{
    protected $model = AgentChain::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'name' => fake()->words(3, true) . ' Chain',
            'description' => fake()->sentence(),
            'chain_definition' => [
                'steps' => [
                    [
                        'agent_id' => 1,
                        'execution_mode' => 'sequential',
                        'conditions' => [],
                        'context_filter_rules' => [],
                        'next_step_conditions' => [],
                        'output_transformers' => [],
                    ],
                ],
            ],
            'is_template' => false,
            'enabled' => true,
        ];
    }

    /**
     * Indicate that the chain is disabled.
     */
    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'enabled' => false,
        ]);
    }

    /**
     * Indicate that the chain is a template.
     */
    public function template(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_template' => true,
        ]);
    }
}
