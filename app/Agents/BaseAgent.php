<?php

declare(strict_types=1);

namespace App\Agents;

use App\Contracts\Tools\ToolInterface;
use App\Models\AgentConfiguration;
use App\Models\AIAgent;
use App\Models\GlobalAISettings;
use App\Services\AgentBudgetService;
use App\Services\ToolGateway;
use App\ValueObjects\AgentContext;
use RuntimeException;

/**
 * Base agent class for Laborobo AI agents.
 *
 * This class provides Laborobo-specific behaviors for AI agents, including:
 * - Provider configuration based on GlobalAISettings
 * - System prompt loading from AgentConfiguration
 * - Tool filtering through ToolGateway permissions
 * - Budget checking before each run
 *
 * Designed to integrate with neuron-ai's Agent class when available.
 * Until neuron-ai is installed, this provides a standalone abstraction.
 *
 * When neuron-ai is available, extend NeuronAI\Agent instead of this class
 * and use the traits/interfaces provided here.
 */
abstract class BaseAgent
{
    /**
     * The AI agent model this agent instance represents.
     */
    protected AIAgent $aiAgent;

    /**
     * The configuration for this agent.
     */
    protected AgentConfiguration $configuration;

    /**
     * The global AI settings for the team.
     */
    protected ?GlobalAISettings $globalSettings = null;

    /**
     * The tool gateway for executing tools.
     */
    protected ToolGateway $toolGateway;

    /**
     * The budget service for cost management.
     */
    protected AgentBudgetService $budgetService;

    /**
     * Context for the current agent run.
     */
    protected ?AgentContext $context = null;

    /**
     * Message history for multi-turn conversations.
     *
     * @var array<int, array{role: string, content: string}>
     */
    protected array $messageHistory = [];

    public function __construct(
        AIAgent $aiAgent,
        AgentConfiguration $configuration,
        ToolGateway $toolGateway,
        AgentBudgetService $budgetService,
    ) {
        $this->aiAgent = $aiAgent;
        $this->configuration = $configuration;
        $this->toolGateway = $toolGateway;
        $this->budgetService = $budgetService;

        // Load global settings for the team
        $this->globalSettings = GlobalAISettings::where('team_id', $configuration->team_id)->first();
    }

    /**
     * Get the AI provider configuration.
     *
     * Returns the appropriate provider based on GlobalAISettings.
     * This method is designed to return a neuron-ai AIProviderInterface
     * when the package is available.
     *
     * @return array{provider: string, model: string, api_key: string|null}
     */
    public function provider(): array
    {
        $defaultProvider = $this->globalSettings?->default_provider ?? 'anthropic';
        $defaultModel = $this->globalSettings?->default_model ?? 'claude-3-sonnet-20240229';

        return [
            'provider' => $defaultProvider,
            'model' => $defaultModel,
            'api_key' => $this->getApiKey($defaultProvider),
        ];
    }

    /**
     * Get the system instructions for this agent.
     *
     * Loads from AgentConfiguration or falls back to AIAgent's template instructions.
     */
    public function instructions(): string
    {
        // Check for custom instructions in configuration
        $customInstructions = $this->configuration->custom_instructions ?? null;

        if ($customInstructions !== null && $customInstructions !== '') {
            return $customInstructions;
        }

        // Fall back to template default instructions
        $template = $this->aiAgent->template;

        if ($template !== null && $template->default_instructions !== null) {
            return $template->default_instructions;
        }

        // Final fallback: generate basic instructions from agent info
        return $this->generateDefaultInstructions();
    }

    /**
     * Get the tools available to this agent.
     *
     * Returns tools filtered through ToolGateway permissions based on
     * the agent's configuration.
     *
     * @return array<string, ToolInterface>
     */
    public function tools(): array
    {
        return $this->toolGateway->getAvailableTools($this->configuration);
    }

    /**
     * Check if the agent can run based on budget constraints.
     *
     * @param  float  $estimatedCost  Estimated cost for this run
     */
    public function canRun(float $estimatedCost = 0.01): bool
    {
        if (! $this->configuration->enabled) {
            return false;
        }

        return $this->budgetService->canRun($this->configuration, $estimatedCost);
    }

    /**
     * Set the context for this agent run.
     */
    public function setContext(AgentContext $context): self
    {
        $this->context = $context;

        return $this;
    }

    /**
     * Get the current context.
     */
    public function getContext(): ?AgentContext
    {
        return $this->context;
    }

    /**
     * Add a message to the conversation history.
     *
     * @param  string  $role  The role (user, assistant, system)
     * @param  string  $content  The message content
     */
    public function addMessage(string $role, string $content): self
    {
        $this->messageHistory[] = [
            'role' => $role,
            'content' => $content,
        ];

        return $this;
    }

    /**
     * Get the conversation history.
     *
     * @return array<int, array{role: string, content: string}>
     */
    public function getMessageHistory(): array
    {
        return $this->messageHistory;
    }

    /**
     * Clear the conversation history.
     */
    public function clearHistory(): self
    {
        $this->messageHistory = [];

        return $this;
    }

    /**
     * Build the full system prompt including context.
     */
    public function buildSystemPrompt(): string
    {
        $parts = [$this->instructions()];

        if ($this->context !== null && ! $this->context->isEmpty()) {
            $parts[] = "\n\n## Current Context\n";
            $parts[] = $this->context->toPromptString();
        }

        return implode('', $parts);
    }

    /**
     * Execute a tool through the gateway.
     *
     * @param  string  $toolName  The name of the tool to execute
     * @param  array<string, mixed>  $params  Parameters for the tool
     * @param  float  $estimatedCost  Estimated cost for the execution
     * @return \App\ValueObjects\ToolResult
     */
    public function executeTool(string $toolName, array $params, float $estimatedCost = 0.0): \App\ValueObjects\ToolResult
    {
        return $this->toolGateway->execute(
            $this->aiAgent,
            $this->configuration,
            $toolName,
            $params,
            $estimatedCost
        );
    }

    /**
     * Get the underlying AIAgent model.
     */
    public function getAIAgent(): AIAgent
    {
        return $this->aiAgent;
    }

    /**
     * Get the agent configuration.
     */
    public function getConfiguration(): AgentConfiguration
    {
        return $this->configuration;
    }

    /**
     * Get the API key for a provider from environment.
     */
    protected function getApiKey(string $provider): ?string
    {
        return match ($provider) {
            'anthropic' => config('services.anthropic.api_key'),
            'openai' => config('services.openai.api_key'),
            default => null,
        };
    }

    /**
     * Generate default instructions from agent information.
     */
    protected function generateDefaultInstructions(): string
    {
        $name = $this->aiAgent->name;
        $description = $this->aiAgent->description ?? 'an AI assistant';
        $capabilities = $this->aiAgent->capabilities ?? [];

        $instructions = "You are {$name}, {$description}.";

        if (! empty($capabilities)) {
            $capabilityList = implode(', ', $capabilities);
            $instructions .= " Your capabilities include: {$capabilityList}.";
        }

        $instructions .= ' Always be helpful, accurate, and professional.';

        return $instructions;
    }

    /**
     * Validate that the agent is properly configured before running.
     *
     * @throws RuntimeException If the agent is not properly configured
     */
    protected function validateConfiguration(): void
    {
        if (! $this->configuration->enabled) {
            throw new RuntimeException('Agent is disabled');
        }

        if ($this->globalSettings === null) {
            throw new RuntimeException('Global AI settings not found for team');
        }
    }
}
