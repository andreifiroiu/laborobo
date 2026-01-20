import { useState } from 'react';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { ChevronDown, Shield, Sliders } from 'lucide-react';
import { cn } from '@/lib/utils';

interface AgentPermissions {
    canCreateWorkOrders: boolean;
    canModifyTasks: boolean;
    canAccessClientData: boolean;
    canSendEmails: boolean;
    canModifyDeliverables: boolean;
    canAccessFinancialData: boolean;
    canModifyPlaybooks: boolean;
}

interface AgentBehaviorSettings {
    verbosityLevel: 'concise' | 'balanced' | 'detailed';
    creativityLevel: 'low' | 'balanced' | 'high';
    riskTolerance: 'low' | 'medium' | 'high';
}

interface AgentPermissionsPanelProps {
    permissions: AgentPermissions;
    behaviorSettings: AgentBehaviorSettings;
    onChange: (updates: Partial<AgentPermissions & AgentBehaviorSettings>) => void;
    readOnly?: boolean;
}

const permissionItems = [
    {
        key: 'canCreateWorkOrders' as const,
        label: 'Create Work Orders',
        description: 'Allow agent to create new work orders within projects',
    },
    {
        key: 'canModifyTasks' as const,
        label: 'Modify Tasks',
        description: 'Allow agent to create, update, and complete tasks',
    },
    {
        key: 'canAccessClientData' as const,
        label: 'Access Client Data',
        description: 'Allow agent to read client/party information',
    },
    {
        key: 'canSendEmails' as const,
        label: 'Send Emails',
        description: 'Allow agent to draft and send email communications',
    },
    {
        key: 'canModifyDeliverables' as const,
        label: 'Modify Deliverables',
        description: 'Allow agent to create and update deliverable records',
    },
    {
        key: 'canAccessFinancialData' as const,
        label: 'Access Financial Data',
        description: 'Allow agent to read budgets, estimates, and financial records',
    },
    {
        key: 'canModifyPlaybooks' as const,
        label: 'Modify Playbooks',
        description: 'Allow agent to create and update playbook templates',
    },
];

export function AgentPermissionsPanel({
    permissions,
    behaviorSettings,
    onChange,
    readOnly = false,
}: AgentPermissionsPanelProps) {
    const [advancedOpen, setAdvancedOpen] = useState(false);

    const handlePermissionChange = (key: keyof AgentPermissions, checked: boolean) => {
        if (!readOnly) {
            onChange({ [key]: checked });
        }
    };

    const handleBehaviorChange = (key: keyof AgentBehaviorSettings, value: string) => {
        if (!readOnly) {
            onChange({ [key]: value });
        }
    };

    return (
        <div className="space-y-6">
            {/* Permissions Section */}
            <div>
                <div className="flex items-center gap-2 mb-4">
                    <Shield className="w-4 h-4 text-muted-foreground" />
                    <h4 className="text-sm font-medium">Permissions</h4>
                </div>
                <div className="space-y-3">
                    {permissionItems.map((item) => (
                        <div
                            key={item.key}
                            className="flex items-start gap-3 p-3 rounded-lg bg-muted/30 hover:bg-muted/50 transition-colors"
                        >
                            <Checkbox
                                id={item.key}
                                checked={permissions[item.key]}
                                onCheckedChange={(checked) =>
                                    handlePermissionChange(item.key, checked === true)
                                }
                                disabled={readOnly}
                                aria-label={item.label}
                            />
                            <div className="flex-1">
                                <Label
                                    htmlFor={item.key}
                                    className="text-sm font-medium cursor-pointer"
                                >
                                    {item.label}
                                </Label>
                                <p className="text-xs text-muted-foreground mt-0.5">
                                    {item.description}
                                </p>
                            </div>
                        </div>
                    ))}
                </div>
            </div>

            {/* Advanced Settings Section */}
            <Collapsible open={advancedOpen} onOpenChange={setAdvancedOpen}>
                <CollapsibleTrigger className="flex items-center gap-2 w-full py-2 text-sm font-medium text-muted-foreground hover:text-foreground transition-colors">
                    <Sliders className="w-4 h-4" />
                    <span>Advanced Settings</span>
                    <ChevronDown
                        className={cn(
                            'w-4 h-4 ml-auto transition-transform',
                            advancedOpen && 'rotate-180'
                        )}
                    />
                </CollapsibleTrigger>
                <CollapsibleContent>
                    <div className="space-y-4 pt-4">
                        {/* Verbosity Level */}
                        <div className="space-y-2">
                            <Label htmlFor="verbosityLevel" className="text-sm font-medium">
                                Verbosity Level
                            </Label>
                            <Select
                                value={behaviorSettings.verbosityLevel}
                                onValueChange={(value) => handleBehaviorChange('verbosityLevel', value)}
                                disabled={readOnly}
                            >
                                <SelectTrigger id="verbosityLevel">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="concise">Concise</SelectItem>
                                    <SelectItem value="balanced">Balanced</SelectItem>
                                    <SelectItem value="detailed">Detailed</SelectItem>
                                </SelectContent>
                            </Select>
                            <p className="text-xs text-muted-foreground">
                                Controls how detailed the agent's responses are
                            </p>
                        </div>

                        {/* Creativity Level */}
                        <div className="space-y-2">
                            <Label htmlFor="creativityLevel" className="text-sm font-medium">
                                Creativity Level
                            </Label>
                            <Select
                                value={behaviorSettings.creativityLevel}
                                onValueChange={(value) => handleBehaviorChange('creativityLevel', value)}
                                disabled={readOnly}
                            >
                                <SelectTrigger id="creativityLevel">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="low">Low</SelectItem>
                                    <SelectItem value="balanced">Balanced</SelectItem>
                                    <SelectItem value="high">High</SelectItem>
                                </SelectContent>
                            </Select>
                            <p className="text-xs text-muted-foreground">
                                Controls how creative or conservative the agent's suggestions are
                            </p>
                        </div>

                        {/* Risk Tolerance */}
                        <div className="space-y-2">
                            <Label htmlFor="riskTolerance" className="text-sm font-medium">
                                Risk Tolerance
                            </Label>
                            <Select
                                value={behaviorSettings.riskTolerance}
                                onValueChange={(value) => handleBehaviorChange('riskTolerance', value)}
                                disabled={readOnly}
                            >
                                <SelectTrigger id="riskTolerance">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="low">Low (more cautious)</SelectItem>
                                    <SelectItem value="medium">Medium</SelectItem>
                                    <SelectItem value="high">High (more autonomous)</SelectItem>
                                </SelectContent>
                            </Select>
                            <p className="text-xs text-muted-foreground">
                                Controls how much autonomy the agent has for decisions
                            </p>
                        </div>
                    </div>
                </CollapsibleContent>
            </Collapsible>
        </div>
    );
}
