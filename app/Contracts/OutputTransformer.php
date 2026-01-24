<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Interface for transforming agent output between chain steps.
 *
 * Output transformers prepare one agent's output format for the next agent's input,
 * enabling flexible data transformation within agent chains.
 */
interface OutputTransformer
{
    /**
     * Transform the output array using the provided configuration.
     *
     * @param  array<string, mixed>  $output  The output to transform
     * @param  array<string, mixed>  $config  The transformation configuration
     * @return array<string, mixed> The transformed output
     */
    public function transform(array $output, array $config): array;

    /**
     * Get the transformer type identifier.
     */
    public function getType(): string;
}
