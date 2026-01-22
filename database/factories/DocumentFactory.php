<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DocumentType;
use App\Models\Document;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Document>
 */
class DocumentFactory extends Factory
{
    protected $model = Document::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'uploaded_by_id' => User::factory(),
            'documentable_type' => Project::class,
            'documentable_id' => Project::factory(),
            'folder_id' => null,
            'name' => fake()->sentence(3) . '.' . fake()->randomElement(['pdf', 'docx', 'xlsx', 'png', 'jpg']),
            'type' => fake()->randomElement(DocumentType::cases()),
            'file_url' => 'documents/' . fake()->uuid() . '/' . fake()->slug() . '.pdf',
            'file_size' => (string) fake()->numberBetween(1024, 10485760),
        ];
    }

    /**
     * Set the document to be in a specific folder.
     */
    public function inFolder(int $folderId): static
    {
        return $this->state(fn (array $attributes) => [
            'folder_id' => $folderId,
        ]);
    }

    /**
     * Set the document type.
     */
    public function ofType(DocumentType $type): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
        ]);
    }

    /**
     * Set as a PDF document.
     */
    public function pdf(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => fake()->sentence(3) . '.pdf',
            'file_url' => 'documents/' . fake()->uuid() . '/document.pdf',
        ]);
    }

    /**
     * Set as an image document.
     */
    public function image(): static
    {
        $extension = fake()->randomElement(['png', 'jpg', 'jpeg', 'gif']);

        return $this->state(fn (array $attributes) => [
            'name' => fake()->sentence(3) . '.' . $extension,
            'file_url' => 'documents/' . fake()->uuid() . '/image.' . $extension,
        ]);
    }
}
