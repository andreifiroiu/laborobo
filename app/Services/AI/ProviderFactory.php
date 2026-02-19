<?php

declare(strict_types=1);

namespace App\Services\AI;

use InvalidArgumentException;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\Anthropic\Anthropic;
use NeuronAI\Providers\Gemini\Gemini;
use NeuronAI\Providers\OpenAI\OpenAI;

/**
 * Factory for creating NeuronAI provider instances from string configuration.
 */
class ProviderFactory
{
    /**
     * Create an AI provider instance from configuration.
     *
     * @param  string  $provider  Provider name (anthropic, openai, google)
     * @param  string  $model  Model identifier
     * @param  string  $apiKey  API key for the provider
     */
    public static function create(string $provider, string $model, string $apiKey): AIProviderInterface
    {
        return match (strtolower($provider)) {
            'anthropic' => new Anthropic(key: $apiKey, model: $model),
            'openai' => new OpenAI(key: $apiKey, model: $model),
            'google', 'gemini' => new Gemini(key: $apiKey, model: $model),
            default => throw new InvalidArgumentException("Unsupported AI provider: {$provider}"),
        };
    }
}
