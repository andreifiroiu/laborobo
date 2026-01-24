<?php

declare(strict_types=1);

namespace App\Services\OutputTransformers;

use App\Contracts\OutputTransformer;

/**
 * Renames keys in the output array based on a mapping.
 */
final class RenameKeysTransformer implements OutputTransformer
{
    public function transform(array $output, array $config): array
    {
        $mappings = $config['mappings'] ?? [];

        if (empty($mappings)) {
            return $output;
        }

        $result = [];

        foreach ($output as $key => $value) {
            $newKey = $mappings[$key] ?? $key;
            $result[$newKey] = $value;
        }

        return $result;
    }

    public function getType(): string
    {
        return 'rename_keys';
    }
}
