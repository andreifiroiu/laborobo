import { useState } from 'react';
import { router } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ChevronDown, Plus, Filter, Calendar, Pencil, Trash2 } from 'lucide-react';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import {
    AgentPermissionsPanel,
    AgentToolsPanel,
    ActivityDetailModal,
    BudgetDisplay,
    AgentTemplateSelector,
} from '@/components/agents';
import { RemoveAgentDialog } from './remove-agent-dialog';
import type { AIAgent, GlobalAISettings, AgentActivityLog, AIProvider } from '@/types/settings';

interface AIAgentsSectionProps {
    agents: AIAgent[];
    globalSettings: GlobalAISettings;
    activityLogs: AgentActivityLog[];
    agentTemplates?: AgentTemplate[];
    agentTools?: AgentTool[];
    usedTemplateIds?: number[];
    aiProviders?: AIProvider[];
}

interface AgentTemplate {
    id: number;
    code: string;
    name: string;
    type: string;
    description: string;
    defaultTools: string[];
    defaultPermissions: string[];
    isActive: boolean;
}

interface AgentTool {
    name: string;
    description: string;
    category: string;
    requiredPermissions: string[];
    enabled: boolean;
}

interface ExtendedAgentActivityLog extends AgentActivityLog {
    toolCalls?: Array<{
        name: string;
        params: Record<string, unknown>;
        result: Record<string, unknown> | string | null;
        durationMs: number;
    }>;
    contextAccessed?: string[];
}

type AgentTab = 'config' | 'tools' | 'activity' | 'budget';

export function AIAgentsSection({
    agents,
    globalSettings,
    activityLogs,
    agentTemplates = [],
    agentTools = [],
    usedTemplateIds = [],
    aiProviders = [],
}: AIAgentsSectionProps) {
    const [expandedAgentId, setExpandedAgentId] = useState<number | null>(null);
    const [agentToDelete, setAgentToDelete] = useState<AIAgent | null>(null);
    const [activeTab, setActiveTab] = useState<AgentTab>('config');
    const [selectedActivity, setSelectedActivity] = useState<ExtendedAgentActivityLog | null>(null);
    const [templateSelectorOpen, setTemplateSelectorOpen] = useState(false);
    const [editingBudget, setEditingBudget] = useState(false);
    const [budgetForm, setBudgetForm] = useState({
        totalMonthlyBudget: globalSettings.totalMonthlyBudget,
        perProjectBudgetCap: globalSettings.perProjectBudgetCap,
    });
    const [savingBudget, setSavingBudget] = useState(false);

    // Activity filters
    const [activityStatusFilter, setActivityStatusFilter] = useState<string>('all');
    const [activityDateFilter, setActivityDateFilter] = useState<string>('all');

    const toggleAgent = (agentId: number, enabled: boolean) => {
        router.post(
            `/settings/ai-agents/${agentId}/toggle`,
            { enabled },
            { preserveScroll: true }
        );
    };

    const getAgentLogs = (agentId: number): ExtendedAgentActivityLog[] => {
        let logs = activityLogs.filter((log) => log.agentId === agentId) as ExtendedAgentActivityLog[];

        // Apply status filter
        if (activityStatusFilter !== 'all') {
            logs = logs.filter((log) => log.approvalStatus === activityStatusFilter);
        }

        // Apply date filter
        if (activityDateFilter !== 'all') {
            const now = new Date();
            const filterDays = activityDateFilter === '7d' ? 7 : activityDateFilter === '30d' ? 30 : 1;
            const cutoff = new Date(now.getTime() - filterDays * 24 * 60 * 60 * 1000);
            logs = logs.filter((log) => new Date(log.timestamp) >= cutoff);
        }

        return logs.slice(0, 20);
    };

    const getAgentTools = (agentId: number): AgentTool[] => {
        // In a real implementation, this would filter tools based on agent configuration
        return agentTools;
    };

    const handlePermissionChange = (agentId: number, updates: Record<string, unknown>) => {
        router.patch(
            `/settings/agents/${agentId}/configuration`,
            updates,
            { preserveScroll: true }
        );
    };

    const handleToolToggle = (agentId: number, toolName: string, enabled: boolean) => {
        router.patch(
            `/settings/agents/${agentId}/configuration`,
            { tool_permissions: { [toolName]: enabled } },
            { preserveScroll: true }
        );
    };

    const handleCreateAgent = (
        template: AgentTemplate | null,
        customName?: string,
        customDescription?: string
    ) => {
        if (template) {
            router.post('/settings/agents', {
                template_id: template.id,
            });
        } else if (customName) {
            router.post('/settings/agents', {
                name: customName,
                description: customDescription,
                is_custom: true,
            });
        }
        setTemplateSelectorOpen(false);
    };

    const handleSaveBudget = () => {
        setSavingBudget(true);
        router.patch('/settings/global-ai', {
            total_monthly_budget: budgetForm.totalMonthlyBudget,
            per_project_budget_cap: budgetForm.perProjectBudgetCap,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                setEditingBudget(false);
                setSavingBudget(false);
            },
            onError: () => setSavingBudget(false),
        });
    };

    // Transform config to permissions format for the panel
    const getPermissionsFromConfig = (config: AIAgent['configuration']) => ({
        canCreateWorkOrders: config?.permissions?.canCreateWorkOrders ?? false,
        canModifyTasks: config?.permissions?.canModifyTasks ?? false,
        canAccessClientData: config?.permissions?.canAccessClientData ?? false,
        canSendEmails: config?.permissions?.canSendEmails ?? false,
        canModifyDeliverables: (config as unknown as { canModifyDeliverables?: boolean })?.canModifyDeliverables ?? false,
        canAccessFinancialData: (config as unknown as { canAccessFinancialData?: boolean })?.canAccessFinancialData ?? false,
        canModifyPlaybooks: (config as unknown as { canModifyPlaybooks?: boolean })?.canModifyPlaybooks ?? false,
    });

    const getBehaviorSettings = (config: AIAgent['configuration']) => ({
        verbosityLevel: config?.behaviorSettings?.verbosityLevel ?? 'balanced',
        creativityLevel: config?.behaviorSettings?.creativityLevel ?? 'balanced',
        riskTolerance: config?.behaviorSettings?.riskTolerance ?? 'medium',
    });

    // Get budget data for an agent
    const getBudgetData = (config: AIAgent['configuration']) => {
        const dailyCap = Number(config?.monthlyBudgetCap ?? 0) / 30; // Approximate daily from monthly
        const dailySpent = (config as unknown as { dailySpend?: number })?.dailySpend ?? 0;

        return {
            dailyCap: Number(dailyCap.toFixed(2)),
            dailySpent: Number(dailySpent),
            monthlyCap: Number(config?.monthlyBudgetCap ?? 0),
            monthlySpent: Number(config?.currentMonthSpend ?? 0),
            costByCategory: [], // Would come from backend aggregation
        };
    };

    return (
        <div className="max-w-7xl mx-auto">
            <div className="flex items-center justify-between mb-8">
                <p className="text-muted-foreground">
                    Configure AI agents, budgets, and permissions
                </p>
                <Button className="ml-4" onClick={() => setTemplateSelectorOpen(true)}>
                    <Plus className="w-4 h-4 mr-2" />
                    Add Agent
                </Button>
            </div>

            {/* Global Budget */}
            <Card className="mb-6">
                <CardHeader className="flex flex-row items-center justify-between">
                    <CardTitle>Global AI Budget</CardTitle>
                    {!editingBudget && (
                        <Button variant="ghost" size="sm" onClick={() => setEditingBudget(true)}>
                            <Pencil className="w-4 h-4 mr-2" />
                            Edit
                        </Button>
                    )}
                </CardHeader>
                <CardContent>
                    {editingBudget ? (
                        <div className="space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="totalMonthlyBudget">Monthly Budget ($)</Label>
                                    <Input
                                        id="totalMonthlyBudget"
                                        type="number"
                                        min={0}
                                        step={0.01}
                                        value={budgetForm.totalMonthlyBudget}
                                        onChange={(e) => setBudgetForm(prev => ({ ...prev, totalMonthlyBudget: parseFloat(e.target.value) || 0 }))}
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="perProjectBudgetCap">Per-Project Cap ($)</Label>
                                    <Input
                                        id="perProjectBudgetCap"
                                        type="number"
                                        min={0}
                                        step={0.01}
                                        value={budgetForm.perProjectBudgetCap}
                                        onChange={(e) => setBudgetForm(prev => ({ ...prev, perProjectBudgetCap: parseFloat(e.target.value) || 0 }))}
                                    />
                                </div>
                            </div>
                            <div className="flex gap-2">
                                <Button onClick={handleSaveBudget} disabled={savingBudget}>
                                    {savingBudget ? 'Saving...' : 'Save'}
                                </Button>
                                <Button
                                    variant="outline"
                                    onClick={() => {
                                        setBudgetForm({
                                            totalMonthlyBudget: globalSettings.totalMonthlyBudget,
                                            perProjectBudgetCap: globalSettings.perProjectBudgetCap,
                                        });
                                        setEditingBudget(false);
                                    }}
                                >
                                    Cancel
                                </Button>
                            </div>
                        </div>
                    ) : (
                        <div className="grid grid-cols-3 gap-6">
                            <div>
                                <p className="text-sm text-muted-foreground mb-1">Monthly Budget</p>
                                <p className="text-2xl font-semibold">
                                    ${globalSettings.totalMonthlyBudget}
                                </p>
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground mb-1">Current Spend</p>
                                <p className="text-2xl font-semibold text-spend-primary">
                                    ${Number(globalSettings.currentMonthSpend).toFixed(2)}
                                </p>
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground mb-1">Remaining</p>
                                <p className="text-2xl font-semibold">
                                    ${(Number(globalSettings.totalMonthlyBudget) - Number(globalSettings.currentMonthSpend)).toFixed(2)}
                                </p>
                            </div>
                        </div>
                    )}
                </CardContent>
            </Card>

            {/* Agent List */}
            <div className="space-y-3">
                {agents.map((agent) => {
                    const isExpanded = expandedAgentId === agent.id;
                    const logs = getAgentLogs(agent.id);
                    const tools = getAgentTools(agent.id);
                    const config = agent.configuration;

                    return (
                        <Card key={agent.id} className="overflow-hidden">
                            {/* Agent Header */}
                            <button
                                onClick={() => setExpandedAgentId(isExpanded ? null : agent.id)}
                                className="w-full flex items-center justify-between p-6 hover:bg-muted/50 transition-colors text-left"
                            >
                                <div className="flex items-center gap-4">
                                    <div className="text-3xl">
                                        {agent.type === 'project-management' ? '📋' : '🤖'}
                                    </div>
                                    <div>
                                        <h4 className="text-lg font-semibold">{agent.name}</h4>
                                        <p className="text-sm text-muted-foreground">
                                            {agent.description}
                                        </p>
                                    </div>
                                </div>
                                <div className="flex items-center gap-4">
                                    <div className="text-right mr-4">
                                        <p className="text-sm text-muted-foreground">This Month</p>
                                        <p className="text-lg font-semibold">
                                            ${config?.currentMonthSpend ? Number(config.currentMonthSpend).toFixed(2) : '0.00'}
                                        </p>
                                    </div>
                                    <Badge
                                        variant={agent.status === 'enabled' ? 'success' : 'secondary'}
                                    >
                                        {agent.status}
                                    </Badge>
                                    <button
                                        onClick={(e) => {
                                            e.stopPropagation();
                                            setAgentToDelete(agent);
                                        }}
                                        className="p-1.5 rounded-md text-muted-foreground hover:text-destructive hover:bg-destructive/10 transition-colors"
                                        title="Remove agent"
                                    >
                                        <Trash2 className="w-4 h-4" />
                                    </button>
                                    <ChevronDown
                                        className={`w-5 h-5 text-muted-foreground transition-transform ${
                                            isExpanded ? 'rotate-180' : ''
                                        }`}
                                    />
                                </div>
                            </button>

                            {/* Expanded Content */}
                            {isExpanded && config && (
                                <div className="border-t">
                                    {/* Tabs */}
                                    <div className="flex gap-6 px-6 pt-4 border-b">
                                        {(['config', 'tools', 'activity', 'budget'] as AgentTab[]).map((tab) => (
                                            <button
                                                key={tab}
                                                onClick={() => setActiveTab(tab)}
                                                className={`pb-3 text-sm font-medium border-b-2 transition-colors ${
                                                    activeTab === tab
                                                        ? 'border-primary text-primary'
                                                        : 'border-transparent text-muted-foreground hover:text-foreground'
                                                }`}
                                            >
                                                {tab.charAt(0).toUpperCase() + tab.slice(1)}
                                            </button>
                                        ))}
                                    </div>

                                    {/* Tab Content */}
                                    <div className="p-6">
                                        {activeTab === 'config' && (
                                            <div className="space-y-6">
                                                {/* Enable Agent Toggle */}
                                                <div className="flex items-center justify-between">
                                                    <div>
                                                        <p className="font-medium">Enable Agent</p>
                                                        <p className="text-sm text-muted-foreground">
                                                            Allow this agent to run automatically
                                                        </p>
                                                    </div>
                                                    <button
                                                        onClick={() => toggleAgent(agent.id, !config.enabled)}
                                                        className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${
                                                            config.enabled
                                                                ? 'bg-primary'
                                                                : 'bg-muted'
                                                        }`}
                                                    >
                                                        <span
                                                            className={`inline-block h-4 w-4 transform rounded-full bg-white transition-transform ${
                                                                config.enabled ? 'translate-x-6' : 'translate-x-1'
                                                            }`}
                                                        />
                                                    </button>
                                                </div>

                                                {/* AI Provider & Model */}
                                                <div className="grid grid-cols-2 gap-4">
                                                    <div className="space-y-2">
                                                        <Label>AI Provider</Label>
                                                        <Select
                                                            value={config.aiProvider ?? '__default__'}
                                                            onValueChange={(value) => {
                                                                const provider = value === '__default__' ? null : value;
                                                                handlePermissionChange(agent.id, {
                                                                    ai_provider: provider,
                                                                    ai_model: null,
                                                                });
                                                            }}
                                                        >
                                                            <SelectTrigger>
                                                                <SelectValue placeholder="Select provider" />
                                                            </SelectTrigger>
                                                            <SelectContent>
                                                                <SelectItem value="__default__">(Use default)</SelectItem>
                                                                {aiProviders.map((p) => (
                                                                    <SelectItem key={p.code} value={p.code}>
                                                                        {p.name}
                                                                    </SelectItem>
                                                                ))}
                                                            </SelectContent>
                                                        </Select>
                                                    </div>
                                                    <div className="space-y-2">
                                                        <Label>AI Model</Label>
                                                        <Select
                                                            value={config.aiModel ?? '__default__'}
                                                            onValueChange={(value) => {
                                                                handlePermissionChange(agent.id, {
                                                                    ai_model: value === '__default__' ? null : value,
                                                                });
                                                            }}
                                                        >
                                                            <SelectTrigger>
                                                                <SelectValue placeholder="Select model" />
                                                            </SelectTrigger>
                                                            <SelectContent>
                                                                <SelectItem value="__default__">(Use default)</SelectItem>
                                                                {(aiProviders.find((p) => p.code === config.aiProvider)?.models ?? []).map((m) => (
                                                                    <SelectItem key={m.id} value={m.id}>
                                                                        {m.label}
                                                                    </SelectItem>
                                                                ))}
                                                            </SelectContent>
                                                        </Select>
                                                    </div>
                                                </div>

                                                {/* Run Limits */}
                                                <div className="grid grid-cols-2 gap-4">
                                                    <div className="space-y-2">
                                                        <Label>Daily Run Limit</Label>
                                                        <Input
                                                            type="number"
                                                            value={config.dailyRunLimit}
                                                            readOnly
                                                        />
                                                    </div>
                                                    <div className="space-y-2">
                                                        <Label>Monthly Budget Cap</Label>
                                                        <Input
                                                            type="number"
                                                            value={config.monthlyBudgetCap}
                                                            readOnly
                                                        />
                                                    </div>
                                                </div>

                                                {/* Permissions Panel */}
                                                <div className="pt-4 border-t">
                                                    <AgentPermissionsPanel
                                                        permissions={getPermissionsFromConfig(config)}
                                                        behaviorSettings={getBehaviorSettings(config)}
                                                        onChange={(updates) => handlePermissionChange(agent.id, updates)}
                                                    />
                                                </div>
                                            </div>
                                        )}

                                        {activeTab === 'tools' && (
                                            <AgentToolsPanel
                                                tools={tools}
                                                permissions={getPermissionsFromConfig(config)}
                                                onChange={(toolName, enabled) => handleToolToggle(agent.id, toolName, enabled)}
                                            />
                                        )}

                                        {activeTab === 'activity' && (
                                            <div className="space-y-4">
                                                {/* Filters */}
                                                <div className="flex items-center gap-4">
                                                    <div className="flex items-center gap-2">
                                                        <Filter className="w-4 h-4 text-muted-foreground" />
                                                        <Select
                                                            value={activityStatusFilter}
                                                            onValueChange={setActivityStatusFilter}
                                                        >
                                                            <SelectTrigger className="w-[140px]">
                                                                <SelectValue placeholder="Status" />
                                                            </SelectTrigger>
                                                            <SelectContent>
                                                                <SelectItem value="all">All Statuses</SelectItem>
                                                                <SelectItem value="approved">Approved</SelectItem>
                                                                <SelectItem value="rejected">Rejected</SelectItem>
                                                                <SelectItem value="pending">Pending</SelectItem>
                                                                <SelectItem value="failed">Failed</SelectItem>
                                                            </SelectContent>
                                                        </Select>
                                                    </div>
                                                    <div className="flex items-center gap-2">
                                                        <Calendar className="w-4 h-4 text-muted-foreground" />
                                                        <Select
                                                            value={activityDateFilter}
                                                            onValueChange={setActivityDateFilter}
                                                        >
                                                            <SelectTrigger className="w-[140px]">
                                                                <SelectValue placeholder="Date" />
                                                            </SelectTrigger>
                                                            <SelectContent>
                                                                <SelectItem value="all">All Time</SelectItem>
                                                                <SelectItem value="1d">Last 24h</SelectItem>
                                                                <SelectItem value="7d">Last 7 days</SelectItem>
                                                                <SelectItem value="30d">Last 30 days</SelectItem>
                                                            </SelectContent>
                                                        </Select>
                                                    </div>
                                                </div>

                                                {/* Activity List */}
                                                <div className="space-y-2">
                                                    {logs.length === 0 ? (
                                                        <p className="text-sm text-muted-foreground text-center py-8">
                                                            No activity logs match your filters
                                                        </p>
                                                    ) : (
                                                        logs.map((log) => (
                                                            <button
                                                                key={log.id}
                                                                onClick={() => setSelectedActivity(log)}
                                                                className="w-full p-4 bg-muted/50 rounded-lg hover:bg-muted/70 transition-colors text-left"
                                                            >
                                                                <div className="flex items-start justify-between mb-2">
                                                                    <p className="text-sm font-medium">
                                                                        {log.runType.replace(/_/g, ' ')}
                                                                    </p>
                                                                    <Badge
                                                                        variant={
                                                                            log.approvalStatus === 'approved'
                                                                                ? 'default'
                                                                                : log.approvalStatus === 'pending'
                                                                                    ? 'secondary'
                                                                                    : log.approvalStatus === 'rejected'
                                                                                        ? 'destructive'
                                                                                        : 'outline'
                                                                        }
                                                                    >
                                                                        {log.approvalStatus}
                                                                    </Badge>
                                                                </div>
                                                                <p className="text-xs text-muted-foreground mb-1">
                                                                    {new Date(log.timestamp).toLocaleString()} • $
                                                                    {Number(log.cost).toFixed(4)}
                                                                    {log.toolCalls && log.toolCalls.length > 0 && (
                                                                        <span className="ml-2">
                                                                            • {log.toolCalls.length} tool call(s)
                                                                        </span>
                                                                    )}
                                                                </p>
                                                                <p className="text-sm line-clamp-1">
                                                                    {log.input}
                                                                </p>
                                                            </button>
                                                        ))
                                                    )}
                                                </div>
                                            </div>
                                        )}

                                        {activeTab === 'budget' && (
                                            <BudgetDisplay budget={getBudgetData(config)} />
                                        )}
                                    </div>
                                </div>
                            )}
                        </Card>
                    );
                })}
            </div>

            {/* Activity Detail Modal */}
            <ActivityDetailModal
                activity={selectedActivity}
                isOpen={!!selectedActivity}
                onClose={() => setSelectedActivity(null)}
            />

            {/* Template Selector Modal */}
            <AgentTemplateSelector
                templates={agentTemplates}
                isOpen={templateSelectorOpen}
                onClose={() => setTemplateSelectorOpen(false)}
                onSelectTemplate={handleCreateAgent}
                usedTemplateIds={usedTemplateIds}
            />

            {/* Remove Agent Confirmation */}
            <RemoveAgentDialog
                open={!!agentToDelete}
                onOpenChange={(open) => !open && setAgentToDelete(null)}
                agent={agentToDelete}
            />
        </div>
    );
}
