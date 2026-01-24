<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AgentChainTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AgentChainTemplate>
 */
class AgentChainTemplateFactory extends Factory
{
    protected $model = AgentChainTemplate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true) . ' Chain Template',
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
            'category' => fake()->randomElement(['work-order-processing', 'task-management', 'client-communication']),
            'is_system' => false,
        ];
    }

    /**
     * Indicate that the template is a system template.
     */
    public function system(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_system' => true,
        ]);
    }
}
