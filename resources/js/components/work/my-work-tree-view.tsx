import { useState } from 'react';
import { ChevronRight, ChevronDown, Folder, Briefcase, CheckSquare } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { StatusBadge } from './status-badge';
import { RaciBadgeGroup, getProminenceClass } from './raci-badge';
import { cn } from '@/lib/utils';
import type { MyWorkTreeData, MyWorkTreeProject, MyWorkTreeWorkOrder, MyWorkTreeTask } from '@/types/work';

interface MyWorkTreeViewProps {
    data: MyWorkTreeData;
    className?: string;
}

export function MyWorkTreeView({ data, className }: MyWorkTreeViewProps) {
    if (!data.projects || data.projects.length === 0) {
        return (
            <div className={cn('p-8 text-center text-muted-foreground', className)}>
                <p>No projects found with your RACI roles or assigned tasks.</p>
            </div>
        );
    }

    return (
        <div className={cn('space-y-1', className)}>
            {data.projects.map((project) => (
                <ProjectTreeNode key={project.id} project={project} />
            ))}
        </div>
    );
}

interface ProjectTreeNodeProps {
    project: MyWorkTreeProject;
}

function ProjectTreeNode({ project }: ProjectTreeNodeProps) {
    const [isExpanded, setIsExpanded] = useState(true);
    const hasWorkOrders = project.workOrders && project.workOrders.length > 0;
    const prominenceClass = getProminenceClass(project.userRaciRoles);

    return (
        <div className={cn('border-l-2 border-muted', prominenceClass && `border-l-4 ${prominenceClass}`)}>
            {/* Project Row */}
            <div className="group relative flex items-center gap-2 py-2 px-2 sm:px-3 hover:bg-muted/50 transition-colors">
                <button
                    onClick={() => setIsExpanded(!isExpanded)}
                    aria-label={isExpanded ? 'Collapse' : 'Expand'}
                    aria-expanded={isExpanded}
                    className="flex-shrink-0 w-5 h-5 flex items-center justify-center text-muted-foreground hover:text-foreground transition-colors"
                >
                    {hasWorkOrders ? (
                        isExpanded ? (
                            <ChevronDown className="h-4 w-4" />
                        ) : (
                            <ChevronRight className="h-4 w-4" />
                        )
                    ) : (
                        <div className="w-1 h-1 bg-muted-foreground rounded-full" />
                    )}
                </button>

                <Folder className="flex-shrink-0 w-5 h-5 text-primary" aria-hidden="true" />

                <Link href={`/work/projects/${project.id}`} className="flex-1 min-w-0 text-left">
                    <div className="flex items-center gap-1.5 sm:gap-2 flex-wrap">
                        <span className="font-semibold text-foreground truncate text-sm sm:text-base">
                            {project.name}
                        </span>
                        <StatusBadge status={project.status} type="project" />
                        <RaciBadgeGroup roles={project.userRaciRoles} />
                    </div>
                    <div className="flex items-center gap-2 sm:gap-4 text-xs text-muted-foreground mt-0.5 flex-wrap">
                        <span className="truncate">{project.partyName}</span>
                        <span>{project.progress}% complete</span>
                        <span className="hidden sm:inline">{project.workOrders.length} work orders</span>
                    </div>
                </Link>
            </div>

            {/* Work Orders - responsive indentation */}
            {isExpanded && hasWorkOrders && (
                <div className="ml-4 sm:ml-7">
                    {project.workOrders.map((workOrder) => (
                        <WorkOrderTreeNode key={workOrder.id} workOrder={workOrder} />
                    ))}
                </div>
            )}
        </div>
    );
}

interface WorkOrderTreeNodeProps {
    workOrder: MyWorkTreeWorkOrder;
}

function WorkOrderTreeNode({ workOrder }: WorkOrderTreeNodeProps) {
    const [isExpanded, setIsExpanded] = useState(false);
    const hasTasks = workOrder.tasks && workOrder.tasks.length > 0;
    const prominenceClass = getProminenceClass(workOrder.userRaciRoles);

    const priorityColors: Record<string, string> = {
        low: 'text-muted-foreground',
        medium: 'text-amber-600 dark:text-amber-500',
        high: 'text-orange-600 dark:text-orange-500',
        urgent: 'text-red-600 dark:text-red-500',
    };

    return (
        <div className={cn('border-l-2 border-muted', prominenceClass && `border-l-4 ${prominenceClass}`)}>
            {/* Work Order Row */}
            <div className="group relative flex items-center gap-2 py-2 px-2 sm:px-3 hover:bg-muted/50 transition-colors">
                <button
                    onClick={() => setIsExpanded(!isExpanded)}
                    aria-label={isExpanded ? 'Collapse' : 'Expand'}
                    aria-expanded={isExpanded}
                    className="flex-shrink-0 w-5 h-5 flex items-center justify-center text-muted-foreground hover:text-foreground transition-colors"
                >
                    {hasTasks ? (
                        isExpanded ? (
                            <ChevronDown className="h-4 w-4" />
                        ) : (
                            <ChevronRight className="h-4 w-4" />
                        )
                    ) : (
                        <div className="w-1 h-1 bg-muted-foreground rounded-full" />
                    )}
                </button>

                <Briefcase className="flex-shrink-0 w-4 h-4 text-muted-foreground" aria-hidden="true" />

                <Link href={`/work/work-orders/${workOrder.id}`} className="flex-1 min-w-0 text-left">
                    <div className="flex items-center gap-1.5 sm:gap-2 flex-wrap">
                        <span className="font-medium text-foreground truncate text-sm">
                            {workOrder.title}
                        </span>
                        <StatusBadge status={workOrder.status} type="workOrder" />
                        <span className={cn('text-xs font-medium hidden sm:inline', priorityColors[workOrder.priority])}>
                            {workOrder.priority}
                        </span>
                        <RaciBadgeGroup roles={workOrder.userRaciRoles} />
                    </div>
                    <div className="flex items-center gap-2 sm:gap-4 text-xs text-muted-foreground mt-0.5 flex-wrap">
                        {workOrder.dueDate && (
                            <span>Due: {new Date(workOrder.dueDate).toLocaleDateString()}</span>
                        )}
                        <span className="hidden sm:inline">{workOrder.tasks.length} tasks</span>
                    </div>
                </Link>
            </div>

            {/* Tasks - responsive indentation */}
            {isExpanded && hasTasks && (
                <div className="ml-4 sm:ml-7">
                    {workOrder.tasks.map((task) => (
                        <TaskTreeNode key={task.id} task={task} />
                    ))}
                </div>
            )}
        </div>
    );
}

interface TaskTreeNodeProps {
    task: MyWorkTreeTask;
}

function TaskTreeNode({ task }: TaskTreeNodeProps) {
    return (
        <div className="border-l-2 border-muted">
            <div className="group relative flex items-center gap-2 py-2 px-2 sm:px-3 hover:bg-muted/50 transition-colors">
                <div className="flex-shrink-0 w-5 h-5" aria-hidden="true" />

                <CheckSquare className="flex-shrink-0 w-4 h-4 text-muted-foreground" aria-hidden="true" />

                <Link href={`/work/tasks/${task.id}`} className="flex-1 min-w-0 text-left">
                    <div className="flex items-center gap-1.5 sm:gap-2 flex-wrap">
                        <span className="text-sm text-foreground truncate">
                            {task.title}
                        </span>
                        <StatusBadge status={task.status} type="task" />
                    </div>
                    <div className="flex items-center gap-2 sm:gap-4 text-xs text-muted-foreground mt-0.5 flex-wrap">
                        <span className="truncate">{task.assignedToName}</span>
                        <span>Due: {new Date(task.dueDate).toLocaleDateString()}</span>
                    </div>
                </Link>
            </div>
        </div>
    );
}
