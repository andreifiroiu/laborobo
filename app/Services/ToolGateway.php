<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Tools\ToolInterface;
use App\Models\AgentActivityLog;
use App\Models\AgentConfiguration;
use App\Models\AIAgent;
use App\Models\GlobalAISettings;
use App\ValueObjects\ToolResult;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Gateway service for all agent tool execution.
 *
 * This is the single entry point for tool execution. It enforces permission
 * checks before delegating to tool implementations and logs all execution
 * attempts to AgentActivityLog.
 *
 * Tools must NOT self-check permissions; the gateway handles all authorization.
 */
class ToolGateway
{
    public function __construct(
        private readonly ToolRegistry $registry,
        private readonly ?AgentPermissionService $permissionService = null,
        private readonly ?AgentBudgetService $budgetService = null,
    ) {}

    /**
     * Execute a tool with permission enforcement and logging.
     *
     * @param  array<string, mixed>  $params  The parameters for tool execution
     * @param  float  $estimatedCost  The estimated cost for budget validation
     */
    public function execute(
        AIAgent $agent,
        AgentConfiguration $config,
        string $toolName,
        array $params,
        float $estimatedCost = 0.0,
    ): ToolResult {
        $startTime = hrtime(true);

        // Check if tool exists
        $tool = $this->registry->get($toolName);

        if ($tool === null) {
            $result = ToolResult::failure("Tool '{$toolName}' not found");
            $this->logExecution($agent, $config, $toolName, $params, $result);

            return $result;
        }

        // Check permissions via AgentPermissionService if available
        if (! $this->checkPermissions($config, $tool)) {
            $result = ToolResult::denied(
                "Permission denied: Agent does not have required permissions for tool '{$toolName}'"
            );
            $this->logExecution($agent, $config, $toolName, $params, $result);

            return $result;
        }

        // Check budget via AgentBudgetService if available
        if (! $this->checkBudget($config, $estimatedCost)) {
            $result = ToolResult::denied(
                "Budget exceeded: Agent does not have sufficient budget to execute tool '{$toolName}'"
            );
            $this->logExecution($agent, $config, $toolName, $params, $result);

            return $result;
        }

        // Check if human approval is required for the tool's category
        $approvalRequired = $this->checkApprovalRequired($config, $tool);
        if ($approvalRequired !== null) {
            $result = ToolResult::denied(
                "Approval required: {$approvalRequired}"
            );
            $this->logExecution($agent, $config, $toolName, $params, $result);

            return $result;
        }

        // Execute the tool
        try {
            $data = $tool->execute($params);
            $executionTimeMs = $this->calculateExecutionTime($startTime);
            $result = ToolResult::success($data, $executionTimeMs);

            // Deduct cost after successful execution
            $this->deductCost($config, $estimatedCost);
        } catch (Throwable $e) {
            $executionTimeMs = $this->calculateExecutionTime($startTime);
            $result = ToolResult::failure($e->getMessage(), $executionTimeMs);

            Log::error('Tool execution failed', [
                'tool' => $toolName,
                'agent_id' => $agent->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        $this->logExecution($agent, $config, $toolName, $params, $result);

        return $result;
    }

    /**
     * Check if the agent configuration has permission to use the tool.
     */
    public function hasPermission(AgentConfiguration $config, ToolInterface|string $tool): bool
    {
        $toolInstance = $tool instanceof ToolInterface ? $tool : $this->registry->get($tool);

        if ($toolInstance === null) {
            return false;
        }

        return $this->checkPermissions($config, $toolInstance);
    }

    /**
     * Get all tools available to an agent based on its configuration.
     *
     * @return array<string, ToolInterface>
     */
    public function getAvailableTools(AgentConfiguration $config): array
    {
        $availableTools = [];

        foreach ($this->registry->all() as $name => $tool) {
            if ($this->hasPermission($config, $tool)) {
                $availableTools[$name] = $tool;
            }
        }

        return $availableTools;
    }

    /**
     * Check permissions using the permission service or fallback to direct check.
     */
    private function checkPermissions(AgentConfiguration $config, ToolInterface $tool): bool
    {
        // Use AgentPermissionService if available
        if ($this->permissionService !== null) {
            return $this->permissionService->canExecuteTool($config, $tool);
        }

        // Fallback to direct permission check via registry
        $toolName = $tool->name();
        $requiredPermissions = $this->registry->getRequiredPermissions($toolName);

        if (empty($requiredPermissions)) {
            return true;
        }

        foreach ($requiredPermissions as $permission) {
            if (! $config->hasPermission($permission)) {
                return false;
            }
        }

        // Check tool-specific permissions if configured
        if ($config->tool_permissions !== null && isset($config->tool_permissions[$toolName])) {
            return (bool) $config->tool_permissions[$toolName];
        }

        return true;
    }

    /**
     * Check budget using the budget service if available.
     */
    private function checkBudget(AgentConfiguration $config, float $estimatedCost): bool
    {
        if ($this->budgetService === null || $estimatedCost <= 0) {
            return true;
        }

        return $this->budgetService->canRun($config, $estimatedCost);
    }

    /**
     * Deduct cost using the budget service if available.
     */
    private function deductCost(AgentConfiguration $config, float $cost): void
    {
        if ($this->budgetService === null || $cost <= 0) {
            return;
        }

        $this->budgetService->deductCost($config, $cost);
    }

    /**
     * Check if human approval is required for a tool's category.
     *
     * Returns null if no approval needed, or a message describing why approval is needed.
     */
    private function checkApprovalRequired(AgentConfiguration $config, ToolInterface $tool): ?string
    {
        if ($this->permissionService === null) {
            return null;
        }

        $categoryApprovalTypes = config('agent-permissions.category_approval_types', []);
        $category = $tool->category();

        if (! isset($categoryApprovalTypes[$category])) {
            return null;
        }

        $actionType = $categoryApprovalTypes[$category];

        // Get GlobalAISettings for the team
        $settings = GlobalAISettings::where('team_id', $config->team_id)->first();

        if ($settings === null) {
            return null;
        }

        if ($this->permissionService->requiresHumanApproval($actionType, $settings)) {
            return "Human approval required for {$actionType} actions";
        }

        return null;
    }

    /**
     * Log tool execution to AgentActivityLog.
     *
     * @param  array<string, mixed>  $params
     */
    private function logExecution(
        AIAgent $agent,
        AgentConfiguration $config,
        string $toolName,
        array $params,
        ToolResult $result,
    ): void {
        try {
            AgentActivityLog::create([
                'team_id' => $config->team_id,
                'ai_agent_id' => $agent->id,
                'run_type' => 'tool_execution',
                'input' => json_encode(['tool' => $toolName, 'params' => $params]),
                'output' => $result->success ? json_encode($result->data) : null,
                'error' => $result->error,
                'tool_calls' => [
                    [
                        'tool' => $toolName,
                        'params' => $params,
                        'result' => $result->toArray(),
                        'duration_ms' => $result->executionTimeMs,
                        'status' => $result->status,
                    ],
                ],
                'duration_ms' => $result->executionTimeMs,
            ]);
        } catch (Throwable $e) {
            // Log the logging failure but don't fail the tool execution
            Log::error('Failed to log tool execution', [
                'tool' => $toolName,
                'agent_id' => $agent->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Calculate execution time in milliseconds.
     */
    private function calculateExecutionTime(int $startTime): int
    {
        return (int) ((hrtime(true) - $startTime) / 1_000_000);
    }

    /**
     * Get the underlying tool registry.
     */
    public function getRegistry(): ToolRegistry
    {
        return $this->registry;
    }
}
