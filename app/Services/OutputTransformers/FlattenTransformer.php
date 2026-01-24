<?php

declare(strict_types=1);

namespace App\Services\OutputTransformers;

use App\Contracts\OutputTransformer;

/**
 * Flattens nested arrays into a single-level array with dot notation keys.
 */
final class FlattenTransformer implements OutputTransformer
{
    public function transform(array $output, array $config): array
    {
        $separator = $config['separator'] ?? '.';

        return $this->flatten($output, '', $separator);
    }

    public function getType(): string
    {
        return 'flatten';
    }

    /**
     * Recursively flatten an array.
     *
     * @param  array<string, mixed>  $array
     * @return array<string, mixed>
     */
    private function flatten(array $array, string $prefix, string $separator): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $newKey = $prefix === '' ? (string) $key : $prefix.$separator.$key;

            if (is_array($value) && ! $this->isIndexedArray($value)) {
                $result = array_merge($result, $this->flatten($value, $newKey, $separator));
            } else {
                $result[$newKey] = $value;
            }
        }

        return $result;
    }

    /**
     * Check if an array is indexed (sequential numeric keys).
     *
     * @param  array<mixed>  $array
     */
    private function isIndexedArray(array $array): bool
    {
        if (empty($array)) {
            return false;
        }

        return array_keys($array) === range(0, count($array) - 1);
    }
}
