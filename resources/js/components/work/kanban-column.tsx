import { Plus } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { MyWorkCard } from './my-work-card';
import type { WorkOrder } from '@/types/work';

interface KanbanColumnProps {
    status: WorkOrder['status'];
    title: string;
    workOrders: WorkOrder[];
    onCreateWorkOrder: () => void;
}

const statusColors: Record<string, string> = {
    draft: 'bg-muted text-muted-foreground',
    active: 'bg-indigo-100 dark:bg-indigo-950/30 text-indigo-700 dark:text-indigo-300',
    in_review: 'bg-amber-100 dark:bg-amber-950/30 text-amber-700 dark:text-amber-300',
    approved: 'bg-emerald-100 dark:bg-emerald-950/30 text-emerald-700 dark:text-emerald-300',
    delivered: 'bg-muted text-muted-foreground',
};

export function KanbanColumn({ status, title, workOrders, onCreateWorkOrder }: KanbanColumnProps) {
    return (
        <div className="flex flex-col min-w-[320px] max-w-[320px] shrink-0">
            {/* Column Header */}
            <div className="flex items-center justify-between mb-3 px-1">
                <div className="flex items-center gap-2">
                    <h3 className="text-sm font-bold text-foreground">{title}</h3>
                    <span
                        className={`px-2 py-0.5 rounded-full text-xs font-medium ${statusColors[status]}`}
                    >
                        {workOrders.length}
                    </span>
                </div>
                <Button
                    variant="ghost"
                    size="icon"
                    onClick={onCreateWorkOrder}
                    className="h-7 w-7"
                    title="Add work order"
                >
                    <Plus className="h-4 w-4" />
                </Button>
            </div>

            {/* Column Content */}
            <div className="flex-1 space-y-3 p-2 bg-muted/50 rounded-lg border-2 border-dashed border-border min-h-[400px]">
                {workOrders.length === 0 ? (
                    <div className="flex items-center justify-center h-32 text-sm text-muted-foreground">
                        No work orders
                    </div>
                ) : (
                    workOrders.map((workOrder) => (
                        <div
                            key={workOrder.id}
                            className="cursor-pointer transition-transform hover:scale-[1.02]"
                        >
                            <MyWorkCard workOrder={workOrder} />
                        </div>
                    ))
                )}
            </div>
        </div>
    );
}
