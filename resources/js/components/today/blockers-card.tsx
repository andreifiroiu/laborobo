import { AlertTriangle, ChevronRight, Clock, Users, AlertCircle, HelpCircle } from 'lucide-react';
import type { TodayBlocker } from '@/types/today';

interface BlockersCardProps {
    blockers: TodayBlocker[];
    onViewBlocker?: (id: string) => void;
}

const priorityColors: Record<string, string> = {
    high: 'bg-red-100 text-red-700 dark:bg-red-950/30 dark:text-red-400',
    medium: 'bg-amber-100 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400',
    low: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400',
};

const reasonLabels: Record<string, { label: string; icon: typeof AlertTriangle }> = {
    waiting_on_external: { label: 'Waiting on External', icon: Users },
    missing_information: { label: 'Missing Info', icon: HelpCircle },
    technical_issue: { label: 'Technical Issue', icon: AlertCircle },
    waiting_on_approval: { label: 'Waiting on Approval', icon: Clock },
};

function formatBlockedDuration(blockedSince: string): string {
    const blocked = new Date(blockedSince);
    const now = new Date();
    const diffMs = now.getTime() - blocked.getTime();
    const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));

    if (diffDays === 0) return 'Today';
    if (diffDays === 1) return '1 day';
    return `${diffDays} days`;
}

export function BlockersCard({ blockers, onViewBlocker }: BlockersCardProps) {
    return (
        <div className="overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
            <div className="border-b border-slate-200 p-6 dark:border-slate-800">
                <div className="flex items-center gap-3">
                    <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-100 text-amber-600 dark:bg-amber-950/30 dark:text-amber-400">
                        <AlertTriangle className="h-5 w-5" />
                    </div>
                    <div>
                        <h3 className="text-lg font-semibold text-slate-900 dark:text-white">Blockers</h3>
                        <p className="text-sm text-slate-600 dark:text-slate-400">Items needing attention</p>
                    </div>
                </div>
            </div>

            <div className="divide-y divide-slate-200 dark:divide-slate-800">
                {blockers.length === 0 ? (
                    <div className="p-8 text-center">
                        <div className="mx-auto mb-3 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-950/30 dark:text-emerald-400">
                            <AlertTriangle className="h-8 w-8" />
                        </div>
                        <p className="font-medium text-slate-600 dark:text-slate-400">No blockers</p>
                        <p className="text-sm text-slate-500 dark:text-slate-500">Great progress!</p>
                    </div>
                ) : (
                    blockers.map((blocker) => {
                        const reasonInfo = reasonLabels[blocker.reason];
                        const ReasonIcon = reasonInfo?.icon || AlertTriangle;
                        return (
                            <button
                                key={blocker.id}
                                onClick={() => onViewBlocker?.(blocker.id)}
                                className="group w-full p-4 text-left transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/50"
                            >
                                <div className="flex items-start justify-between gap-4">
                                    <div className="min-w-0 flex-1">
                                        <div className="mb-1 flex flex-wrap items-center gap-2">
                                            <span className="inline-flex items-center gap-1 rounded-md bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-950/30 dark:text-amber-400">
                                                <ReasonIcon className="h-3 w-3" />
                                                {reasonInfo?.label || blocker.reason}
                                            </span>
                                            <span
                                                className={`inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium capitalize ${priorityColors[blocker.priority]}`}
                                            >
                                                {blocker.priority}
                                            </span>
                                        </div>
                                        <h4 className="mb-1 truncate font-medium text-slate-900 dark:text-white">
                                            {blocker.title}
                                        </h4>
                                        <p className="mb-2 line-clamp-2 text-sm text-slate-600 dark:text-slate-400">
                                            {blocker.blockerDetails}
                                        </p>
                                        <div className="flex items-center gap-4 text-xs text-slate-500 dark:text-slate-500">
                                            <span>{blocker.projectTitle}</span>
                                            <span>•</span>
                                            <span>Blocked {formatBlockedDuration(blocker.blockedSince)}</span>
                                            <span>•</span>
                                            <span>{blocker.assignedTo}</span>
                                        </div>
                                    </div>
                                    <ChevronRight className="mt-1 h-5 w-5 flex-shrink-0 text-slate-400 transition-colors group-hover:text-slate-600 dark:group-hover:text-slate-300" />
                                </div>
                            </button>
                        );
                    })
                )}
            </div>
        </div>
    );
}
