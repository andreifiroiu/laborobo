import { useState } from 'react';
import { router } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    Bot,
    Clock,
    CheckCircle,
    XCircle,
    Link as LinkIcon,
    ChevronRight,
    AlertTriangle,
} from 'lucide-react';
import { cn } from '@/lib/utils';

interface AgentWorkflowApproval {
    id: number;
    workflowStateId: number;
    agentId: number;
    agentName: string;
    agentCode: string;
    actionDescription: string;
    contextPreview: string;
    pauseReason: string;
    relatedEntityType: string | null;
    relatedEntityId: number | null;
    relatedEntityName: string | null;
    createdAt: string;
    waitingHours: number;
    urgency: 'urgent' | 'high' | 'normal';
}

interface AgentApprovalItemProps {
    item: AgentWorkflowApproval;
    isSelected: boolean;
    onSelect: () => void;
    onView: () => void;
}

function formatWaitingTime(hours: number): string {
    if (hours < 1) {
        return '<1h';
    }
    if (hours < 24) {
        return `${Math.floor(hours)}h`;
    }
    const days = Math.floor(hours / 24);
    return `${days}d`;
}

export function AgentApprovalItem({
    item,
    isSelected,
    onSelect,
    onView,
}: AgentApprovalItemProps) {
    const [rejectDialogOpen, setRejectDialogOpen] = useState(false);
    const [rejectReason, setRejectReason] = useState('');
    const [processing, setProcessing] = useState(false);

    const urgencyColors = {
        urgent: 'border-l-red-500',
        high: 'border-l-orange-500',
        normal: 'border-l-gray-300 dark:border-l-gray-700',
    };

    const handleApprove = () => {
        setProcessing(true);
        router.post(
            `/settings/workflow-states/${item.workflowStateId}/approve`,
            {},
            {
                preserveScroll: true,
                onFinish: () => setProcessing(false),
            }
        );
    };

    const handleReject = () => {
        setProcessing(true);
        router.post(
            `/settings/workflow-states/${item.workflowStateId}/reject`,
            { reason: rejectReason },
            {
                preserveScroll: true,
                onFinish: () => {
                    setProcessing(false);
                    setRejectDialogOpen(false);
                    setRejectReason('');
                },
            }
        );
    };

    return (
        <>
            <div
                className={cn(
                    'flex items-start gap-4 p-4 border-l-4 hover:bg-muted/50 transition-colors',
                    urgencyColors[item.urgency],
                    isSelected && 'bg-muted/50'
                )}
            >
                {/* Checkbox */}
                <div className="pt-1">
                    <Checkbox
                        checked={isSelected}
                        onCheckedChange={onSelect}
                        aria-label={`Select ${item.actionDescription}`}
                    />
                </div>

                {/* Main Content */}
                <div className="flex-1 min-w-0 cursor-pointer" onClick={onView}>
                    {/* Header */}
                    <div className="flex items-start justify-between gap-4 mb-2">
                        <div className="flex-1 min-w-0">
                            <div className="flex items-center gap-2 mb-1">
                                <Badge
                                    variant="outline"
                                    className="bg-blue-500/10 text-blue-700 dark:text-blue-400"
                                >
                                    <Bot className="w-3 h-3 mr-1" />
                                    Agent Approval
                                </Badge>
                                {item.urgency === 'urgent' && (
                                    <Badge variant="destructive" className="text-xs">
                                        <AlertTriangle className="w-3 h-3 mr-1" />
                                        URGENT
                                    </Badge>
                                )}
                                {item.urgency === 'high' && (
                                    <Badge
                                        variant="outline"
                                        className="text-xs border-orange-500 text-orange-600 dark:text-orange-400"
                                    >
                                        HIGH
                                    </Badge>
                                )}
                            </div>
                            <h3 className="text-base font-semibold text-foreground">
                                {item.actionDescription}
                            </h3>
                        </div>

                        {/* Waiting time */}
                        <div className="flex items-center gap-1 text-xs text-muted-foreground whitespace-nowrap">
                            <Clock className="w-3 h-3" />
                            <span>{formatWaitingTime(item.waitingHours)}</span>
                        </div>
                    </div>

                    {/* Context Preview */}
                    <p className="text-sm text-muted-foreground line-clamp-2 mb-3">
                        {item.contextPreview}
                    </p>

                    {/* Metadata Footer */}
                    <div className="flex items-center gap-4 text-xs text-muted-foreground">
                        {/* Agent Source */}
                        <div className="flex items-center gap-1">
                            <Bot className="w-3 h-3" />
                            <span>{item.agentName}</span>
                        </div>

                        {/* Related Entity */}
                        {item.relatedEntityName && (
                            <div className="flex items-center gap-1">
                                <LinkIcon className="w-3 h-3" />
                                <span>{item.relatedEntityName}</span>
                            </div>
                        )}

                        {/* Pause Reason */}
                        {item.pauseReason && (
                            <div className="text-amber-600 dark:text-amber-400">
                                {item.pauseReason}
                            </div>
                        )}
                    </div>
                </div>

                {/* Actions */}
                <div className="flex items-center gap-2 flex-shrink-0">
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={(e) => {
                            e.stopPropagation();
                            setRejectDialogOpen(true);
                        }}
                        disabled={processing}
                    >
                        <XCircle className="w-4 h-4 mr-1" />
                        Reject
                    </Button>
                    <Button
                        variant="default"
                        size="sm"
                        onClick={(e) => {
                            e.stopPropagation();
                            handleApprove();
                        }}
                        disabled={processing}
                    >
                        <CheckCircle className="w-4 h-4 mr-1" />
                        Approve
                    </Button>
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={onView}
                        className="text-muted-foreground"
                    >
                        <ChevronRight className="w-4 h-4" />
                    </Button>
                </div>
            </div>

            {/* Reject Dialog */}
            <Dialog open={rejectDialogOpen} onOpenChange={setRejectDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Reject Agent Action</DialogTitle>
                        <DialogDescription>
                            Please provide a reason for rejecting this action. The agent will be
                            notified and may attempt an alternative approach.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4 py-4">
                        <div className="p-3 rounded-lg bg-muted/30">
                            <p className="text-sm font-medium mb-1">Action to reject:</p>
                            <p className="text-sm text-muted-foreground">
                                {item.actionDescription}
                            </p>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="rejectReason">Rejection Reason</Label>
                            <Textarea
                                id="rejectReason"
                                value={rejectReason}
                                onChange={(e) => setRejectReason(e.target.value)}
                                placeholder="Explain why you're rejecting this action..."
                                rows={3}
                            />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => {
                                setRejectDialogOpen(false);
                                setRejectReason('');
                            }}
                            disabled={processing}
                        >
                            Cancel
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={handleReject}
                            disabled={processing || !rejectReason.trim()}
                        >
                            {processing ? 'Rejecting...' : 'Reject Action'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
