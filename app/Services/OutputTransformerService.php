<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\OutputTransformer;
use App\Services\OutputTransformers\FlattenTransformer;
use App\Services\OutputTransformers\RenameKeysTransformer;
use App\Services\OutputTransformers\SelectKeysTransformer;
use App\Services\OutputTransformers\SummarizeTransformer;
use InvalidArgumentException;

/**
 * Service for transforming agent outputs between chain steps.
 *
 * Provides a unified interface for applying various output transformations
 * that prepare one agent's output for the next agent's input.
 */
class OutputTransformerService
{
    /**
     * @var array<string, OutputTransformer>
     */
    private array $transformers = [];

    public function __construct()
    {
        $this->registerBuiltInTransformers();
    }

    /**
     * Transform output using the specified configuration.
     *
     * @param  array<string, mixed>  $output  The output to transform
     * @param  array<string, mixed>  $config  The transformation configuration
     * @return array<string, mixed> The transformed output
     *
     * @throws InvalidArgumentException If the transformer type is unknown
     */
    public function transform(array $output, array $config): array
    {
        $type = $config['type'] ?? null;

        if ($type === null) {
            return $output;
        }

        if (! isset($this->transformers[$type])) {
            throw new InvalidArgumentException("Unknown transformer type: {$type}");
        }

        return $this->transformers[$type]->transform($output, $config);
    }

    /**
     * Apply multiple transformations in sequence.
     *
     * @param  array<string, mixed>  $output  The output to transform
     * @param  array<int, array<string, mixed>>  $transformerConfigs  Array of transformation configurations
     * @return array<string, mixed> The transformed output
     */
    public function applyTransformers(array $output, array $transformerConfigs): array
    {
        $result = $output;

        foreach ($transformerConfigs as $config) {
            $result = $this->transform($result, $config);
        }

        return $result;
    }

    /**
     * Register a custom transformer.
     */
    public function registerTransformer(OutputTransformer $transformer): void
    {
        $this->transformers[$transformer->getType()] = $transformer;
    }

    /**
     * Check if a transformer type is registered.
     */
    public function hasTransformer(string $type): bool
    {
        return isset($this->transformers[$type]);
    }

    /**
     * Get all registered transformer types.
     *
     * @return array<string>
     */
    public function getRegisteredTypes(): array
    {
        return array_keys($this->transformers);
    }

    /**
     * Register the built-in transformers.
     */
    private function registerBuiltInTransformers(): void
    {
        $this->registerTransformer(new FlattenTransformer);
        $this->registerTransformer(new SelectKeysTransformer);
        $this->registerTransformer(new RenameKeysTransformer);
        $this->registerTransformer(new SummarizeTransformer);
    }
}
