import {
    Activity,
    CheckCircle,
    FileCheck,
    MessageSquare,
    FileText,
    AlertTriangle,
    Play,
    Clock,
    FolderPlus,
} from 'lucide-react';
import type { TodayActivity } from '@/types/today';

interface ActivityFeedProps {
    activities: TodayActivity[];
    onViewActivity?: (id: string) => void;
}

const activityIcons: Record<string, typeof Activity> = {
    task_completed: CheckCircle,
    approval_created: FileCheck,
    comment_added: MessageSquare,
    deliverable_submitted: FileText,
    blocker_flagged: AlertTriangle,
    task_started: Play,
    time_logged: Clock,
    work_order_created: FolderPlus,
};

const activityColors: Record<string, string> = {
    task_completed: 'bg-emerald-100 text-emerald-600 dark:bg-emerald-950/30 dark:text-emerald-400',
    approval_created: 'bg-indigo-100 text-indigo-600 dark:bg-indigo-950/30 dark:text-indigo-400',
    comment_added: 'bg-blue-100 text-blue-600 dark:bg-blue-950/30 dark:text-blue-400',
    deliverable_submitted: 'bg-purple-100 text-purple-600 dark:bg-purple-950/30 dark:text-purple-400',
    blocker_flagged: 'bg-amber-100 text-amber-600 dark:bg-amber-950/30 dark:text-amber-400',
    task_started: 'bg-cyan-100 text-cyan-600 dark:bg-cyan-950/30 dark:text-cyan-400',
    time_logged: 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400',
    work_order_created: 'bg-indigo-100 text-indigo-600 dark:bg-indigo-950/30 dark:text-indigo-400',
};

function formatTimestamp(timestamp: string): string {
    const date = new Date(timestamp);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffMins = Math.floor(diffMs / (1000 * 60));
    const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
    const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));

    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins}m ago`;
    if (diffHours < 24) return `${diffHours}h ago`;
    if (diffDays === 1) return 'Yesterday';
    return `${diffDays}d ago`;
}

export function ActivityFeed({ activities, onViewActivity }: ActivityFeedProps) {
    return (
        <div className="overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
            <div className="border-b border-slate-200 p-6 dark:border-slate-800">
                <div className="flex items-center gap-3">
                    <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                        <Activity className="h-5 w-5" />
                    </div>
                    <div>
                        <h3 className="text-lg font-semibold text-slate-900 dark:text-white">Recent Activity</h3>
                        <p className="text-sm text-slate-600 dark:text-slate-400">What's been happening</p>
                    </div>
                </div>
            </div>

            <div className="max-h-[400px] divide-y divide-slate-200 overflow-y-auto dark:divide-slate-800">
                {activities.length === 0 ? (
                    <div className="p-8 text-center">
                        <div className="mx-auto mb-3 flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                            <Activity className="h-8 w-8" />
                        </div>
                        <p className="font-medium text-slate-600 dark:text-slate-400">No recent activity</p>
                        <p className="text-sm text-slate-500 dark:text-slate-500">Activities will appear here</p>
                    </div>
                ) : (
                    activities.map((activity) => {
                        const Icon = activityIcons[activity.type] || Activity;
                        const colorClass = activityColors[activity.type] || activityColors.time_logged;
                        return (
                            <button
                                key={activity.id}
                                onClick={() => onViewActivity?.(activity.id)}
                                className="group w-full p-4 text-left transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/50"
                            >
                                <div className="flex items-start gap-3">
                                    <div className={`rounded-lg p-2 ${colorClass}`}>
                                        <Icon className="h-4 w-4" />
                                    </div>
                                    <div className="min-w-0 flex-1">
                                        <div className="mb-1 flex items-center justify-between gap-2">
                                            <h4 className="truncate font-medium text-slate-900 dark:text-white">
                                                {activity.title}
                                            </h4>
                                            <span className="flex-shrink-0 text-xs text-slate-500 dark:text-slate-500">
                                                {formatTimestamp(activity.timestamp)}
                                            </span>
                                        </div>
                                        <p className="line-clamp-2 text-sm text-slate-600 dark:text-slate-400">
                                            {activity.description}
                                        </p>
                                        <div className="mt-1 text-xs text-slate-500 dark:text-slate-500">
                                            {activity.projectTitle}
                                        </div>
                                    </div>
                                </div>
                            </button>
                        );
                    })
                )}
            </div>
        </div>
    );
}
