import { ArrowLeft, ArrowRight } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { KanbanColumn } from './kanban-column';
import type { WorkOrder } from '@/types/work';

interface KanbanViewProps {
    workOrders: WorkOrder[];
    onCreateWorkOrder: (status: WorkOrder['status']) => void;
}

export function KanbanView({ workOrders, onCreateWorkOrder }: KanbanViewProps) {
    const columns: Array<{
        status: WorkOrder['status'];
        title: string;
    }> = [
        { status: 'draft', title: 'Draft' },
        { status: 'active', title: 'Active' },
        { status: 'in_review', title: 'In Review' },
        { status: 'approved', title: 'Approved' },
        { status: 'delivered', title: 'Delivered' },
    ];

    const workOrdersByStatus = columns.map((column) => ({
        ...column,
        workOrders: workOrders
            .filter((wo) => wo.status === column.status)
            .sort((a, b) => {
                // Sort by priority (urgent first), then by due date
                const priorityOrder = { urgent: 0, high: 1, medium: 2, low: 3 };
                const aPriority = priorityOrder[a.priority];
                const bPriority = priorityOrder[b.priority];
                if (aPriority !== bPriority) return aPriority - bPriority;
                return new Date(a.dueDate).getTime() - new Date(b.dueDate).getTime();
            }),
    }));

    const totalWorkOrders = workOrders.length;
    const activeCount = workOrders.filter((wo) => wo.status !== 'delivered').length;

    return (
        <div className="space-y-6">
            {/* Summary Stats */}
            <div className="flex items-center gap-6">
                <div className="px-4 py-2 bg-card border border-border rounded-lg">
                    <div className="text-2xl font-bold text-foreground">
                        {activeCount}
                        <span className="text-sm font-normal text-muted-foreground ml-1">
                            / {totalWorkOrders}
                        </span>
                    </div>
                    <div className="text-xs text-muted-foreground">Active Work Orders</div>
                </div>

                <div className="text-xs text-muted-foreground">
                    Organize work orders by status. Click a card to view details or use the + button to
                    create new work orders in each column.
                </div>
            </div>

            {/* Kanban Board */}
            <div className="overflow-x-auto pb-4">
                <div className="flex gap-4 min-w-max">
                    {workOrdersByStatus.map((column) => (
                        <KanbanColumn
                            key={column.status}
                            status={column.status}
                            title={column.title}
                            workOrders={column.workOrders}
                            onCreateWorkOrder={() => onCreateWorkOrder(column.status)}
                        />
                    ))}
                </div>
            </div>

            {/* Empty State */}
            {totalWorkOrders === 0 && (
                <div className="bg-card border border-border rounded-xl p-12 text-center">
                    <div className="w-16 h-16 rounded-full bg-muted flex items-center justify-center mx-auto mb-4">
                        <ArrowRight className="w-8 h-8 text-muted-foreground" />
                    </div>
                    <h3 className="text-lg font-semibold text-foreground mb-2">No work orders yet</h3>
                    <p className="text-sm text-muted-foreground mb-6">
                        Create your first work order to get started with the kanban board.
                    </p>
                    <Button onClick={() => onCreateWorkOrder('draft')}>Create Work Order</Button>
                </div>
            )}

            {/* Drag and Drop Info */}
            {totalWorkOrders > 0 && (
                <div className="flex items-center gap-2 text-xs text-muted-foreground bg-muted rounded-lg p-3">
                    <ArrowLeft className="w-4 h-4" />
                    <span>
                        <strong>Tip:</strong> Click on any work order card to view details and update its
                        status. Use the + button in each column header to add new work orders.
                    </span>
                </div>
            )}
        </div>
    );
}
