<?php

declare(strict_types=1);

namespace App\Services\AI;

/**
 * Value object representing an LLM completion response.
 */
final readonly class LLMResponse
{
    public function __construct(
        public string $content,
        public int $tokensUsed = 0,
        public int $inputTokens = 0,
        public int $outputTokens = 0,
    ) {}

    /**
     * Estimate cost based on token usage.
     *
     * Uses approximate pricing: $3/M input, $15/M output for Claude Sonnet.
     */
    public function estimateCost(): float
    {
        $inputCost = ($this->inputTokens / 1_000_000) * 3.0;
        $outputCost = ($this->outputTokens / 1_000_000) * 15.0;

        return $inputCost + $outputCost;
    }
}
