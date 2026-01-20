<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Project;
use App\Models\Team;
use App\Models\WorkOrderList;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WorkOrderList>
 */
class WorkOrderListFactory extends Factory
{
    protected $model = WorkOrderList::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'project_id' => Project::factory(),
            'name' => fake()->words(2, true),
            'description' => fake()->optional(0.5)->sentence(),
            'color' => fake()->optional(0.7)->hexColor(),
            'position' => fake()->numberBetween(0, 1000),
        ];
    }

    public function withPosition(int $position): static
    {
        return $this->state(fn (array $attributes) => [
            'position' => $position,
        ]);
    }

    public function withColor(string $color): static
    {
        return $this->state(fn (array $attributes) => [
            'color' => $color,
        ]);
    }
}
