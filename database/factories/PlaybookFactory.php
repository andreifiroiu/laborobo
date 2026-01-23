<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PlaybookType;
use App\Models\Playbook;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Playbook>
 */
class PlaybookFactory extends Factory
{
    protected $model = Playbook::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'type' => fake()->randomElement(PlaybookType::cases()),
            'content' => [
                'checklist' => [
                    'Step 1: Review requirements',
                    'Step 2: Implement solution',
                    'Step 3: Test functionality',
                    'Step 4: Document changes',
                ],
            ],
            'tags' => fake()->words(3),
            'times_applied' => fake()->numberBetween(0, 50),
            'last_used' => fake()->optional()->dateTimeBetween('-6 months', 'now'),
            'created_by' => User::factory(),
            'created_by_name' => fake()->name(),
            'ai_generated' => fake()->boolean(20),
        ];
    }

    /**
     * State for a checklist-type playbook.
     */
    public function checklist(): static
    {
        return $this->state(fn () => [
            'type' => PlaybookType::Checklist,
            'content' => [
                'checklist' => [
                    'Verify requirements are clear',
                    'Create implementation plan',
                    'Implement core functionality',
                    'Write unit tests',
                    'Perform code review',
                    'Update documentation',
                ],
            ],
        ]);
    }

    /**
     * State for a template-type playbook.
     */
    public function template(): static
    {
        return $this->state(fn () => [
            'type' => PlaybookType::Template,
            'content' => [
                'deliverables' => [
                    ['title' => 'Main Deliverable', 'type' => 'document'],
                    ['title' => 'Supporting Documentation', 'type' => 'document'],
                ],
                'checklist' => [
                    'Follow template structure',
                    'Include all required sections',
                    'Review for completeness',
                ],
            ],
        ]);
    }

    /**
     * State for an SOP-type playbook.
     */
    public function sop(): static
    {
        return $this->state(fn () => [
            'type' => PlaybookType::SOP,
            'content' => [
                'steps' => [
                    ['order' => 1, 'description' => 'Initialize project'],
                    ['order' => 2, 'description' => 'Gather requirements'],
                    ['order' => 3, 'description' => 'Execute work'],
                    ['order' => 4, 'description' => 'Quality review'],
                ],
            ],
        ]);
    }
}
