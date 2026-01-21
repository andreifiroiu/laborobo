import { AlertCircle, Clock, User } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { StatusBadge } from './status-badge';
import { RaciBadgeGroup, getProminenceClass } from './raci-badge';
import { cn } from '@/lib/utils';
import type { WorkOrder, RaciRole, MyWorkFiltersState, SortBy, SortDirection } from '@/types/work';

interface MyWorkOrdersListProps {
    workOrders: Array<WorkOrder & { userRaciRoles: RaciRole[] }>;
    filters: MyWorkFiltersState;
    showInformed: boolean;
    className?: string;
}

export function MyWorkOrdersList({ workOrders, filters, showInformed, className }: MyWorkOrdersListProps) {
    // Apply filters
    let filteredWorkOrders = [...workOrders];

    // Filter out informed items unless showInformed is true
    if (!showInformed) {
        filteredWorkOrders = filteredWorkOrders.filter(
            (wo) => !wo.userRaciRoles.every((role) => role === 'informed')
        );
    }

    // Filter by RACI roles
    if (filters.raciRoles.length > 0) {
        filteredWorkOrders = filteredWorkOrders.filter((wo) =>
            wo.userRaciRoles.some((role) => filters.raciRoles.includes(role))
        );
    }

    // Filter by status
    if (filters.statuses.length > 0) {
        filteredWorkOrders = filteredWorkOrders.filter((wo) => filters.statuses.includes(wo.status));
    }

    // Filter by due date range
    if (filters.dueDateRange && filters.dueDateRange !== 'custom') {
        const now = new Date();
        filteredWorkOrders = filteredWorkOrders.filter((wo) => {
            if (!wo.dueDate) return false;
            const dueDate = new Date(wo.dueDate);

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
    filteredWorkOrders = sortWorkOrders(filteredWorkOrders, filters.sortBy, filters.sortDirection);

    // Group by status
    const inReviewOrders = filteredWorkOrders.filter((wo) => wo.status === 'in_review');
    const activeOrders = filteredWorkOrders.filter((wo) => wo.status === 'active');
    const draftOrders = filteredWorkOrders.filter((wo) => wo.status === 'draft');
    const approvedOrders = filteredWorkOrders.filter((wo) => wo.status === 'approved');
    const deliveredOrders = filteredWorkOrders.filter((wo) => wo.status === 'delivered');

    const isEmpty = filteredWorkOrders.length === 0;

    if (isEmpty) {
        return (
            <div className={cn('p-8 text-center text-muted-foreground', className)}>
                <p>No work orders match your current filters.</p>
            </div>
        );
    }

    return (
        <div className={cn('space-y-8', className)}>
            {inReviewOrders.length > 0 && (
                <WorkOrderSection title="In Review" workOrders={inReviewOrders} color="amber" />
            )}
            {activeOrders.length > 0 && (
                <WorkOrderSection title="Active" workOrders={activeOrders} />
            )}
            {draftOrders.length > 0 && (
                <WorkOrderSection title="Draft" workOrders={draftOrders} color="muted" />
            )}
            {approvedOrders.length > 0 && (
                <WorkOrderSection title="Approved" workOrders={approvedOrders} color="emerald" />
            )}
            {deliveredOrders.length > 0 && (
                <WorkOrderSection title="Delivered" workOrders={deliveredOrders} color="muted" />
            )}
        </div>
    );
}

interface WorkOrderSectionProps {
    title: string;
    workOrders: Array<WorkOrder & { userRaciRoles: RaciRole[] }>;
    color?: 'amber' | 'emerald' | 'muted';
}

function WorkOrderSection({ title, workOrders, color }: WorkOrderSectionProps) {
    const colorClasses: Record<string, string> = {
        amber: 'text-amber-600 dark:text-amber-400',
        emerald: 'text-emerald-600 dark:text-emerald-400',
        muted: 'text-muted-foreground',
    };

    const titleColorClass = color ? colorClasses[color] : 'text-foreground';

    return (
        <div>
            <h3 className={cn('text-lg font-bold mb-4', titleColorClass)}>
                {title} <span className="text-sm font-medium text-muted-foreground">({workOrders.length})</span>
            </h3>
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
                {workOrders.map((workOrder) => (
                    <WorkOrderCard key={workOrder.id} workOrder={workOrder} />
                ))}
            </div>
        </div>
    );
}

interface WorkOrderCardProps {
    workOrder: WorkOrder & { userRaciRoles: RaciRole[] };
}

function WorkOrderCard({ workOrder }: WorkOrderCardProps) {
    const priorityColors: Record<string, string> = {
        low: 'border-l-muted',
        medium: 'border-l-amber-400 dark:border-l-amber-500',
        high: 'border-l-orange-500 dark:border-l-orange-500',
        urgent: 'border-l-red-500 dark:border-l-red-500',
    };

    const prominenceClass = getProminenceClass(workOrder.userRaciRoles);
    const isInformedOnly = workOrder.userRaciRoles.every((role) => role === 'informed');

    const dueDate = workOrder.dueDate ? new Date(workOrder.dueDate) : null;
    const now = new Date();
    const daysUntilDue = dueDate
        ? Math.ceil((dueDate.getTime() - now.getTime()) / (1000 * 60 * 60 * 24))
        : null;
    const isOverdue = daysUntilDue !== null && daysUntilDue < 0;

    return (
        <Link
            href={`/work/work-orders/${workOrder.id}`}
            className={cn(
                'block w-full text-left p-5 bg-card border border-border border-l-4 rounded-lg hover:shadow-md transition-all group',
                priorityColors[workOrder.priority],
                prominenceClass,
                isInformedOnly && 'opacity-60'
            )}
        >
            <div className="flex items-start justify-between mb-3">
                <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-2 mb-1 flex-wrap">
                        <StatusBadge status={workOrder.status} type="workOrder" />
                        <span className="text-xs font-medium text-muted-foreground uppercase">
                            {workOrder.priority}
                        </span>
                        <RaciBadgeGroup roles={workOrder.userRaciRoles} />
                        {workOrder.sopAttached && (
                            <span className="text-xs px-2 py-0.5 rounded-full font-medium bg-primary/10 text-primary">
                                SOP
                            </span>
                        )}
                    </div>
                    <h3 className="font-semibold text-foreground mb-1 group-hover:text-primary transition-colors">
                        {workOrder.title}
                    </h3>
                    {workOrder.description && (
                        <p className="text-sm text-muted-foreground line-clamp-2 mb-3">
                            {workOrder.description}
                        </p>
                    )}
                </div>
            </div>

            <div className="flex items-center justify-between text-xs text-muted-foreground">
                <div className="flex items-center gap-4">
                    <span className="flex items-center gap-1">
                        <User className="h-3.5 w-3.5" />
                        {workOrder.projectName}
                    </span>
                    <span className="flex items-center gap-1">
                        <Clock className="h-3.5 w-3.5" />
                        {workOrder.actualHours}/{workOrder.estimatedHours}h
                    </span>
                </div>
                {dueDate && (
                    <div
                        className={cn(
                            'flex items-center gap-1 font-medium',
                            isOverdue && 'text-destructive'
                        )}
                    >
                        {isOverdue ? (
                            <AlertCircle className="h-3.5 w-3.5" />
                        ) : (
                            <Clock className="h-3.5 w-3.5" />
                        )}
                        {isOverdue
                            ? `${Math.abs(daysUntilDue)}d overdue`
                            : `${daysUntilDue}d left`}
                    </div>
                )}
            </div>
        </Link>
    );
}

// Helper function for sorting
function sortWorkOrders(
    workOrders: Array<WorkOrder & { userRaciRoles: RaciRole[] }>,
    sortBy: SortBy,
    direction: SortDirection
): Array<WorkOrder & { userRaciRoles: RaciRole[] }> {
    const priorityOrder: Record<string, number> = { urgent: 0, high: 1, medium: 2, low: 3 };
    const raciOrder: Record<string, number> = { accountable: 0, responsible: 1, consulted: 2, informed: 3 };

    const sorted = [...workOrders].sort((a, b) => {
        let comparison = 0;

        switch (sortBy) {
            case 'due_date':
                if (!a.dueDate && !b.dueDate) comparison = 0;
                else if (!a.dueDate) comparison = 1;
                else if (!b.dueDate) comparison = -1;
                else comparison = new Date(a.dueDate).getTime() - new Date(b.dueDate).getTime();
                break;
            case 'priority':
                comparison = priorityOrder[a.priority] - priorityOrder[b.priority];
                break;
            case 'recently_updated':
                // Secondary sort by due date for work orders
                if (!a.dueDate && !b.dueDate) comparison = 0;
                else if (!a.dueDate) comparison = 1;
                else if (!b.dueDate) comparison = -1;
                else comparison = new Date(b.dueDate).getTime() - new Date(a.dueDate).getTime();
                break;
            case 'alphabetical':
                comparison = a.title.localeCompare(b.title);
                break;
        }

        // Secondary sort by RACI prominence
        if (comparison === 0) {
            const aHighestRole = Math.min(...a.userRaciRoles.map((r) => raciOrder[r]));
            const bHighestRole = Math.min(...b.userRaciRoles.map((r) => raciOrder[r]));
            comparison = aHighestRole - bHighestRole;
        }

        return direction === 'asc' ? comparison : -comparison;
    });

    return sorted;
}
