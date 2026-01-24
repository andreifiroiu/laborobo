<?php

declare(strict_types=1);

namespace App\Services\OutputTransformers;

use App\Contracts\OutputTransformer;
use Illuminate\Support\Arr;

/**
 * Summarizes data by applying aggregate functions (count, sum, avg, etc.).
 */
final class SummarizeTransformer implements OutputTransformer
{
    public function transform(array $output, array $config): array
    {
        $fields = $config['fields'] ?? [];

        if (empty($fields)) {
            return $output;
        }

        $result = $output;

        foreach ($fields as $newKey => $expression) {
            $result[$newKey] = $this->evaluateExpression($output, $expression);
        }

        return $result;
    }

    public function getType(): string
    {
        return 'summarize';
    }

    /**
     * Evaluate a summarization expression.
     *
     * Supported formats:
     * - count:path - Count elements at path
     * - sum:path - Sum values at path (supports wildcards like 'items.*.amount')
     * - avg:path - Average values at path
     * - min:path - Minimum value at path
     * - max:path - Maximum value at path
     */
    private function evaluateExpression(array $data, string $expression): mixed
    {
        [$function, $path] = explode(':', $expression, 2);

        $values = $this->getValuesAtPath($data, $path);

        return match ($function) {
            'count' => count($values),
            'sum' => array_sum(array_map(fn ($v) => is_numeric($v) ? (float) $v : 0, $values)),
            'avg' => count($values) > 0 ? array_sum(array_map(fn ($v) => is_numeric($v) ? (float) $v : 0, $values)) / count($values) : 0,
            'min' => count($values) > 0 ? min(array_map(fn ($v) => is_numeric($v) ? (float) $v : PHP_FLOAT_MAX, $values)) : null,
            'max' => count($values) > 0 ? max(array_map(fn ($v) => is_numeric($v) ? (float) $v : PHP_FLOAT_MIN, $values)) : null,
            default => null,
        };
    }

    /**
     * Get values at a path, supporting wildcards.
     *
     * @param  array<string, mixed>  $data
     * @return array<mixed>
     */
    private function getValuesAtPath(array $data, string $path): array
    {
        // Handle wildcard paths like 'items.*.amount'
        if (str_contains($path, '*')) {
            return $this->getWildcardValues($data, $path);
        }

        $value = Arr::get($data, $path);

        if (is_array($value)) {
            return $value;
        }

        return $value !== null ? [$value] : [];
    }

    /**
     * Get values using a wildcard path.
     *
     * @param  array<string, mixed>  $data
     * @return array<mixed>
     */
    private function getWildcardValues(array $data, string $path): array
    {
        $segments = explode('.', $path);
        $values = [$data];

        foreach ($segments as $segment) {
            $newValues = [];

            foreach ($values as $current) {
                if (! is_array($current)) {
                    continue;
                }

                if ($segment === '*') {
                    foreach ($current as $item) {
                        $newValues[] = $item;
                    }
                } else {
                    if (array_key_exists($segment, $current)) {
                        $newValues[] = $current[$segment];
                    }
                }
            }

            $values = $newValues;
        }

        return $values;
    }
}
