<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AgentChainExecution;
use App\Models\AgentChainExecutionStep;
use App\Models\AgentWorkflowState;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AgentChainExecutionStep>
 */
class AgentChainExecutionStepFactory extends Factory
{
    protected $model = AgentChainExecutionStep::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'agent_chain_execution_id' => AgentChainExecution::factory(),
            'agent_workflow_state_id' => null,
            'step_index' => 0,
            'status' => 'pending',
            'output_data' => [],
        ];
    }

    /**
     * Indicate that the step is running.
     */
    public function running(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    /**
     * Indicate that the step is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'started_at' => now()->subMinutes(2),
            'completed_at' => now(),
            'output_data' => ['result' => 'success'],
        ]);
    }

    /**
     * Indicate that the step has failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'started_at' => now()->subMinutes(2),
            'completed_at' => now(),
            'output_data' => ['error' => 'Step failed'],
        ]);
    }
}
