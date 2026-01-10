<?php

namespace Database\Factories;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $estimatedHours = fake()->numberBetween(1, 16);

        return [
            'team_id' => Team::factory(),
            'work_order_id' => WorkOrder::factory(),
            'project_id' => Project::factory(),
            'assigned_to_id' => fake()->optional(0.8)->passthrough(User::factory()),
            'title' => fake()->sentence(5),
            'description' => fake()->optional()->paragraph(),
            'status' => fake()->randomElement(TaskStatus::cases()),
            'due_date' => fake()->dateTimeBetween('now', '+1 month'),
            'estimated_hours' => $estimatedHours,
            'actual_hours' => fake()->numberBetween(0, $estimatedHours),
            'checklist_items' => $this->generateChecklistItems(),
            'dependencies' => [],
            'is_blocked' => fake()->boolean(10),
        ];
    }

    private function generateChecklistItems(): array
    {
        $items = [];
        $count = fake()->numberBetween(0, 5);

        for ($i = 0; $i < $count; $i++) {
            $items[] = [
                'id' => Str::uuid()->toString(),
                'text' => fake()->sentence(4),
                'completed' => fake()->boolean(50),
            ];
        }

        return $items;
    }

    public function todo(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TaskStatus::Todo,
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TaskStatus::InProgress,
        ]);
    }

    public function done(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TaskStatus::Done,
        ]);
    }

    public function blocked(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_blocked' => true,
        ]);
    }
}
