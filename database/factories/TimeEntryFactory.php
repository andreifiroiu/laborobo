<?php

namespace Database\Factories;

use App\Enums\TimeTrackingMode;
use App\Models\Task;
use App\Models\Team;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TimeEntry>
 */
class TimeEntryFactory extends Factory
{
    protected $model = TimeEntry::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $mode = fake()->randomElement(TimeTrackingMode::cases());
        $hours = fake()->randomFloat(2, 0.25, 8);
        $date = fake()->dateTimeBetween('-1 month', 'now');

        return [
            'team_id' => Team::factory(),
            'user_id' => User::factory(),
            'task_id' => Task::factory(),
            'hours' => $hours,
            'date' => $date,
            'mode' => $mode,
            'note' => fake()->optional(0.3)->sentence(),
            'started_at' => $mode === TimeTrackingMode::Timer ? $date : null,
            'stopped_at' => $mode === TimeTrackingMode::Timer ? (clone $date)->modify('+' . ($hours * 60) . ' minutes') : null,
        ];
    }

    public function manual(): static
    {
        return $this->state(fn (array $attributes) => [
            'mode' => TimeTrackingMode::Manual,
            'started_at' => null,
            'stopped_at' => null,
        ]);
    }

    public function timer(): static
    {
        return $this->state(function (array $attributes) {
            $startedAt = fake()->dateTimeBetween('-1 week', 'now');
            $hours = fake()->randomFloat(2, 0.25, 4);

            return [
                'mode' => TimeTrackingMode::Timer,
                'started_at' => $startedAt,
                'stopped_at' => (clone $startedAt)->modify('+' . ($hours * 60) . ' minutes'),
                'hours' => $hours,
            ];
        });
    }
}
