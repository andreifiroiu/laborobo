import { useState } from 'react';
import { ChevronRight, ChevronDown, Folder, MoreVertical, Plus } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { StatusBadge } from './status-badge';
import type { Project, WorkOrder, Task } from '@/types/work';

interface ProjectTreeItemProps {
    project: Project;
    workOrders: WorkOrder[];
    tasks: Task[];
    onCreateWorkOrder: (projectId: string) => void;
    onCreateTask: (workOrderId: string) => void;
}

export function ProjectTreeItem({
    project,
    workOrders,
    tasks,
    onCreateWorkOrder,
    onCreateTask,
}: ProjectTreeItemProps) {
    const [isExpanded, setIsExpanded] = useState(true);

    const projectWorkOrders = workOrders.filter((wo) => wo.projectId === project.id);

    return (
        <div className="border-l-2 border-muted">
            {/* Project Row */}
            <div className="group relative flex items-center gap-2 py-2 px-3 hover:bg-muted/50 transition-colors">
                <button
                    onClick={() => setIsExpanded(!isExpanded)}
                    className="flex-shrink-0 w-5 h-5 flex items-center justify-center text-muted-foreground hover:text-foreground transition-colors"
                >
                    {isExpanded ? <ChevronDown className="h-4 w-4" /> : <ChevronRight className="h-4 w-4" />}
                </button>

                <Folder className="flex-shrink-0 w-5 h-5 text-primary" />

                <Link href={`/work/projects/${project.id}`} className="flex-1 min-w-0 text-left">
                    <div className="flex items-center gap-2 flex-wrap">
                        <span className="font-semibold text-foreground truncate">
                            {project.name}
                        </span>
                        <StatusBadge status={project.status} type="project" />
                        <span className="text-sm text-muted-foreground">
                            {project.partyName}
                        </span>
                    </div>
                    <div className="flex items-center gap-4 text-xs text-muted-foreground mt-0.5">
                        <span>{projectWorkOrders.length} work orders</span>
                        {project.budgetHours && (
                            <span>
                                {project.actualHours}/{project.budgetHours}h
                            </span>
                        )}
                        <span>{project.progress}% complete</span>
                    </div>
                </Link>

                <Button
                    variant="ghost"
                    size="icon"
                    onClick={() => onCreateWorkOrder(project.id)}
                    className="opacity-0 group-hover:opacity-100 transition-opacity h-7 w-7"
                    title="Add work order"
                >
                    <Plus className="h-4 w-4" />
                </Button>

                <Button variant="ghost" size="icon" className="h-7 w-7" title="More options">
                    <MoreVertical className="h-4 w-4" />
                </Button>
            </div>

            {/* Work Orders */}
            {isExpanded && projectWorkOrders.length > 0 && (
                <div className="ml-7">
                    {projectWorkOrders.map((workOrder) => (
                        <WorkOrderTreeItem
                            key={workOrder.id}
                            workOrder={workOrder}
                            tasks={tasks.filter((t) => t.workOrderId === workOrder.id)}
                            onCreateTask={onCreateTask}
                        />
                    ))}
                </div>
            )}
        </div>
    );
}

interface WorkOrderTreeItemProps {
    workOrder: WorkOrder;
    tasks: Task[];
    onCreateTask: (workOrderId: string) => void;
}

function WorkOrderTreeItem({ workOrder, tasks, onCreateTask }: WorkOrderTreeItemProps) {
    const [isExpanded, setIsExpanded] = useState(false);

    const priorityColors: Record<string, string> = {
        low: 'text-muted-foreground',
        medium: 'text-amber-600 dark:text-amber-500',
        high: 'text-orange-600 dark:text-orange-500',
        urgent: 'text-red-600 dark:text-red-500',
    };

    return (
        <div className="border-l-2 border-muted">
            {/* Work Order Row */}
            <div className="group relative flex items-center gap-2 py-2 px-3 hover:bg-muted/50 transition-colors">
                <button
                    onClick={() => setIsExpanded(!isExpanded)}
                    className="flex-shrink-0 w-5 h-5 flex items-center justify-center text-muted-foreground hover:text-foreground transition-colors"
                >
                    {tasks.length > 0 ? (
                        isExpanded ? (
                            <ChevronDown className="h-4 w-4" />
                        ) : (
                            <ChevronRight className="h-4 w-4" />
                        )
                    ) : (
                        <div className="w-1 h-1 bg-muted-foreground rounded-full" />
                    )}
                </button>

                <Link href={`/work/work-orders/${workOrder.id}`} className="flex-1 min-w-0 text-left">
                    <div className="flex items-center gap-2 flex-wrap">
                        <span className="font-medium text-foreground truncate">
                            {workOrder.title}
                        </span>
                        <StatusBadge status={workOrder.status} type="workOrder" />
                        <span className={`text-xs font-medium ${priorityColors[workOrder.priority]}`}>
                            {workOrder.priority}
                        </span>
                    </div>
                    <div className="flex items-center gap-4 text-xs text-muted-foreground mt-0.5">
                        <span>{workOrder.assignedToName}</span>
                        <span>{tasks.length} tasks</span>
                        <span>
                            {workOrder.actualHours}/{workOrder.estimatedHours}h
                        </span>
                    </div>
                </Link>

                <Button
                    variant="ghost"
                    size="icon"
                    onClick={() => onCreateTask(workOrder.id)}
                    className="opacity-0 group-hover:opacity-100 transition-opacity h-7 w-7"
                    title="Add task"
                >
                    <Plus className="h-4 w-4" />
                </Button>

                <Button variant="ghost" size="icon" className="h-7 w-7" title="More options">
                    <MoreVertical className="h-4 w-4" />
                </Button>
            </div>

            {/* Tasks */}
            {isExpanded && tasks.length > 0 && (
                <div className="ml-7">
                    {tasks.map((task) => (
                        <TaskTreeItem key={task.id} task={task} />
                    ))}
                </div>
            )}
        </div>
    );
}

interface TaskTreeItemProps {
    task: Task;
}

function TaskTreeItem({ task }: TaskTreeItemProps) {
    const completedItems = task.checklistItems.filter((item) => item.completed).length;
    const totalItems = task.checklistItems.length;

    return (
        <div className="group relative flex items-center gap-2 py-2 px-3 hover:bg-muted/50 transition-colors">
            <div className="flex-shrink-0 w-5 h-5" />

            <Link href={`/work/tasks/${task.id}`} className="flex-1 min-w-0 text-left">
                <div className="flex items-center gap-2 flex-wrap">
                    <span
                        className={`text-sm ${
                            task.isBlocked
                                ? 'line-through text-muted-foreground'
                                : 'text-foreground'
                        }`}
                    >
                        {task.title}
                    </span>
                    <StatusBadge status={task.status} type="task" />
                    {task.isBlocked && (
                        <span className="text-xs px-2 py-0.5 rounded-full font-medium bg-red-100 text-red-700 dark:bg-red-950/30 dark:text-red-400">
                            blocked
                        </span>
                    )}
                </div>
                <div className="flex items-center gap-4 text-xs text-muted-foreground mt-0.5">
                    <span>{task.assignedToName}</span>
                    {totalItems > 0 && (
                        <span>
                            {completedItems}/{totalItems} checklist items
                        </span>
                    )}
                    <span>
                        {task.actualHours}/{task.estimatedHours}h
                    </span>
                </div>
            </Link>

            <Button variant="ghost" size="icon" className="h-7 w-7" title="More options">
                <MoreVertical className="h-4 w-4" />
            </Button>
        </div>
    );
}
