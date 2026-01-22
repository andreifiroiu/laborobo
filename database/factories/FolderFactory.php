<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Folder;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Folder>
 */
class FolderFactory extends Factory
{
    protected $model = Folder::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'project_id' => null,
            'parent_id' => null,
            'name' => fake()->words(2, true),
            'created_by_id' => User::factory(),
        ];
    }

    /**
     * Set the folder as project-scoped.
     */
    public function forProject(int $projectId): static
    {
        return $this->state(fn (array $attributes) => [
            'project_id' => $projectId,
        ]);
    }

    /**
     * Set the folder as a child of another folder.
     */
    public function childOf(int $parentId): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parentId,
        ]);
    }

    /**
     * Create a team-scoped folder (not tied to a project).
     */
    public function teamScoped(): static
    {
        return $this->state(fn (array $attributes) => [
            'project_id' => null,
        ]);
    }
}
