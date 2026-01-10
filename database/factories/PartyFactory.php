<?php

namespace Database\Factories;

use App\Enums\PartyType;
use App\Models\Party;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Party>
 */
class PartyFactory extends Factory
{
    protected $model = Party::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(PartyType::cases());

        return [
            'team_id' => Team::factory(),
            'name' => match ($type) {
                PartyType::Client => fake()->company(),
                PartyType::Vendor => fake()->company() . ' Services',
                PartyType::Department => 'Internal - ' . fake()->randomElement(['Marketing', 'Engineering', 'Sales', 'Operations']),
                PartyType::TeamMember => fake()->name(),
            },
            'type' => $type,
            'contact_name' => $type !== PartyType::Department ? fake()->name() : null,
            'contact_email' => $type !== PartyType::Department ? fake()->companyEmail() : null,
        ];
    }

    public function client(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PartyType::Client,
            'name' => fake()->company(),
            'contact_name' => fake()->name(),
            'contact_email' => fake()->companyEmail(),
        ]);
    }

    public function vendor(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PartyType::Vendor,
            'name' => fake()->company() . ' Services',
            'contact_name' => fake()->name(),
            'contact_email' => fake()->companyEmail(),
        ]);
    }

    public function department(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PartyType::Department,
            'name' => 'Internal - ' . fake()->randomElement(['Marketing', 'Engineering', 'Sales', 'Operations']),
            'contact_name' => null,
            'contact_email' => null,
        ]);
    }
}
