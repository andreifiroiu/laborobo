import { Calendar, Clock, Tag, User, CheckCircle2 } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { ProgressBar } from './progress-bar';
import type { Project } from '@/types/work';

interface ArchiveProjectCardProps {
    project: Project;
    workOrderCount: number;
    taskCount: number;
    onRestore: () => void;
}

const statusColors: Record<string, string> = {
    completed:
        'bg-emerald-100 dark:bg-emerald-950/30 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-900',
    archived:
        'bg-muted text-muted-foreground border-border',
    on_hold:
        'bg-amber-100 dark:bg-amber-950/30 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-900',
    active:
        'bg-indigo-100 dark:bg-indigo-950/30 text-indigo-700 dark:text-indigo-300 border-indigo-200 dark:border-indigo-900',
};

export function ArchiveProjectCard({
    project,
    workOrderCount,
    taskCount,
    onRestore,
}: ArchiveProjectCardProps) {
    const progressPercentage = Math.round(project.progress);
    const isCompleted = project.status === 'completed';
    const completedDate = project.targetEndDate
        ? new Date(project.targetEndDate).toLocaleDateString('en-US', {
              year: 'numeric',
              month: 'short',
              day: 'numeric',
          })
        : null;

    const duration =
        project.startDate && project.targetEndDate
            ? Math.ceil(
                  (new Date(project.targetEndDate).getTime() - new Date(project.startDate).getTime()) /
                      (1000 * 60 * 60 * 24)
              )
            : null;

    const budgetVariance = project.budgetHours
        ? ((project.actualHours - project.budgetHours) / project.budgetHours) * 100
        : null;

    return (
        <div className="group relative bg-card border border-border rounded-xl p-6 hover:border-muted-foreground/30 transition-all hover:shadow-lg">
            {/* Status Badge */}
            <div className="absolute top-4 right-4">
                <Badge variant="outline" className={`${statusColors[project.status]}`}>
                    {isCompleted && <CheckCircle2 className="w-3.5 h-3.5 mr-1" />}
                    {project.status === 'completed' ? 'Completed' : 'Archived'}
                </Badge>
            </div>

            {/* Header */}
            <div className="pr-24 mb-4">
                <Link
                    href={`/work/projects/${project.id}`}
                    className="text-lg font-bold text-foreground hover:text-primary transition-colors text-left mb-1 block"
                >
                    {project.name}
                </Link>
                {project.description && (
                    <p className="text-sm text-muted-foreground line-clamp-2">{project.description}</p>
                )}
            </div>

            {/* Metadata Grid */}
            <div className="grid grid-cols-2 gap-4 mb-4 pb-4 border-b border-border">
                <div className="flex items-center gap-2 text-sm">
                    <User className="w-4 h-4 text-muted-foreground shrink-0" />
                    <div className="min-w-0">
                        <div className="text-xs text-muted-foreground mb-0.5">Client</div>
                        <div className="font-medium text-foreground truncate">{project.partyName}</div>
                    </div>
                </div>

                <div className="flex items-center gap-2 text-sm">
                    <User className="w-4 h-4 text-muted-foreground shrink-0" />
                    <div className="min-w-0">
                        <div className="text-xs text-muted-foreground mb-0.5">Owner</div>
                        <div className="font-medium text-foreground truncate">{project.ownerName}</div>
                    </div>
                </div>

                {completedDate && (
                    <div className="flex items-center gap-2 text-sm">
                        <Calendar className="w-4 h-4 text-muted-foreground shrink-0" />
                        <div className="min-w-0">
                            <div className="text-xs text-muted-foreground mb-0.5">Completed</div>
                            <div className="font-medium text-foreground">{completedDate}</div>
                        </div>
                    </div>
                )}

                {duration && (
                    <div className="flex items-center gap-2 text-sm">
                        <Clock className="w-4 h-4 text-muted-foreground shrink-0" />
                        <div className="min-w-0">
                            <div className="text-xs text-muted-foreground mb-0.5">Duration</div>
                            <div className="font-medium text-foreground">
                                {duration} {duration === 1 ? 'day' : 'days'}
                            </div>
                        </div>
                    </div>
                )}
            </div>

            {/* Stats */}
            <div className="grid grid-cols-3 gap-4 mb-4">
                <div className="text-center">
                    <div className="text-2xl font-bold text-primary">{workOrderCount}</div>
                    <div className="text-xs text-muted-foreground">Work Orders</div>
                </div>
                <div className="text-center">
                    <div className="text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                        {taskCount}
                    </div>
                    <div className="text-xs text-muted-foreground">Tasks</div>
                </div>
                <div className="text-center">
                    <div className="text-2xl font-bold text-foreground">{project.actualHours}h</div>
                    <div className="text-xs text-muted-foreground">Actual Hours</div>
                </div>
            </div>

            {/* Budget Variance */}
            {budgetVariance !== null && (
                <div className="mb-4">
                    <div className="flex items-center justify-between text-xs mb-1">
                        <span className="text-muted-foreground">Budget Performance</span>
                        <span
                            className={`font-medium ${
                                budgetVariance <= 0
                                    ? 'text-emerald-600 dark:text-emerald-400'
                                    : budgetVariance <= 10
                                      ? 'text-amber-600 dark:text-amber-400'
                                      : 'text-red-600 dark:text-red-400'
                            }`}
                        >
                            {budgetVariance > 0 ? '+' : ''}
                            {budgetVariance.toFixed(0)}%
                        </span>
                    </div>
                    <div className="flex items-center gap-2 text-xs text-muted-foreground">
                        <span>Budget: {project.budgetHours}h</span>
                        <span>•</span>
                        <span>Actual: {project.actualHours}h</span>
                    </div>
                </div>
            )}

            {/* Progress Bar */}
            <div className="mb-4">
                <div className="flex items-center justify-between text-xs mb-2">
                    <span className="text-muted-foreground">Progress</span>
                    <span className="font-medium text-foreground">{progressPercentage}%</span>
                </div>
                <ProgressBar progress={progressPercentage} />
            </div>

            {/* Tags */}
            {project.tags.length > 0 && (
                <div className="flex flex-wrap gap-2 mb-4">
                    {project.tags.map((tag) => (
                        <Badge key={tag} variant="secondary" className="text-xs">
                            <Tag className="w-3 h-3 mr-1" />
                            {tag}
                        </Badge>
                    ))}
                </div>
            )}

            {/* Actions */}
            <div className="flex items-center gap-2 pt-4 border-t border-border">
                <Button variant="secondary" className="flex-1" asChild>
                    <Link href={`/work/projects/${project.id}`}>View Details</Link>
                </Button>
                {project.status === 'archived' && (
                    <Button onClick={onRestore}>Restore</Button>
                )}
            </div>
        </div>
    );
}
