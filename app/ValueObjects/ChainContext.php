<?php

declare(strict_types=1);

namespace App\ValueObjects;

/**
 * Immutable value object representing the accumulated context for a chain execution.
 *
 * Contains step outputs, accumulated context data, and metadata for chain-level
 * context passing between agents. Supports filtering via include/exclude arrays.
 */
final readonly class ChainContext
{
    /**
     * Average characters per token for estimation purposes.
     */
    private const CHARS_PER_TOKEN = 4;

    /**
     * @param  array<int, array{output: array<string, mixed>, agent_id: int|null, completed_at: string|null}>  $stepOutputs  Outputs from each completed step indexed by step index
     * @param  array<string, mixed>  $accumulatedContext  Aggregated context data across all steps
     * @param  array<string, mixed>  $metadata  Additional metadata about the chain context
     */
    public function __construct(
        public array $stepOutputs = [],
        public array $accumulatedContext = [],
        public array $metadata = [],
    ) {}

    /**
     * Create a ChainContext from an array (typically from JSON column).
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $stepOutputs = [];

        // Convert steps data to indexed step outputs
        if (isset($data['steps']) && is_array($data['steps'])) {
            foreach ($data['steps'] as $index => $stepData) {
                $stepOutputs[(int) $index] = [
                    'output' => $stepData['output'] ?? [],
                    'agent_id' => $stepData['agent_id'] ?? null,
                    'completed_at' => $stepData['completed_at'] ?? null,
                ];
            }
        }

        return new self(
            stepOutputs: $stepOutputs,
            accumulatedContext: $data['accumulated_context'] ?? [],
            metadata: $data['metadata'] ?? [],
        );
    }

    /**
     * Create a new context with an additional step output.
     *
     * @param  int  $stepIndex  The step index
     * @param  array<string, mixed>  $output  The output from the step
     * @param  int|null  $agentId  The agent ID that produced the output
     */
    public function withStepOutput(int $stepIndex, array $output, ?int $agentId = null): self
    {
        $newStepOutputs = $this->stepOutputs;
        $newStepOutputs[$stepIndex] = [
            'output' => $output,
            'agent_id' => $agentId,
            'completed_at' => now()->toIso8601String(),
        ];

        // Merge the output into accumulated context
        $newAccumulatedContext = array_merge(
            $this->accumulatedContext,
            ["step_{$stepIndex}" => $output]
        );

        return new self(
            stepOutputs: $newStepOutputs,
            accumulatedContext: $newAccumulatedContext,
            metadata: $this->metadata,
        );
    }

    /**
     * Get the output for a specific step.
     *
     * @param  int  $stepIndex  The step index to retrieve output for
     * @return array<string, mixed>|null The step output or null if not found
     */
    public function getOutputForStep(int $stepIndex): ?array
    {
        return $this->stepOutputs[$stepIndex]['output'] ?? null;
    }

    /**
     * Get all step outputs.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAllOutputs(): array
    {
        $outputs = [];
        foreach ($this->stepOutputs as $index => $data) {
            $outputs[$index] = $data['output'];
        }

        return $outputs;
    }

    /**
     * Apply context filtering based on include/exclude rules.
     *
     * @param  array<string>  $include  Keys to include (if empty, include all)
     * @param  array<string>  $exclude  Keys to exclude
     */
    public function filter(array $include = [], array $exclude = []): self
    {
        $filteredOutputs = [];

        foreach ($this->stepOutputs as $index => $stepData) {
            $output = $stepData['output'];

            // Apply include filter
            if (! empty($include)) {
                $output = $this->filterByKeys($output, $include, true);
            }

            // Apply exclude filter
            if (! empty($exclude)) {
                $output = $this->filterByKeys($output, $exclude, false);
            }

            $filteredOutputs[$index] = [
                'output' => $output,
                'agent_id' => $stepData['agent_id'],
                'completed_at' => $stepData['completed_at'],
            ];
        }

        return new self(
            stepOutputs: $filteredOutputs,
            accumulatedContext: $this->accumulatedContext,
            metadata: array_merge($this->metadata, ['filtered' => true]),
        );
    }

    /**
     * Convert the context to a formatted string suitable for LLM consumption.
     */
    public function toPromptString(): string
    {
        $parts = [];

        if (! empty($this->stepOutputs)) {
            $parts[] = $this->formatStepOutputsSection();
        }

        if (! empty($this->accumulatedContext)) {
            $parts[] = $this->formatSection('Accumulated Context', $this->accumulatedContext);
        }

        if (! empty($this->metadata)) {
            $parts[] = $this->formatSection('Chain Metadata', $this->metadata);
        }

        return implode("\n\n", $parts);
    }

    /**
     * Get an estimate of the token count for this context.
     */
    public function getTokenEstimate(): int
    {
        $promptString = $this->toPromptString();
        $charCount = strlen($promptString);

        return (int) ceil($charCount / self::CHARS_PER_TOKEN);
    }

    /**
     * Check if the context is empty (no step outputs).
     */
    public function isEmpty(): bool
    {
        return empty($this->stepOutputs) && empty($this->accumulatedContext);
    }

    /**
     * Get the number of completed steps.
     */
    public function getCompletedStepCount(): int
    {
        return count($this->stepOutputs);
    }

    /**
     * Create a new context with merged metadata.
     *
     * @param  array<string, mixed>  $additionalMetadata
     */
    public function withMetadata(array $additionalMetadata): self
    {
        return new self(
            stepOutputs: $this->stepOutputs,
            accumulatedContext: $this->accumulatedContext,
            metadata: array_merge($this->metadata, $additionalMetadata),
        );
    }

    /**
     * Create a new context with pause reason.
     */
    public function withPauseReason(string $reason): self
    {
        return $this->withMetadata(['pause_reason' => $reason]);
    }

    /**
     * Create a new context with resume data.
     *
     * @param  array<string, mixed>  $resumeData
     */
    public function withResumeData(array $resumeData): self
    {
        return $this->withMetadata(['resume_data' => $resumeData]);
    }

    /**
     * Convert the context to an array for storage.
     *
     * @return array{steps: array<int, array{output: array<string, mixed>, agent_id: int|null, completed_at: string|null}>, accumulated_context: array<string, mixed>, metadata: array<string, mixed>, pause_reason?: string, resume_data?: array<string, mixed>}
     */
    public function toArray(): array
    {
        $result = [
            'steps' => $this->stepOutputs,
            'accumulated_context' => $this->accumulatedContext,
            'metadata' => $this->metadata,
        ];

        // Include pause_reason at top level for easy access
        if (isset($this->metadata['pause_reason'])) {
            $result['pause_reason'] = $this->metadata['pause_reason'];
        }

        // Include resume_data at top level for easy access
        if (isset($this->metadata['resume_data'])) {
            $result['resume_data'] = $this->metadata['resume_data'];
        }

        return $result;
    }

    /**
     * Evaluate a condition against the chain context.
     *
     * Supports simple dot-notation conditions like:
     * - steps.0.output.recommendation == "approved"
     * - steps.1.output.score > 80
     *
     * @param  string  $condition  The condition to evaluate
     * @return bool True if the condition is met
     */
    public function evaluateCondition(string $condition): bool
    {
        // Parse the condition into path, operator, and value
        $operators = ['==', '!=', '>', '<', '>=', '<=', 'contains', 'not_contains'];
        $operator = null;
        $parts = [];

        foreach ($operators as $op) {
            if (str_contains($condition, " {$op} ")) {
                $parts = explode(" {$op} ", $condition, 2);
                $operator = $op;
                break;
            }
        }

        if ($operator === null || count($parts) !== 2) {
            return false;
        }

        $path = trim($parts[0]);
        $expectedValue = trim($parts[1], '" \'');

        // Get the actual value from the context
        $actualValue = $this->getValueByPath($path);

        if ($actualValue === null) {
            return false;
        }

        return $this->compareValues($actualValue, $operator, $expectedValue);
    }

    /**
     * Get a value from the context by dot-notation path.
     *
     * @param  string  $path  The dot-notation path (e.g., "steps.0.output.recommendation")
     * @return mixed The value or null if not found
     */
    private function getValueByPath(string $path): mixed
    {
        $segments = explode('.', $path);
        $data = $this->toArray();

        foreach ($segments as $segment) {
            if (is_array($data) && array_key_exists($segment, $data)) {
                $data = $data[$segment];
            } elseif (is_array($data) && is_numeric($segment) && array_key_exists((int) $segment, $data)) {
                $data = $data[(int) $segment];
            } else {
                return null;
            }
        }

        return $data;
    }

    /**
     * Compare two values using the given operator.
     */
    private function compareValues(mixed $actual, string $operator, string $expected): bool
    {
        return match ($operator) {
            '==' => (string) $actual === $expected,
            '!=' => (string) $actual !== $expected,
            '>' => is_numeric($actual) && (float) $actual > (float) $expected,
            '<' => is_numeric($actual) && (float) $actual < (float) $expected,
            '>=' => is_numeric($actual) && (float) $actual >= (float) $expected,
            '<=' => is_numeric($actual) && (float) $actual <= (float) $expected,
            'contains' => is_string($actual) && str_contains($actual, $expected),
            'not_contains' => is_string($actual) && ! str_contains($actual, $expected),
            default => false,
        };
    }

    /**
     * Filter an array by keys.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string>  $keys
     * @param  bool  $include  True to include only these keys, false to exclude them
     * @return array<string, mixed>
     */
    private function filterByKeys(array $data, array $keys, bool $include): array
    {
        if ($include) {
            return array_intersect_key($data, array_flip($keys));
        }

        return array_diff_key($data, array_flip($keys));
    }

    /**
     * Format the step outputs section for the prompt string.
     */
    private function formatStepOutputsSection(): string
    {
        $lines = ['## Previous Step Outputs'];

        foreach ($this->stepOutputs as $index => $stepData) {
            $lines[] = "### Step {$index}";
            if (! empty($stepData['output'])) {
                foreach ($stepData['output'] as $key => $value) {
                    $formattedKey = $this->formatKey($key);
                    $formattedValue = $this->formatValue($value);
                    $lines[] = "- **{$formattedKey}**: {$formattedValue}";
                }
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Format a section for the prompt string.
     *
     * @param  array<string, mixed>  $data
     */
    private function formatSection(string $title, array $data): string
    {
        $lines = ["## {$title}"];

        foreach ($data as $key => $value) {
            $formattedKey = $this->formatKey($key);
            $formattedValue = $this->formatValue($value);
            $lines[] = "- **{$formattedKey}**: {$formattedValue}";
        }

        return implode("\n", $lines);
    }

    /**
     * Format a key for display.
     */
    private function formatKey(string $key): string
    {
        return ucwords(str_replace('_', ' ', $key));
    }

    /**
     * Format a value for display.
     */
    private function formatValue(mixed $value): string
    {
        if (is_array($value)) {
            return json_encode($value, JSON_PRETTY_PRINT) ?: '[]';
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if ($value === null) {
            return 'Not specified';
        }

        return (string) $value;
    }
}
