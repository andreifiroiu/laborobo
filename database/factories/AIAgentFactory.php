<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AgentType;
use App\Models\AIAgent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AIAgent>
 */
class AIAgentFactory extends Factory
{
    protected $model = AIAgent::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->slug(2),
            'name' => fake()->name().' AI',
            'type' => fake()->randomElement(AgentType::cases()),
            'description' => fake()->sentence(),
            'instructions' => null,
            'tools' => [
                'task-list',
                'work-order-info',
            ],
        ];
    }
}
