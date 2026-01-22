<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DocumentShareAccess;
use App\Models\DocumentShareLink;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DocumentShareAccess>
 */
class DocumentShareAccessFactory extends Factory
{
    protected $model = DocumentShareAccess::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_share_link_id' => DocumentShareLink::factory(),
            'accessed_at' => fake()->dateTimeBetween('-7 days', 'now'),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }

    /**
     * Set a specific IP address.
     */
    public function fromIp(string $ip): static
    {
        return $this->state(fn (array $attributes) => [
            'ip_address' => $ip,
        ]);
    }

    /**
     * Set the access time to now.
     */
    public function accessedNow(): static
    {
        return $this->state(fn (array $attributes) => [
            'accessed_at' => now(),
        ]);
    }
}
