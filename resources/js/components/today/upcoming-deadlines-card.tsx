import { Calendar, ChevronRight, Users } from 'lucide-react';
import { Progress } from '@/components/ui/progress';
import type { TodayUpcomingDeadline } from '@/types/today';

interface UpcomingDeadlinesCardProps {
    deadlines: TodayUpcomingDeadline[];
    onViewWorkOrder?: (id: string) => void;
}

const priorityColors: Record<string, string> = {
    high: 'bg-red-100 text-red-700 dark:bg-red-950/30 dark:text-red-400',
    medium: 'bg-amber-100 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400',
    low: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400',
};

function getProgressColor(progress: number): string {
    if (progress >= 75) return 'bg-emerald-500';
    if (progress >= 50) return 'bg-amber-500';
    return 'bg-red-500';
}

function formatDaysUntilDue(days: number): string {
    if (days === 0) return 'Due today';
    if (days === 1) return 'Due tomorrow';
    return `${days} days left`;
}

export function UpcomingDeadlinesCard({ deadlines, onViewWorkOrder }: UpcomingDeadlinesCardProps) {
    return (
        <div className="overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
            <div className="border-b border-slate-200 p-6 dark:border-slate-800">
                <div className="flex items-center gap-3">
                    <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-100 text-indigo-600 dark:bg-indigo-950/30 dark:text-indigo-400">
                        <Calendar className="h-5 w-5" />
                    </div>
                    <div>
                        <h3 className="text-lg font-semibold text-slate-900 dark:text-white">Upcoming Deadlines</h3>
                        <p className="text-sm text-slate-600 dark:text-slate-400">Next 7 days</p>
                    </div>
                </div>
            </div>

            <div className="divide-y divide-slate-200 dark:divide-slate-800">
                {deadlines.length === 0 ? (
                    <div className="p-8 text-center">
                        <div className="mx-auto mb-3 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-950/30 dark:text-emerald-400">
                            <Calendar className="h-8 w-8" />
                        </div>
                        <p className="font-medium text-slate-600 dark:text-slate-400">No upcoming deadlines</p>
                        <p className="text-sm text-slate-500 dark:text-slate-500">All clear this week!</p>
                    </div>
                ) : (
                    deadlines.map((deadline) => (
                        <button
                            key={deadline.id}
                            onClick={() => onViewWorkOrder?.(deadline.id)}
                            className="group w-full p-4 text-left transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/50"
                        >
                            <div className="flex items-start justify-between gap-4">
                                <div className="min-w-0 flex-1">
                                    <div className="mb-1 flex flex-wrap items-center gap-2">
                                        <span
                                            className={`inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ${
                                                deadline.daysUntilDue <= 1
                                                    ? 'bg-red-100 text-red-700 dark:bg-red-950/30 dark:text-red-400'
                                                    : deadline.daysUntilDue <= 3
                                                      ? 'bg-amber-100 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400'
                                                      : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400'
                                            }`}
                                        >
                                            {formatDaysUntilDue(deadline.daysUntilDue)}
                                        </span>
                                        <span
                                            className={`inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium capitalize ${priorityColors[deadline.priority]}`}
                                        >
                                            {deadline.priority}
                                        </span>
                                    </div>
                                    <h4 className="mb-1 truncate font-medium text-slate-900 dark:text-white">
                                        {deadline.title}
                                    </h4>
                                    <p className="mb-2 text-sm text-slate-600 dark:text-slate-400">
                                        {deadline.projectTitle}
                                    </p>

                                    {/* Progress bar */}
                                    <div className="mb-2">
                                        <div className="mb-1 flex items-center justify-between text-xs">
                                            <span className="text-slate-500 dark:text-slate-500">Progress</span>
                                            <span className="font-medium text-slate-700 dark:text-slate-300">
                                                {deadline.progress}%
                                            </span>
                                        </div>
                                        <Progress value={deadline.progress} className="h-2" indicatorClassName={getProgressColor(deadline.progress)} />
                                    </div>

                                    <div className="flex items-center gap-1 text-xs text-slate-500 dark:text-slate-500">
                                        <Users className="h-3 w-3" />
                                        <span>{deadline.assignedTeam.join(', ')}</span>
                                    </div>
                                </div>
                                <ChevronRight className="mt-1 h-5 w-5 flex-shrink-0 text-slate-400 transition-colors group-hover:text-slate-600 dark:group-hover:text-slate-300" />
                            </div>
                        </button>
                    ))
                )}
            </div>
        </div>
    );
}
