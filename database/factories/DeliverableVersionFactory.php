<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Deliverable;
use App\Models\DeliverableVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliverableVersion>
 */
class DeliverableVersionFactory extends Factory
{
    protected $model = DeliverableVersion::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $extension = fake()->randomElement(['pdf', 'docx', 'png', 'jpg']);
        $fileName = fake()->slug(3) . '.' . $extension;

        return [
            'deliverable_id' => Deliverable::factory(),
            'version_number' => fake()->numberBetween(1, 10),
            'file_url' => '/storage/deliverables/' . fake()->uuid() . '/' . $fileName,
            'file_name' => $fileName,
            'file_size' => fake()->numberBetween(1024, 52428800), // 1KB to 50MB
            'mime_type' => $this->getMimeTypeForExtension($extension),
            'notes' => fake()->optional(0.7)->sentence(),
            'uploaded_by_id' => User::factory(),
        ];
    }

    /**
     * Get MIME type for a given extension.
     */
    private function getMimeTypeForExtension(string $extension): string
    {
        return match ($extension) {
            'pdf' => 'application/pdf',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'webp' => 'image/webp',
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'mov' => 'video/quicktime',
            default => 'application/octet-stream',
        };
    }

    /**
     * Indicate that the version is an image file.
     */
    public function image(): static
    {
        $extension = fake()->randomElement(['png', 'jpg', 'jpeg', 'gif', 'webp']);
        $fileName = fake()->slug(3) . '.' . $extension;

        return $this->state(fn (array $attributes) => [
            'file_url' => '/storage/deliverables/' . fake()->uuid() . '/' . $fileName,
            'file_name' => $fileName,
            'mime_type' => $this->getMimeTypeForExtension($extension),
        ]);
    }

    /**
     * Indicate that the version is a PDF file.
     */
    public function pdf(): static
    {
        $fileName = fake()->slug(3) . '.pdf';

        return $this->state(fn (array $attributes) => [
            'file_url' => '/storage/deliverables/' . fake()->uuid() . '/' . $fileName,
            'file_name' => $fileName,
            'mime_type' => 'application/pdf',
        ]);
    }

    /**
     * Indicate that the version is a video file.
     */
    public function video(): static
    {
        $extension = fake()->randomElement(['mp4', 'webm', 'mov']);
        $fileName = fake()->slug(3) . '.' . $extension;

        return $this->state(fn (array $attributes) => [
            'file_url' => '/storage/deliverables/' . fake()->uuid() . '/' . $fileName,
            'file_name' => $fileName,
            'mime_type' => $this->getMimeTypeForExtension($extension),
            'file_size' => fake()->numberBetween(1048576, 52428800), // 1MB to 50MB for videos
        ]);
    }
}
