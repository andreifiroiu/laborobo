import { useState } from 'react';
import { router } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ChevronDown } from 'lucide-react';
import type { AIAgent, GlobalAISettings, AgentActivityLog } from '@/types/settings';

interface AIAgentsSectionProps {
    agents: AIAgent[];
    globalSettings: GlobalAISettings;
    activityLogs: AgentActivityLog[];
}

export function AIAgentsSection({ agents, globalSettings, activityLogs }: AIAgentsSectionProps) {
    const [expandedAgentId, setExpandedAgentId] = useState<number | null>(null);
    const [activeTab, setActiveTab] = useState<'config' | 'activity' | 'budget'>('config');

    const toggleAgent = (agentId: number, enabled: boolean) => {
        router.post(
            `/settings/ai-agents/${agentId}/toggle`,
            { enabled },
            { preserveScroll: true }
        );
    };

    const getAgentLogs = (agentId: number) =>
        activityLogs.filter((log) => log.agentId === agentId).slice(0, 10);

    return (
        <div className="max-w-6xl mx-auto">
            <div className="mb-8">
                <h2 className="text-2xl font-semibold mb-2">AI Agents</h2>
                <p className="text-muted-foreground">
                    Configure AI agents, budgets, and permissions
                </p>
            </div>

            {/* Global Budget */}
            <Card className="mb-6">
                <CardHeader>
                    <CardTitle>Global AI Budget</CardTitle>
                </CardHeader>
                <CardContent>
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
                </CardContent>
            </Card>

            {/* Agent List */}
            <div className="space-y-3">
                {agents.map((agent) => {
                    const isExpanded = expandedAgentId === agent.id;
                    const logs = getAgentLogs(agent.id);
                    const config = agent.configuration;

                    return (
                        <Card key={agent.id} className="overflow-hidden">
                            {/* Agent Header */}
                            <button
                                onClick={() => setExpandedAgentId(isExpanded ? null : agent.id)}
                                className="w-full flex items-center justify-between p-6 hover:bg-muted/50 transition-colors text-left"
                            >
                                <div className="flex items-center gap-4">
                                    <div className="text-3xl">🤖</div>
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
                                        <button
                                            onClick={() => setActiveTab('config')}
                                            className={`pb-3 text-sm font-medium border-b-2 transition-colors ${
                                                activeTab === 'config'
                                                    ? 'border-primary text-primary'
                                                    : 'border-transparent text-muted-foreground hover:text-foreground'
                                            }`}
                                        >
                                            Config
                                        </button>
                                        <button
                                            onClick={() => setActiveTab('activity')}
                                            className={`pb-3 text-sm font-medium border-b-2 transition-colors ${
                                                activeTab === 'activity'
                                                    ? 'border-primary text-primary'
                                                    : 'border-transparent text-muted-foreground hover:text-foreground'
                                            }`}
                                        >
                                            Activity
                                        </button>
                                        <button
                                            onClick={() => setActiveTab('budget')}
                                            className={`pb-3 text-sm font-medium border-b-2 transition-colors ${
                                                activeTab === 'budget'
                                                    ? 'border-primary text-primary'
                                                    : 'border-transparent text-muted-foreground hover:text-foreground'
                                            }`}
                                        >
                                            Budget
                                        </button>
                                    </div>

                                    {/* Tab Content */}
                                    <div className="p-6">
                                        {activeTab === 'config' && (
                                            <div className="space-y-4">
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
                                            </div>
                                        )}

                                        {activeTab === 'activity' && (
                                            <div className="space-y-2">
                                                {logs.length === 0 ? (
                                                    <p className="text-sm text-muted-foreground text-center py-8">
                                                        No activity logs yet
                                                    </p>
                                                ) : (
                                                    logs.map((log) => (
                                                        <div
                                                            key={log.id}
                                                            className="p-4 bg-muted/50 rounded-lg"
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
                                                                                : 'outline'
                                                                    }
                                                                >
                                                                    {log.approvalStatus}
                                                                </Badge>
                                                            </div>
                                                            <p className="text-xs text-muted-foreground mb-1">
                                                                {new Date(log.timestamp).toLocaleString()} • $
                                                                {Number(log.cost).toFixed(4)}
                                                            </p>
                                                            <p className="text-sm">
                                                                {log.input.substring(0, 100)}...
                                                            </p>
                                                        </div>
                                                    ))
                                                )}
                                            </div>
                                        )}

                                        {activeTab === 'budget' && (
                                            <div className="space-y-4">
                                                <div className="grid grid-cols-3 gap-4">
                                                    <div>
                                                        <p className="text-sm text-muted-foreground mb-1">
                                                            Budget Cap
                                                        </p>
                                                        <p className="text-xl font-semibold">
                                                            ${config.monthlyBudgetCap}
                                                        </p>
                                                    </div>
                                                    <div>
                                                        <p className="text-sm text-muted-foreground mb-1">
                                                            Spent
                                                        </p>
                                                        <p className="text-xl font-semibold text-spend-primary">
                                                            ${Number(config.currentMonthSpend).toFixed(2)}
                                                        </p>
                                                    </div>
                                                    <div>
                                                        <p className="text-sm text-muted-foreground mb-1">
                                                            Remaining
                                                        </p>
                                                        <p className="text-xl font-semibold">
                                                            ${(Number(config.monthlyBudgetCap) - Number(config.currentMonthSpend)).toFixed(2)}
                                                        </p>
                                                    </div>
                                                </div>
                                                <div>
                                                    <p className="text-sm text-muted-foreground mb-2">
                                                        Budget Usage
                                                    </p>
                                                    <div className="w-full bg-muted rounded-full h-3">
                                                        <div
                                                            className={`h-3 rounded-full transition-all ${
                                                                (Number(config.currentMonthSpend) / Number(config.monthlyBudgetCap)) * 100 >= 90
                                                                    ? 'bg-red-600'
                                                                    : (Number(config.currentMonthSpend) / Number(config.monthlyBudgetCap)) * 100 >= 75
                                                                        ? 'bg-amber-600'
                                                                        : 'bg-spend-primary'
                                                            }`}
                                                            style={{
                                                                width: `${Math.min((Number(config.currentMonthSpend) / Number(config.monthlyBudgetCap)) * 100, 100)}%`,
                                                            }}
                                                        />
                                                    </div>
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            )}
                        </Card>
                    );
                })}
            </div>
        </div>
    );
}
