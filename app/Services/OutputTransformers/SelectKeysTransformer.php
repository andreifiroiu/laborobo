<?php

declare(strict_types=1);

namespace App\Services\OutputTransformers;

use App\Contracts\OutputTransformer;

/**
 * Selects only specified keys from the output array.
 */
final class SelectKeysTransformer implements OutputTransformer
{
    public function transform(array $output, array $config): array
    {
        $keys = $config['keys'] ?? [];

        if (empty($keys)) {
            return $output;
        }

        return array_intersect_key($output, array_flip($keys));
    }

    public function getType(): string
    {
        return 'select_keys';
    }
}
