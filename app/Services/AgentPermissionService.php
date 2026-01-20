<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Tools\ToolInterface;
use App\Models\AgentConfiguration;
use App\Models\GlobalAISettings;

/**
 * Service for managing agent permissions.
 *
 * Centralizes permission checking logic for agent tool execution
 * and system-level approval requirements.
 */
class AgentPermissionService
{
    /**
     * Check if an agent configuration has permission to execute a tool.
     *
     * This maps tool categories to the required permission fields on
     * AgentConfiguration and checks if the agent has the necessary permissions.
     */
    public function canExecuteTool(AgentConfiguration $config, ToolInterface $tool): bool
    {
        $category = $tool->category();
        $permissionMapping = $this->getPermissionMapping();

        // If no permission required for this category, allow execution
        if (! isset($permissionMapping[$category])) {
            return true;
        }

        $requiredPermission = $permissionMapping[$category];

        // Check if the agent has the required permission
        return $config->hasPermission($requiredPermission);
    }

    /**
     * Check if an action type requires human approval based on system settings.
     *
     * This checks the GlobalAISettings for system-level approval requirements
     * that override agent-level permissions.
     */
    public function requiresHumanApproval(string $actionType, GlobalAISettings $settings): bool
    {
        return $settings->requiresApprovalFor($actionType);
    }

    /**
     * Get all permissions that an agent configuration has enabled.
     *
     * @return array<string>
     */
    public function getEnabledPermissions(AgentConfiguration $config): array
    {
        $permissionMapping = $this->getPermissionMapping();
        $enabled = [];

        foreach ($permissionMapping as $category => $permission) {
            if ($config->hasPermission($permission)) {
                $enabled[] = $permission;
            }
        }

        return $enabled;
    }

    /**
     * Get the categories that an agent has access to.
     *
     * @return array<string>
     */
    public function getAccessibleCategories(AgentConfiguration $config): array
    {
        $permissionMapping = $this->getPermissionMapping();
        $accessible = [];

        foreach ($permissionMapping as $category => $permission) {
            if ($config->hasPermission($permission)) {
                $accessible[] = $category;
            }
        }

        // Add 'general' category which doesn't require specific permissions
        $accessible[] = 'general';

        return $accessible;
    }

    /**
     * Check if a specific permission is enabled for an agent.
     */
    public function hasPermission(AgentConfiguration $config, string $permission): bool
    {
        return $config->hasPermission($permission);
    }

    /**
     * Get the permission required for a tool category.
     */
    public function getPermissionForCategory(string $category): ?string
    {
        $mapping = $this->getPermissionMapping();

        return $mapping[$category] ?? null;
    }

    /**
     * Get the mapping of tool categories to permission fields.
     *
     * @return array<string, string>
     */
    private function getPermissionMapping(): array
    {
        return config('agent-permissions.category_permissions', [
            'tasks' => 'can_modify_tasks',
            'work_orders' => 'can_create_work_orders',
            'client_data' => 'can_access_client_data',
            'email' => 'can_send_emails',
            'deliverables' => 'can_modify_deliverables',
            'financial' => 'can_access_financial_data',
            'playbooks' => 'can_modify_playbooks',
        ]);
    }
}
