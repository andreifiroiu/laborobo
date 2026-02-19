<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\GlobalAISettings;
use App\Models\TeamApiKey;
use Illuminate\Support\Facades\Log;
use NeuronAI\Agent;
use NeuronAI\Chat\Messages\UserMessage;
use Throwable;

/**
 * Lightweight service for simple prompt → text completions (no tools needed).
 *
 * Used by TaskBreakdownService, DeliverableGeneratorService, ClientCommsDraftService
 * for LLM-powered generation with graceful fallback.
 */
class LLMService
{
    /**
     * Send a prompt to the LLM and get a text response.
     *
     * @param  string  $systemPrompt  The system prompt / instructions
     * @param  string  $userPrompt  The user message to complete
     * @param  int  $teamId  Team ID for API key and settings resolution
     * @param  int|null  $userId  Optional user ID for private key resolution
     * @return LLMResponse|null Null if no API key or on failure
     */
    public function complete(
        string $systemPrompt,
        string $userPrompt,
        int $teamId,
        ?int $userId = null,
    ): ?LLMResponse {
        $settings = GlobalAISettings::where('team_id', $teamId)->first();

        $providerName = $settings?->default_provider ?? 'anthropic';
        $model = $settings?->default_model ?? 'claude-sonnet-4-20250514';
        $apiKey = TeamApiKey::resolveKey($providerName, $teamId, $userId);

        if ($apiKey === null) {
            return null;
        }

        try {
            $provider = ProviderFactory::create($providerName, $model, $apiKey);

            $agent = Agent::make()
                ->withProvider($provider)
                ->setInstructions($systemPrompt);

            $response = $agent->chat(new UserMessage($userPrompt));

            $usage = $response->getUsage();
            $inputTokens = $usage?->inputTokens ?? 0;
            $outputTokens = $usage?->outputTokens ?? 0;

            return new LLMResponse(
                content: $response->getContent() ?? '',
                tokensUsed: $inputTokens + $outputTokens,
                inputTokens: $inputTokens,
                outputTokens: $outputTokens,
            );
        } catch (Throwable $e) {
            Log::warning('LLM completion failed', [
                'provider' => $providerName,
                'model' => $model,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
