import { AlertCircle, Clock, CheckCircle2 } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { StatusBadge } from './status-badge';
import { ProgressBar } from './progress-bar';
import { cn } from '@/lib/utils';
import type { Task, MyWorkFiltersState, SortBy, SortDirection } from '@/types/work';

interface MyWorkTasksListProps {
    tasks: Task[];
    filters: MyWorkFiltersState;
    className?: string;
}

export function MyWorkTasksList({ tasks, filters, className }: MyWorkTasksListProps) {
    // Apply filters
    let filteredTasks = [...tasks];

    // Filter by status
    if (filters.statuses.length > 0) {
        filteredTasks = filteredTasks.filter((task) => filters.statuses.includes(task.status));
    }

    // Filter by due date range
    if (filters.dueDateRange) {
        const now = new Date();
        filteredTasks = filteredTasks.filter((task) => {
            if (!task.dueDate) return filters.dueDateRange !== 'overdue';
            const dueDate = new Date(task.dueDate);
            switch (filters.dueDateRange) {
                case 'this_week': {
                    const weekEnd = new Date(now);
                    weekEnd.setDate(weekEnd.getDate() + (7 - now.getDay()));
                    return dueDate <= weekEnd;
                }
                case 'next_7_days': {
                    const sevenDaysFromNow = new Date(now);
                    sevenDaysFromNow.setDate(sevenDaysFromNow.getDate() + 7);
                    return dueDate <= sevenDaysFromNow;
                }
                case 'next_30_days': {
                    const thirtyDaysFromNow = new Date(now);
                    thirtyDaysFromNow.setDate(thirtyDaysFromNow.getDate() + 30);
                    return dueDate <= thirtyDaysFromNow;
                }
                case 'overdue':
                    return dueDate < now;
                default:
                    return true;
            }
        });
    }

    // Apply sorting
    filteredTasks = sortTasks(filteredTasks, filters.sortBy, filters.sortDirection);

    // Group tasks by status
    const urgentTasks = filteredTasks.filter(
        (t) => t.isBlocked || (t.dueDate && isOverdue(t.dueDate)) || (t.status !== 'done' && t.dueDate && isUrgent(t.dueDate))
    );
    const inProgressTasks = filteredTasks.filter(
        (t) => t.status === 'in_progress' && !t.isBlocked && (!t.dueDate || !isOverdue(t.dueDate))
    );
    const todoTasks = filteredTasks.filter(
        (t) => t.status === 'todo' && !t.isBlocked && (!t.dueDate || !isOverdue(t.dueDate))
    );
    const doneTasks = filteredTasks.filter((t) => t.status === 'done');
    const blockedTasks = filteredTasks.filter((t) => t.isBlocked);

    const isEmpty = filteredTasks.length === 0;

    if (isEmpty) {
        return (
            <div className={cn('p-8 text-center text-muted-foreground', className)}>
                <p>No tasks match your current filters.</p>
            </div>
        );
    }

    return (
        <div className={cn('space-y-8', className)}>
            {urgentTasks.length > 0 && (
                <TaskSection title="Urgent & Overdue" tasks={urgentTasks} color="red" />
            )}
            {inProgressTasks.length > 0 && (
                <TaskSection title="In Progress" tasks={inProgressTasks} />
            )}
            {todoTasks.length > 0 && <TaskSection title="To Do" tasks={todoTasks} />}
            {blockedTasks.length > 0 && (
                <TaskSection title="Blocked" tasks={blockedTasks} color="red" />
            )}
            {doneTasks.length > 0 && (
                <TaskSection title="Completed" tasks={doneTasks} color="muted" />
            )}
        </div>
    );
}

interface TaskSectionProps {
    title: string;
    tasks: Task[];
    color?: 'red' | 'amber' | 'muted';
}

function TaskSection({ title, tasks, color }: TaskSectionProps) {
    const colorClasses: Record<string, string> = {
        red: 'text-red-600 dark:text-red-400',
        amber: 'text-amber-600 dark:text-amber-400',
        muted: 'text-muted-foreground',
    };

    const titleColorClass = color ? colorClasses[color] : 'text-foreground';

    return (
        <div>
            <h3 className={cn('text-lg font-bold mb-4', titleColorClass)}>
                {title} <span className="text-sm font-medium text-muted-foreground">({tasks.length})</span>
            </h3>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                {tasks.map((task) => (
                    <TaskCard key={task.id} task={task} />
                ))}
            </div>
        </div>
    );
}

interface TaskCardProps {
    task: Task;
}

function TaskCard({ task }: TaskCardProps) {
    const dueDate = task.dueDate ? new Date(task.dueDate) : null;
    const now = new Date();
    const daysUntilDue = dueDate ? Math.ceil((dueDate.getTime() - now.getTime()) / (1000 * 60 * 60 * 24)) : null;
    const overdue = daysUntilDue !== null && daysUntilDue < 0;

    const completedItems = task.checklistItems.filter((item) => item.completed).length;
    const totalItems = task.checklistItems.length;
    const progress = totalItems > 0 ? (completedItems / totalItems) * 100 : 0;

    return (
        <Link
            href={`/work/tasks/${task.id}`}
            className="block w-full text-left p-4 bg-card border border-border rounded-lg hover:shadow-md transition-all group"
        >
            <div className="flex items-start justify-between mb-2">
                <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-2 mb-1">
                        <StatusBadge status={task.status} type="task" />
                        {task.isBlocked && (
                            <span className="text-xs px-2 py-0.5 rounded-full font-medium bg-red-100 text-red-700 dark:bg-red-950/30 dark:text-red-400 flex items-center gap-1">
                                <AlertCircle className="h-3 w-3" />
                                Blocked
                            </span>
                        )}
                    </div>
                    <h4
                        className={cn(
                            'text-sm font-medium mb-1 group-hover:text-primary transition-colors',
                            task.isBlocked ? 'line-through text-muted-foreground' : 'text-foreground'
                        )}
                    >
                        {task.title}
                    </h4>
                    <p className="text-xs text-muted-foreground mb-2">{task.projectName} &middot; {task.workOrderTitle}</p>
                </div>
            </div>

            {totalItems > 0 && (
                <div className="mb-2">
                    <div className="flex items-center justify-between text-xs text-muted-foreground mb-1">
                        <span>
                            {completedItems}/{totalItems} checklist items
                        </span>
                        <span className="font-medium">{Math.round(progress)}%</span>
                    </div>
                    <ProgressBar progress={progress} />
                </div>
            )}

            <div className="flex items-center justify-between text-xs text-muted-foreground">
                <span className="flex items-center gap-1">
                    <Clock className="h-3 w-3" />
                    {task.actualHours}/{task.estimatedHours}h
                </span>
                {task.status === 'done' ? (
                    <div className="flex items-center gap-1 font-medium">
                        <CheckCircle2 className="h-3 w-3" />
                        Completed
                    </div>
                ) : daysUntilDue !== null ? (
                    <div className={cn('flex items-center gap-1 font-medium', overdue ? 'text-destructive' : '')}>
                        {overdue ? <AlertCircle className="h-3 w-3" /> : <Clock className="h-3 w-3" />}
                        {overdue ? `${Math.abs(daysUntilDue)}d overdue` : `${daysUntilDue}d left`}
                    </div>
                ) : null}
            </div>
        </Link>
    );
}

// Helper functions
function isOverdue(dueDateStr: string): boolean {
    const dueDate = new Date(dueDateStr);
    const now = new Date();
    return dueDate < now;
}

function isUrgent(dueDateStr: string): boolean {
    const dueDate = new Date(dueDateStr);
    const now = new Date();
    const daysUntilDue = Math.ceil((dueDate.getTime() - now.getTime()) / (1000 * 60 * 60 * 24));
    return daysUntilDue <= 2;
}

function sortTasks(tasks: Task[], sortBy: SortBy, direction: SortDirection): Task[] {
    const sorted = [...tasks].sort((a, b) => {
        let comparison = 0;

        switch (sortBy) {
            case 'due_date': {
                const aTime = a.dueDate ? new Date(a.dueDate).getTime() : Infinity;
                const bTime = b.dueDate ? new Date(b.dueDate).getTime() : Infinity;
                comparison = aTime - bTime;
                break;
            }
            case 'priority': {
                // For tasks, we don't have explicit priority, so sort by status urgency
                const statusOrder: Record<string, number> = { todo: 2, in_progress: 1, done: 3 };
                comparison = statusOrder[a.status] - statusOrder[b.status];
                break;
            }
            case 'recently_updated': {
                // Fallback to due date for tasks without update timestamp
                const aTime = a.dueDate ? new Date(a.dueDate).getTime() : 0;
                const bTime = b.dueDate ? new Date(b.dueDate).getTime() : 0;
                comparison = bTime - aTime;
                break;
            }
            case 'alphabetical':
                comparison = a.title.localeCompare(b.title);
                break;
        }

        return direction === 'asc' ? comparison : -comparison;
    });

    return sorted;
}
