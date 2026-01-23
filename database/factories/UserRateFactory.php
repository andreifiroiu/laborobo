<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Team;
use App\Models\User;
use App\Models\UserRate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserRate>
 */
class UserRateFactory extends Factory
{
    protected $model = UserRate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $internalRate = fake()->randomFloat(2, 25, 150);

        return [
            'team_id' => Team::factory(),
            'user_id' => User::factory(),
            'internal_rate' => $internalRate,
            'billing_rate' => $internalRate * fake()->randomFloat(2, 1.5, 3.0),
            'effective_date' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
