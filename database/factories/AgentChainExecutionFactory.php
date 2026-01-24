<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ChainExecutionStatus;
use App\Models\AgentChain;
use App\Models\AgentChainExecution;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AgentChainExecution>
 */
class AgentChainExecutionFactory extends Factory
{
    protected $model = AgentChainExecution::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'agent_chain_id' => AgentChain::factory(),
            'current_step_index' => 0,
            'execution_status' => ChainExecutionStatus::Pending,
            'chain_context' => [],
        ];
    }

    /**
     * Indicate that the execution is running.
     */
    public function running(): static
    {
        return $this->state(fn (array $attributes) => [
            'execution_status' => ChainExecutionStatus::Running,
            'started_at' => now(),
        ]);
    }

    /**
     * Indicate that the execution is paused.
     */
    public function paused(): static
    {
        return $this->state(fn (array $attributes) => [
            'execution_status' => ChainExecutionStatus::Paused,
            'started_at' => now()->subMinutes(5),
            'paused_at' => now(),
        ]);
    }

    /**
     * Indicate that the execution is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'execution_status' => ChainExecutionStatus::Completed,
            'started_at' => now()->subMinutes(10),
            'completed_at' => now(),
        ]);
    }

    /**
     * Indicate that the execution has failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'execution_status' => ChainExecutionStatus::Failed,
            'started_at' => now()->subMinutes(5),
            'failed_at' => now(),
            'error_message' => fake()->sentence(),
        ]);
    }
}
