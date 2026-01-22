<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CommunicationThread;
use App\Models\Project;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CommunicationThread>
 */
class CommunicationThreadFactory extends Factory
{
    protected $model = CommunicationThread::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'threadable_type' => Project::class,
            'threadable_id' => Project::factory(),
            'message_count' => 0,
            'last_activity' => now(),
        ];
    }

    /**
     * Set the threadable entity.
     */
    public function for(string $type, int $id): static
    {
        return $this->state(fn (array $attributes) => [
            'threadable_type' => $type,
            'threadable_id' => $id,
        ]);
    }

    /**
     * Set a specific message count.
     */
    public function withMessageCount(int $count): static
    {
        return $this->state(fn (array $attributes) => [
            'message_count' => $count,
        ]);
    }
}
