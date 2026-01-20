<?php

declare(strict_types=1);

namespace App\ValueObjects;

/**
 * Immutable value object representing the assembled context for an agent run.
 *
 * Contains project-level, client-level, and org-level context data along
 * with metadata about the entity being operated on.
 */
final readonly class AgentContext
{
    /**
     * Average characters per token for estimation purposes.
     * This is a rough approximation; actual tokenization varies by model.
     */
    private const CHARS_PER_TOKEN = 4;

    /**
     * @param  array<string, mixed>  $projectContext  Context related to the current project
     * @param  array<string, mixed>  $clientContext  Context related to the client/party
     * @param  array<string, mixed>  $orgContext  Context related to the organization/team
     * @param  array<string, mixed>  $metadata  Additional metadata about the context
     */
    public function __construct(
        public array $projectContext = [],
        public array $clientContext = [],
        public array $orgContext = [],
        public array $metadata = [],
    ) {}

    /**
     * Convert the context to a formatted string suitable for LLM consumption.
     */
    public function toPromptString(): string
    {
        $parts = [];

        if (! empty($this->orgContext)) {
            $parts[] = $this->formatSection('Organization Context', $this->orgContext);
        }

        if (! empty($this->clientContext)) {
            $parts[] = $this->formatSection('Client Context', $this->clientContext);
        }

        if (! empty($this->projectContext)) {
            $parts[] = $this->formatSection('Project Context', $this->projectContext);
        }

        if (! empty($this->metadata)) {
            $parts[] = $this->formatSection('Context Metadata', $this->metadata);
        }

        return implode("\n\n", $parts);
    }

    /**
     * Get an estimate of the token count for this context.
     *
     * Uses a character-based approximation since actual tokenization
     * depends on the specific model being used.
     */
    public function getTokenEstimate(): int
    {
        $promptString = $this->toPromptString();
        $charCount = strlen($promptString);

        return (int) ceil($charCount / self::CHARS_PER_TOKEN);
    }

    /**
     * Check if the context is empty (no data in any scope).
     */
    public function isEmpty(): bool
    {
        return empty($this->projectContext)
            && empty($this->clientContext)
            && empty($this->orgContext)
            && empty($this->metadata);
    }

    /**
     * Create a new context with merged project data.
     *
     * @param  array<string, mixed>  $additionalProjectContext
     */
    public function withProjectContext(array $additionalProjectContext): self
    {
        return new self(
            projectContext: array_merge($this->projectContext, $additionalProjectContext),
            clientContext: $this->clientContext,
            orgContext: $this->orgContext,
            metadata: $this->metadata,
        );
    }

    /**
     * Create a new context with merged client data.
     *
     * @param  array<string, mixed>  $additionalClientContext
     */
    public function withClientContext(array $additionalClientContext): self
    {
        return new self(
            projectContext: $this->projectContext,
            clientContext: array_merge($this->clientContext, $additionalClientContext),
            orgContext: $this->orgContext,
            metadata: $this->metadata,
        );
    }

    /**
     * Create a new context with merged org data.
     *
     * @param  array<string, mixed>  $additionalOrgContext
     */
    public function withOrgContext(array $additionalOrgContext): self
    {
        return new self(
            projectContext: $this->projectContext,
            clientContext: $this->clientContext,
            orgContext: array_merge($this->orgContext, $additionalOrgContext),
            metadata: $this->metadata,
        );
    }

    /**
     * Create a new context with merged metadata.
     *
     * @param  array<string, mixed>  $additionalMetadata
     */
    public function withMetadata(array $additionalMetadata): self
    {
        return new self(
            projectContext: $this->projectContext,
            clientContext: $this->clientContext,
            orgContext: $this->orgContext,
            metadata: array_merge($this->metadata, $additionalMetadata),
        );
    }

    /**
     * Convert the context to an array for logging/serialization.
     *
     * @return array{project_context: array<string, mixed>, client_context: array<string, mixed>, org_context: array<string, mixed>, metadata: array<string, mixed>, token_estimate: int}
     */
    public function toArray(): array
    {
        return [
            'project_context' => $this->projectContext,
            'client_context' => $this->clientContext,
            'org_context' => $this->orgContext,
            'metadata' => $this->metadata,
            'token_estimate' => $this->getTokenEstimate(),
        ];
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
     * Format a key for display (convert snake_case to Title Case).
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
            if ($this->isAssociativeArray($value)) {
                return json_encode($value, JSON_PRETTY_PRINT);
            }

            return implode(', ', array_map(
                fn ($item) => is_scalar($item) ? (string) $item : json_encode($item),
                $value
            ));
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if ($value === null) {
            return 'Not specified';
        }

        return (string) $value;
    }

    /**
     * Check if an array is associative (has string keys).
     *
     * @param  array<mixed>  $array
     */
    private function isAssociativeArray(array $array): bool
    {
        if (empty($array)) {
            return false;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }
}
