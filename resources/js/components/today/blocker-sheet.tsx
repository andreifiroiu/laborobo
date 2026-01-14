import { Calendar, Clock, User, Briefcase, AlertTriangle, Users, HelpCircle, AlertCircle, ArrowUpCircle, CheckCircle } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import type { TodayBlocker } from '@/types/today';

interface BlockerSheetProps {
    blocker: TodayBlocker | null;
    onClose: () => void;
    onResolveBlocker?: (id: string) => void;
    onEscalateBlocker?: (id: string) => void;
}

const priorityColors: Record<string, string> = {
    high: 'bg-red-100 text-red-700 dark:bg-red-950/30 dark:text-red-400',
    medium: 'bg-amber-100 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400',
    low: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400',
};

const reasonLabels: Record<string, { label: string; icon: typeof AlertTriangle; description: string }> = {
    waiting_on_external: {
        label: 'Waiting on External',
        icon: Users,
        description: 'This item is blocked waiting for input from an external party.',
    },
    missing_information: {
        label: 'Missing Information',
        icon: HelpCircle,
        description: 'This item is blocked due to missing required information.',
    },
    technical_issue: {
        label: 'Technical Issue',
        icon: AlertCircle,
        description: 'This item is blocked due to a technical problem that needs resolution.',
    },
    waiting_on_approval: {
        label: 'Waiting on Approval',
        icon: Clock,
        description: 'This item is blocked waiting for an approval to proceed.',
    },
};

const typeLabels: Record<string, string> = {
    task: 'Task',
    work_order: 'Work Order',
};

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString('en-US', {
        weekday: 'short',
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function formatBlockedDuration(blockedSince: string): string {
    const blocked = new Date(blockedSince);
    const now = new Date();
    const diffMs = now.getTime() - blocked.getTime();
    const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));

    if (diffDays === 0) return 'Less than a day';
    if (diffDays === 1) return '1 day';
    return `${diffDays} days`;
}

export function BlockerSheet({ blocker, onClose, onResolveBlocker, onEscalateBlocker }: BlockerSheetProps) {
    const handleResolve = () => {
        if (blocker) {
            onResolveBlocker?.(blocker.id);
            onClose();
        }
    };

    const handleEscalate = () => {
        if (blocker) {
            onEscalateBlocker?.(blocker.id);
            onClose();
        }
    };

    const reasonInfo = blocker ? reasonLabels[blocker.reason] : null;
    const ReasonIcon = reasonInfo?.icon || AlertTriangle;

    return (
        <Sheet open={!!blocker} onOpenChange={(open) => !open && onClose()}>
            <SheetContent side="right" className="w-full sm:w-[500px]">
                {blocker && (
                    <>
                        <SheetHeader>
                            <div className="mb-2 flex flex-wrap items-center gap-2">
                                <span className="inline-flex items-center gap-1 rounded-md bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-950/30 dark:text-amber-400">
                                    <ReasonIcon className="h-3 w-3" />
                                    {reasonInfo?.label || blocker.reason}
                                </span>
                                <span
                                    className={`inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium capitalize ${priorityColors[blocker.priority]}`}
                                >
                                    {blocker.priority} priority
                                </span>
                                <span className="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-400">
                                    {typeLabels[blocker.type]}
                                </span>
                            </div>
                            <SheetTitle>{blocker.title}</SheetTitle>
                            <SheetDescription>{blocker.blockerDetails}</SheetDescription>
                        </SheetHeader>

                        <div className="mt-6 space-y-4">
                            {/* Blocker reason explanation */}
                            {reasonInfo && (
                                <div className="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800/30 dark:bg-amber-950/20">
                                    <div className="flex items-start gap-3">
                                        <ReasonIcon className="mt-0.5 h-5 w-5 text-amber-600 dark:text-amber-400" />
                                        <div>
                                            <h4 className="text-sm font-medium text-amber-800 dark:text-amber-300">
                                                {reasonInfo.label}
                                            </h4>
                                            <p className="mt-1 text-sm text-amber-700 dark:text-amber-400">
                                                {reasonInfo.description}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            )}

                            <div className="rounded-lg border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/50">
                                <h4 className="mb-3 text-sm font-medium text-slate-900 dark:text-white">Details</h4>
                                <div className="space-y-3">
                                    <div className="flex items-center gap-3 text-sm">
                                        <User className="h-4 w-4 text-slate-400" />
                                        <span className="text-slate-600 dark:text-slate-400">Assigned to:</span>
                                        <span className="font-medium text-slate-900 dark:text-white">
                                            {blocker.assignedTo}
                                        </span>
                                    </div>
                                    <div className="flex items-center gap-3 text-sm">
                                        <Calendar className="h-4 w-4 text-slate-400" />
                                        <span className="text-slate-600 dark:text-slate-400">Blocked since:</span>
                                        <span className="font-medium text-slate-900 dark:text-white">
                                            {formatDate(blocker.blockedSince)}
                                        </span>
                                    </div>
                                    <div className="flex items-center gap-3 text-sm">
                                        <Clock className="h-4 w-4 text-slate-400" />
                                        <span className="text-slate-600 dark:text-slate-400">Duration:</span>
                                        <span className="font-medium text-red-600 dark:text-red-400">
                                            {formatBlockedDuration(blocker.blockedSince)}
                                        </span>
                                    </div>
                                    <div className="flex items-center gap-3 text-sm">
                                        <Briefcase className="h-4 w-4 text-slate-400" />
                                        <span className="text-slate-600 dark:text-slate-400">Project:</span>
                                        <span className="font-medium text-slate-900 dark:text-white">
                                            {blocker.projectTitle}
                                        </span>
                                    </div>
                                    <div className="flex items-center gap-3 text-sm">
                                        <AlertTriangle className="h-4 w-4 text-slate-400" />
                                        <span className="text-slate-600 dark:text-slate-400">Work Order:</span>
                                        <span className="font-medium text-slate-900 dark:text-white">
                                            {blocker.workOrderTitle}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <SheetFooter className="mt-6 flex gap-2">
                            <Button variant="outline" onClick={handleEscalate} className="flex-1">
                                <ArrowUpCircle className="mr-2 h-4 w-4" />
                                Escalate
                            </Button>
                            <Button onClick={handleResolve} className="flex-1">
                                <CheckCircle className="mr-2 h-4 w-4" />
                                Resolve
                            </Button>
                        </SheetFooter>
                    </>
                )}
            </SheetContent>
        </Sheet>
    );
}
