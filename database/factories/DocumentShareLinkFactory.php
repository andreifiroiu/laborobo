<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Document;
use App\Models\DocumentShareLink;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DocumentShareLink>
 */
class DocumentShareLinkFactory extends Factory
{
    protected $model = DocumentShareLink::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_id' => Document::factory(),
            'token' => DocumentShareLink::generateToken(),
            'expires_at' => fake()->optional(0.5)->dateTimeBetween('now', '+30 days'),
            'password_hash' => null,
            'allow_download' => fake()->boolean(70),
            'created_by_id' => User::factory(),
        ];
    }

    /**
     * Create an expired share link.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => fake()->dateTimeBetween('-30 days', '-1 day'),
        ]);
    }

    /**
     * Create a permanent (non-expiring) share link.
     */
    public function permanent(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => null,
        ]);
    }

    /**
     * Create a password-protected share link.
     */
    public function withPassword(string $password = 'secret'): static
    {
        return $this->state(fn (array $attributes) => [
            'password_hash' => Hash::make($password),
        ]);
    }

    /**
     * Set download permission.
     */
    public function allowDownload(bool $allow = true): static
    {
        return $this->state(fn (array $attributes) => [
            'allow_download' => $allow,
        ]);
    }

    /**
     * Create a share link expiring in a specific number of days.
     */
    public function expiresInDays(int $days): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->addDays($days),
        ]);
    }
}
