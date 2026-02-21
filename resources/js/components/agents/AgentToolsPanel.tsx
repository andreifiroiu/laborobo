import { Switch } from '@/components/ui/switch';
import { Badge } from '@/components/ui/badge';
import { Wrench, Lock, Info } from 'lucide-react';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { cn } from '@/lib/utils';

interface AgentPermissions {
    canCreateWorkOrders: boolean;
    canModifyTasks: boolean;
    canAccessClientData: boolean;
    canSendEmails: boolean;
    canModifyDeliverables: boolean;
    canAccessFinancialData: boolean;
    canModifyPlaybooks: boolean;
    canAccessDocuments: boolean;
}

interface AgentTool {
    name: string;
    description: string;
    category: string;
    requiredPermissions: string[];
    enabled: boolean;
}

interface AgentToolsPanelProps {
    tools: AgentTool[];
    permissions: AgentPermissions;
    onChange: (toolName: string, enabled: boolean) => void;
    readOnly?: boolean;
}

// Map permission keys to human-readable labels
const permissionLabels: Record<string, string> = {
    can_create_work_orders: 'Create Work Orders',
    can_modify_tasks: 'Modify Tasks',
    can_access_client_data: 'Access Client Data',
    can_send_emails: 'Send Emails',
    can_modify_deliverables: 'Modify Deliverables',
    can_access_financial_data: 'Access Financial Data',
    can_modify_playbooks: 'Modify Playbooks',
    can_access_documents: 'Access Documents',
};

// Map category names to display names
const categoryLabels: Record<string, string> = {
    tasks: 'Tasks',
    work_orders: 'Work Orders',
    client_data: 'Client Data',
    email: 'Email',
    deliverables: 'Deliverables',
    financial: 'Financial',
    playbooks: 'Playbooks',
    documents: 'Documents',
    general: 'General',
};

// Map permission snake_case to camelCase
function permissionKeyToCamelCase(key: string): keyof AgentPermissions | null {
    const mapping: Record<string, keyof AgentPermissions> = {
        can_create_work_orders: 'canCreateWorkOrders',
        can_modify_tasks: 'canModifyTasks',
        can_access_client_data: 'canAccessClientData',
        can_send_emails: 'canSendEmails',
        can_modify_deliverables: 'canModifyDeliverables',
        can_access_financial_data: 'canAccessFinancialData',
        can_modify_playbooks: 'canModifyPlaybooks',
        can_access_documents: 'canAccessDocuments',
    };
    return mapping[key] || null;
}

function hasRequiredPermissions(
    tool: AgentTool,
    permissions: AgentPermissions
): boolean {
    if (tool.requiredPermissions.length === 0) {
        return true;
    }

    return tool.requiredPermissions.every((permKey) => {
        const camelKey = permissionKeyToCamelCase(permKey);
        return camelKey ? permissions[camelKey] : false;
    });
}

function getMissingPermissions(
    tool: AgentTool,
    permissions: AgentPermissions
): string[] {
    return tool.requiredPermissions.filter((permKey) => {
        const camelKey = permissionKeyToCamelCase(permKey);
        return camelKey ? !permissions[camelKey] : true;
    });
}

// Group tools by category
function groupToolsByCategory(tools: AgentTool[]): Record<string, AgentTool[]> {
    return tools.reduce((groups, tool) => {
        const category = tool.category || 'general';
        if (!groups[category]) {
            groups[category] = [];
        }
        groups[category].push(tool);
        return groups;
    }, {} as Record<string, AgentTool[]>);
}

export function AgentToolsPanel({
    tools,
    permissions,
    onChange,
    readOnly = false,
}: AgentToolsPanelProps) {
    const groupedTools = groupToolsByCategory(tools);
    const categories = Object.keys(groupedTools).sort();

    return (
        <TooltipProvider>
            <div className="space-y-6">
                <div className="flex items-center gap-2 mb-4">
                    <Wrench className="w-4 h-4 text-muted-foreground" />
                    <h4 className="text-sm font-medium">Available Tools</h4>
                </div>

                {categories.map((category) => (
                    <div key={category} className="space-y-2">
                        <h5 className="text-xs font-medium text-muted-foreground uppercase tracking-wide">
                            {categoryLabels[category] || category}
                        </h5>
                        <div className="space-y-1">
                            {groupedTools[category].map((tool) => {
                                const hasPermission = hasRequiredPermissions(tool, permissions);
                                const missingPerms = getMissingPermissions(tool, permissions);

                                return (
                                    <div
                                        key={tool.name}
                                        data-testid={`tool-row-${tool.name}`}
                                        className={cn(
                                            'flex items-center justify-between p-3 rounded-lg bg-muted/30 transition-colors',
                                            hasPermission
                                                ? 'hover:bg-muted/50'
                                                : 'opacity-50 cursor-not-allowed'
                                        )}
                                    >
                                        <div className="flex-1 min-w-0">
                                            <div className="flex items-center gap-2">
                                                <span className="text-sm font-medium font-mono">
                                                    {tool.name}
                                                </span>
                                                {!hasPermission && (
                                                    <Tooltip>
                                                        <TooltipTrigger>
                                                            <Lock className="w-3 h-3 text-muted-foreground" />
                                                        </TooltipTrigger>
                                                        <TooltipContent side="top" className="max-w-xs">
                                                            <p className="text-xs">
                                                                Missing permissions:{' '}
                                                                {missingPerms
                                                                    .map((p) => permissionLabels[p] || p)
                                                                    .join(', ')}
                                                            </p>
                                                        </TooltipContent>
                                                    </Tooltip>
                                                )}
                                            </div>
                                            <p className="text-xs text-muted-foreground mt-0.5 truncate">
                                                {tool.description}
                                            </p>
                                            {tool.requiredPermissions.length > 0 && (
                                                <div className="flex flex-wrap gap-1 mt-1.5">
                                                    {tool.requiredPermissions.map((perm) => (
                                                        <Badge
                                                            key={perm}
                                                            variant="outline"
                                                            className={cn(
                                                                'text-[10px] px-1.5 py-0',
                                                                permissionKeyToCamelCase(perm) &&
                                                                    permissions[
                                                                        permissionKeyToCamelCase(perm)!
                                                                    ]
                                                                    ? 'border-emerald-500/50 text-emerald-600 dark:text-emerald-400'
                                                                    : 'border-red-500/50 text-red-600 dark:text-red-400'
                                                            )}
                                                        >
                                                            {permissionLabels[perm] || perm}
                                                        </Badge>
                                                    ))}
                                                </div>
                                            )}
                                        </div>
                                        <div className="ml-4">
                                            <Switch
                                                checked={tool.enabled && hasPermission}
                                                onCheckedChange={(checked) =>
                                                    onChange(tool.name, checked)
                                                }
                                                disabled={readOnly || !hasPermission}
                                                aria-label={`Enable ${tool.name}`}
                                            />
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                ))}

                {tools.length === 0 && (
                    <div className="flex flex-col items-center justify-center py-8 text-center">
                        <Info className="w-8 h-8 text-muted-foreground mb-2" />
                        <p className="text-sm text-muted-foreground">
                            No tools configured for this agent
                        </p>
                    </div>
                )}
            </div>
        </TooltipProvider>
    );
}
