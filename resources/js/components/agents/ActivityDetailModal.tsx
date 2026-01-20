import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { ChevronDown, Clock, Cpu, DollarSign, Wrench, CheckCircle, XCircle, AlertCircle } from 'lucide-react';
import { cn } from '@/lib/utils';
import { useState } from 'react';

interface ToolCall {
    name: string;
    params: Record<string, unknown>;
    result: Record<string, unknown> | string | null;
    durationMs: number;
}

interface ActivityLog {
    id: number;
    agentId: number;
    agentName: string;
    runType: string;
    timestamp: string;
    input: string;
    output: string | null;
    tokensUsed: number;
    cost: number;
    approvalStatus: 'pending' | 'approved' | 'rejected' | 'auto_approved' | 'failed';
    approvedBy: number | null;
    approvedAt: string | null;
    error: string | null;
    toolCalls?: ToolCall[];
    contextAccessed?: string[];
}

interface ActivityDetailModalProps {
    activity: ActivityLog | null;
    isOpen: boolean;
    onClose: () => void;
}

const statusConfig = {
    pending: {
        label: 'Pending',
        variant: 'secondary' as const,
        icon: AlertCircle,
    },
    approved: {
        label: 'Approved',
        variant: 'default' as const,
        icon: CheckCircle,
    },
    rejected: {
        label: 'Rejected',
        variant: 'destructive' as const,
        icon: XCircle,
    },
    auto_approved: {
        label: 'Auto-approved',
        variant: 'outline' as const,
        icon: CheckCircle,
    },
    failed: {
        label: 'Failed',
        variant: 'destructive' as const,
        icon: XCircle,
    },
};

function formatDuration(ms: number): string {
    if (ms < 1000) {
        return `${ms}ms`;
    }
    return `${(ms / 1000).toFixed(2)}s`;
}

function formatCost(cost: number): string {
    return `$${cost.toFixed(4)}`;
}

function formatDate(dateString: string): string {
    return new Intl.DateTimeFormat('en-US', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(dateString));
}

export function ActivityDetailModal({
    activity,
    isOpen,
    onClose,
}: ActivityDetailModalProps) {
    const [expandedTools, setExpandedTools] = useState<Record<number, boolean>>({});

    if (!activity) return null;

    const status = statusConfig[activity.approvalStatus];
    const StatusIcon = status.icon;

    const toggleToolExpanded = (index: number) => {
        setExpandedTools((prev) => ({
            ...prev,
            [index]: !prev[index],
        }));
    };

    const totalToolDuration = activity.toolCalls?.reduce(
        (sum, call) => sum + call.durationMs,
        0
    ) || 0;

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="max-w-2xl max-h-[85vh] overflow-y-auto">
                <DialogHeader>
                    <div className="flex items-center gap-2 mb-2">
                        <Badge variant={status.variant}>
                            <StatusIcon className="w-3 h-3 mr-1" />
                            {status.label}
                        </Badge>
                        <span className="text-xs text-muted-foreground">
                            {formatDate(activity.timestamp)}
                        </span>
                    </div>
                    <DialogTitle className="text-lg">
                        {activity.runType.replace(/_/g, ' ')}
                    </DialogTitle>
                    <DialogDescription>
                        Activity from {activity.agentName}
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-6 mt-4">
                    {/* Metadata Grid */}
                    <div className="grid grid-cols-3 gap-4">
                        <div className="flex items-center gap-2 p-3 rounded-lg bg-muted/30">
                            <Cpu className="w-4 h-4 text-muted-foreground" />
                            <div>
                                <p className="text-xs text-muted-foreground">Tokens</p>
                                <p className="text-sm font-medium">{activity.tokensUsed}</p>
                            </div>
                        </div>
                        <div className="flex items-center gap-2 p-3 rounded-lg bg-muted/30">
                            <DollarSign className="w-4 h-4 text-muted-foreground" />
                            <div>
                                <p className="text-xs text-muted-foreground">Cost</p>
                                <p className="text-sm font-medium">{formatCost(activity.cost)}</p>
                            </div>
                        </div>
                        <div className="flex items-center gap-2 p-3 rounded-lg bg-muted/30">
                            <Clock className="w-4 h-4 text-muted-foreground" />
                            <div>
                                <p className="text-xs text-muted-foreground">Tool Time</p>
                                <p className="text-sm font-medium">{formatDuration(totalToolDuration)}</p>
                            </div>
                        </div>
                    </div>

                    {/* Input */}
                    <div>
                        <h4 className="text-sm font-medium mb-2">Input</h4>
                        <div className="p-3 rounded-lg bg-muted/30 text-sm">
                            {activity.input}
                        </div>
                    </div>

                    {/* Output */}
                    {activity.output && (
                        <div>
                            <h4 className="text-sm font-medium mb-2">Output</h4>
                            <div className="p-3 rounded-lg bg-muted/30 text-sm whitespace-pre-wrap">
                                {activity.output}
                            </div>
                        </div>
                    )}

                    {/* Error */}
                    {activity.error && (
                        <div>
                            <h4 className="text-sm font-medium mb-2 text-destructive">Error</h4>
                            <div className="p-3 rounded-lg bg-destructive/10 border border-destructive/20 text-sm text-destructive">
                                {activity.error}
                            </div>
                        </div>
                    )}

                    {/* Tool Calls */}
                    {activity.toolCalls && activity.toolCalls.length > 0 && (
                        <div>
                            <div className="flex items-center gap-2 mb-3">
                                <Wrench className="w-4 h-4 text-muted-foreground" />
                                <h4 className="text-sm font-medium">
                                    Tool Calls ({activity.toolCalls.length})
                                </h4>
                            </div>
                            <div className="space-y-2">
                                {activity.toolCalls.map((call, index) => (
                                    <Collapsible
                                        key={index}
                                        open={expandedTools[index]}
                                        onOpenChange={() => toggleToolExpanded(index)}
                                    >
                                        <CollapsibleTrigger className="w-full">
                                            <div className="flex items-center justify-between p-3 rounded-lg bg-muted/30 hover:bg-muted/50 transition-colors">
                                                <div className="flex items-center gap-3">
                                                    <span className="text-sm font-mono font-medium">
                                                        {call.name}
                                                    </span>
                                                    <Badge variant="outline" className="text-xs">
                                                        {formatDuration(call.durationMs)}
                                                    </Badge>
                                                </div>
                                                <ChevronDown
                                                    className={cn(
                                                        'w-4 h-4 text-muted-foreground transition-transform',
                                                        expandedTools[index] && 'rotate-180'
                                                    )}
                                                />
                                            </div>
                                        </CollapsibleTrigger>
                                        <CollapsibleContent>
                                            <div className="mt-2 space-y-2 ml-3 border-l-2 border-muted pl-3">
                                                {/* Parameters */}
                                                <div>
                                                    <p className="text-xs text-muted-foreground mb-1">
                                                        Parameters
                                                    </p>
                                                    <pre className="p-2 rounded bg-muted/30 text-xs overflow-x-auto">
                                                        {JSON.stringify(call.params, null, 2)}
                                                    </pre>
                                                </div>
                                                {/* Result */}
                                                <div>
                                                    <p className="text-xs text-muted-foreground mb-1">
                                                        Result
                                                    </p>
                                                    <pre className="p-2 rounded bg-muted/30 text-xs overflow-x-auto">
                                                        {typeof call.result === 'string'
                                                            ? call.result
                                                            : JSON.stringify(call.result, null, 2)}
                                                    </pre>
                                                </div>
                                            </div>
                                        </CollapsibleContent>
                                    </Collapsible>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Context Accessed */}
                    {activity.contextAccessed && activity.contextAccessed.length > 0 && (
                        <div>
                            <h4 className="text-sm font-medium mb-2">Context Accessed</h4>
                            <div className="flex flex-wrap gap-1">
                                {activity.contextAccessed.map((context, index) => (
                                    <Badge key={index} variant="outline" className="text-xs">
                                        {context}
                                    </Badge>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Approval Info */}
                    {activity.approvedBy && activity.approvedAt && (
                        <div className="pt-4 border-t">
                            <p className="text-xs text-muted-foreground">
                                Approved by User #{activity.approvedBy} on{' '}
                                {formatDate(activity.approvedAt)}
                            </p>
                        </div>
                    )}
                </div>
            </DialogContent>
        </Dialog>
    );
}
