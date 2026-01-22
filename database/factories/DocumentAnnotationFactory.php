<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CommunicationThread;
use App\Models\Document;
use App\Models\DocumentAnnotation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DocumentAnnotation>
 */
class DocumentAnnotationFactory extends Factory
{
    protected $model = DocumentAnnotation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_id' => Document::factory(),
            'page' => fake()->optional(0.7)->numberBetween(1, 50),
            'x_percent' => fake()->randomFloat(2, 0, 100),
            'y_percent' => fake()->randomFloat(2, 0, 100),
            'communication_thread_id' => CommunicationThread::factory(),
            'created_by_id' => User::factory(),
        ];
    }

    /**
     * Set the annotation for a specific page.
     */
    public function onPage(int $page): static
    {
        return $this->state(fn (array $attributes) => [
            'page' => $page,
        ]);
    }

    /**
     * Set the annotation for an image (no page number).
     */
    public function forImage(): static
    {
        return $this->state(fn (array $attributes) => [
            'page' => null,
        ]);
    }

    /**
     * Set the annotation at a specific position.
     */
    public function atPosition(float $xPercent, float $yPercent): static
    {
        return $this->state(fn (array $attributes) => [
            'x_percent' => $xPercent,
            'y_percent' => $yPercent,
        ]);
    }
}
