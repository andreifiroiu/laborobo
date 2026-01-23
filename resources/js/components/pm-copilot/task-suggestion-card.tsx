import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { useState } from 'react';
import { Check, X, ListTodo, Clock, ChevronDown, ChevronUp, CheckSquare, Link2 } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { TaskSuggestionCardProps, AIConfidence } from '@/types/pm-copilot.d';

const confidenceColors: Record<AIConfidence, string> = {
    high: 'bg-green-100 text-green-700 border-green-200 dark:bg-green-950/30 dark:text-green-400 dark:border-green-900',
    medium: 'bg-amber-100 text-amber-700 border-amber-200 dark:bg-amber-950/30 dark:text-amber-400 dark:border-amber-900',
    low: 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700',
};

/**
 * Card displaying task suggestions from PM Copilot.
 * Shows task list with estimates, checklist items preview, and dependencies.
 */
export function TaskSuggestionCard({
    tasks,
    onApprove,
    onReject,
    showActions = false,
}: TaskSuggestionCardProps) {
    const [expandedTasks, setExpandedTasks] = useState<Set<string>>(new Set());

    const toggleTask = (taskId: string) => {
        setExpandedTasks((prev) => {
            const next = new Set(prev);
            if (next.has(taskId)) {
                next.delete(taskId);
            } else {
                next.add(taskId);
            }
            return next;
        });
    };

    const totalEstimatedHours = tasks.reduce((sum, task) => sum + task.estimatedHours, 0);

    if (tasks.length === 0) {
        return (
            <Card>
                <CardContent className="py-8 text-center">
                    <ListTodo className="mx-auto mb-2 h-8 w-8 text-muted-foreground" />
                    <p className="text-muted-foreground">No tasks suggested</p>
                </CardContent>
            </Card>
        );
    }

    return (
        <Card>
            <CardHeader className="pb-3">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <div className="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-blue-700 dark:bg-blue-950/50 dark:text-blue-300">
                            <ListTodo className="h-4 w-4" />
                        </div>
                        <CardTitle className="text-base">Task Breakdown</CardTitle>
                    </div>
                    <div className="flex items-center gap-2 text-sm text-muted-foreground">
                        <Clock className="h-4 w-4" />
                        <span>{totalEstimatedHours}h total</span>
                    </div>
                </div>
            </CardHeader>
            <CardContent className="space-y-2">
                {tasks.map((task, index) => {
                    const isExpanded = expandedTasks.has(task.id);
                    const hasDetails = task.checklistItems.length > 0 || task.dependencies.length > 0;

                    return (
                        <Collapsible
                            key={task.id}
                            open={isExpanded}
                            onOpenChange={() => hasDetails && toggleTask(task.id)}
                        >
                            <div className="rounded-lg border border-border p-3">
                                {/* Task Header */}
                                <div className="flex items-start gap-3">
                                    <div className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-muted text-xs font-medium">
                                        {index + 1}
                                    </div>

                                    <div className="flex-1 min-w-0">
                                        <div className="flex items-center gap-2">
                                            <span className="font-medium text-sm">{task.title}</span>
                                            <Badge
                                                variant="outline"
                                                className={cn('text-xs capitalize', confidenceColors[task.confidence])}
                                            >
                                                {task.confidence}
                                            </Badge>
                                        </div>
                                        {task.description && (
                                            <p className="text-xs text-muted-foreground mt-0.5 line-clamp-2">
                                                {task.description}
                                            </p>
                                        )}
                                        <div className="flex items-center gap-3 mt-1 text-xs text-muted-foreground">
                                            <span className="flex items-center gap-1">
                                                <Clock className="h-3 w-3" />
                                                {task.estimatedHours}h
                                            </span>
                                            {task.checklistItems.length > 0 && (
                                                <span className="flex items-center gap-1">
                                                    <CheckSquare className="h-3 w-3" />
                                                    {task.checklistItems.length} items
                                                </span>
                                            )}
                                            {task.dependencies.length > 0 && (
                                                <span className="flex items-center gap-1">
                                                    <Link2 className="h-3 w-3" />
                                                    {task.dependencies.length} deps
                                                </span>
                                            )}
                                        </div>
                                    </div>

                                    <div className="flex items-center gap-2">
                                        {showActions && onApprove && onReject && (
                                            <>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    className="h-7 w-7 p-0"
                                                    onClick={() => onApprove(task.id)}
                                                    aria-label={`Approve ${task.title}`}
                                                >
                                                    <Check className="h-4 w-4 text-green-600" />
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    className="h-7 w-7 p-0"
                                                    onClick={() => onReject(task.id, undefined)}
                                                    aria-label={`Reject ${task.title}`}
                                                >
                                                    <X className="h-4 w-4 text-red-600" />
                                                </Button>
                                            </>
                                        )}
                                        {hasDetails && (
                                            <CollapsibleTrigger asChild>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    className="h-7 w-7 p-0"
                                                    aria-label={isExpanded ? 'Collapse details' : 'Expand details'}
                                                >
                                                    {isExpanded ? (
                                                        <ChevronUp className="h-4 w-4" />
                                                    ) : (
                                                        <ChevronDown className="h-4 w-4" />
                                                    )}
                                                </Button>
                                            </CollapsibleTrigger>
                                        )}
                                    </div>
                                </div>

                                {/* Expanded Details */}
                                <CollapsibleContent>
                                    <div className="mt-3 pt-3 border-t border-border space-y-3">
                                        {/* Checklist Items */}
                                        {task.checklistItems.length > 0 && (
                                            <div>
                                                <h5 className="text-xs font-medium uppercase text-muted-foreground mb-1">
                                                    Checklist
                                                </h5>
                                                <ul className="space-y-1">
                                                    {task.checklistItems.map((item, i) => (
                                                        <li key={i} className="flex items-center gap-2 text-sm">
                                                            <CheckSquare className="h-3 w-3 text-muted-foreground" />
                                                            <span>{item}</span>
                                                        </li>
                                                    ))}
                                                </ul>
                                            </div>
                                        )}

                                        {/* Dependencies */}
                                        {task.dependencies.length > 0 && (
                                            <div>
                                                <h5 className="text-xs font-medium uppercase text-muted-foreground mb-1">
                                                    Dependencies
                                                </h5>
                                                <div className="flex flex-wrap gap-1">
                                                    {task.dependencies.map((dep, i) => (
                                                        <Badge key={i} variant="secondary" className="text-xs">
                                                            {dep}
                                                        </Badge>
                                                    ))}
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                </CollapsibleContent>
                            </div>
                        </Collapsible>
                    );
                })}
            </CardContent>
        </Card>
    );
}
