import { useState, useEffect, useRef } from 'react';
import { router } from '@inertiajs/react';
import {
    DndContext,
    DragOverlay,
    pointerWithin,
    KeyboardSensor,
    PointerSensor,
    useSensor,
    useSensors,
    DragStartEvent,
    DragEndEvent,
    DragOverEvent,
} from '@dnd-kit/core';
import {
    SortableContext,
    verticalListSortingStrategy,
    arrayMove,
} from '@dnd-kit/sortable';
import { Plus } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { WorkOrderListGroup } from './work-order-list-group';
import { WorkOrderListItem } from './work-order-list-item';
import { CreateListDialog } from './create-list-dialog';
import type { WorkOrderList, WorkOrderInList } from '@/types/work';

interface WorkOrderListSectionProps {
    projectId: string;
    projectName: string;
    workOrderLists: WorkOrderList[];
    ungroupedWorkOrders: WorkOrderInList[];
    onCreateWorkOrder: (listId?: string) => void;
}

export function WorkOrderListSection({
    projectId,
    projectName,
    workOrderLists,
    ungroupedWorkOrders,
    onCreateWorkOrder,
}: WorkOrderListSectionProps) {
    const [createListDialogOpen, setCreateListDialogOpen] = useState(false);
    const [activeItem, setActiveItem] = useState<WorkOrderInList | null>(null);
    const [lists, setLists] = useState(workOrderLists);
    const [ungrouped, setUngrouped] = useState(ungroupedWorkOrders);

    // Track the original container when drag starts (for cross-container moves)
    const sourceContainerRef = useRef<string | null>(null);

    // Sync props to local state when Inertia updates them
    useEffect(() => {
        setLists(workOrderLists);
    }, [workOrderLists]);

    useEffect(() => {
        setUngrouped(ungroupedWorkOrders);
    }, [ungroupedWorkOrders]);

    const sensors = useSensors(
        useSensor(PointerSensor, {
            activationConstraint: {
                distance: 8,
            },
        }),
        useSensor(KeyboardSensor)
    );

    const handleDragStart = (event: DragStartEvent) => {
        const { active } = event;
        const activeId = String(active.id);

        // Find and store the source container
        sourceContainerRef.current = findContainer(activeId);

        // Find the item being dragged
        for (const list of lists) {
            const item = list.workOrders.find((wo) => wo.id === activeId);
            if (item) {
                setActiveItem(item);
                return;
            }
        }

        const ungroupedItem = ungrouped.find((wo) => wo.id === activeId);
        if (ungroupedItem) {
            setActiveItem(ungroupedItem);
        }
    };

    const handleDragOver = (event: DragOverEvent) => {
        const { active, over } = event;
        if (!over || !activeItem) return;

        const activeId = String(active.id);
        const overId = String(over.id);

        // Find current container of the active item
        const activeContainer = findContainer(activeId);
        // Find destination container
        let overContainer = findContainer(overId);

        // If over is a container directly (list id or 'ungrouped')
        if (overId === 'ungrouped' || lists.some((l) => l.id === overId)) {
            overContainer = overId;
        }

        if (!activeContainer || !overContainer || activeContainer === overContainer) {
            return;
        }

        // Move item between containers (optimistic UI update)
        // Remove from source
        if (activeContainer === 'ungrouped') {
            setUngrouped((prev) => prev.filter((wo) => wo.id !== activeId));
        } else {
            setLists((prevLists) =>
                prevLists.map((l) =>
                    l.id === activeContainer
                        ? { ...l, workOrders: l.workOrders.filter((wo) => wo.id !== activeId) }
                        : l
                )
            );
        }

        // Add to destination
        if (overContainer === 'ungrouped') {
            setUngrouped((prev) => [...prev, activeItem]);
        } else {
            setLists((prevLists) =>
                prevLists.map((l) =>
                    l.id === overContainer
                        ? { ...l, workOrders: [...l.workOrders, activeItem] }
                        : l
                )
            );
        }
    };

    const handleDragEnd = (event: DragEndEvent) => {
        const { active, over } = event;
        const draggedItem = activeItem;
        const originalContainer = sourceContainerRef.current;

        setActiveItem(null);
        sourceContainerRef.current = null;

        if (!over || !draggedItem) return;

        const activeId = String(active.id);
        const overId = String(over.id);

        // Find current container (after any DragOver moves)
        const currentContainer = findContainer(activeId);
        let overContainer = findContainer(overId);

        // If dropping on a container directly
        if (overId === 'ungrouped' || lists.some((l) => l.id === overId)) {
            overContainer = overId;
        }

        if (!currentContainer || !overContainer) return;

        // Same container reorder
        if (currentContainer === overContainer) {
            if (currentContainer === 'ungrouped') {
                const oldIndex = ungrouped.findIndex((wo) => wo.id === activeId);
                const newIndex = ungrouped.findIndex((wo) => wo.id === overId);
                if (oldIndex !== -1 && newIndex !== -1 && oldIndex !== newIndex) {
                    const newOrder = arrayMove(ungrouped, oldIndex, newIndex);
                    setUngrouped(newOrder);
                    saveWorkOrdersOrder(null, newOrder.map((wo) => wo.id));
                }
            } else {
                const list = lists.find((l) => l.id === currentContainer);
                if (list) {
                    const oldIndex = list.workOrders.findIndex((wo) => wo.id === activeId);
                    const newIndex = list.workOrders.findIndex((wo) => wo.id === overId);
                    if (oldIndex !== -1 && newIndex !== -1 && oldIndex !== newIndex) {
                        const newOrder = arrayMove(list.workOrders, oldIndex, newIndex);
                        setLists((prev) =>
                            prev.map((l) =>
                                l.id === currentContainer
                                    ? { ...l, workOrders: newOrder }
                                    : l
                            )
                        );
                        saveWorkOrdersOrder(currentContainer, newOrder.map((wo) => wo.id));
                    }
                }
            }
        }

        // Cross-container move - persist to backend
        if (originalContainer && originalContainer !== currentContainer) {
            // Save the work order to the new list
            saveWorkOrderMove(activeId, currentContainer === 'ungrouped' ? null : currentContainer);
        }
    };

    const saveWorkOrderMove = (workOrderId: string, newListId: string | null) => {
        if (newListId) {
            // Moving to a specific list
            router.post(
                `/work/work-order-lists/${newListId}/move-work-order`,
                { workOrderId },
                { preserveScroll: true }
            );
        } else {
            // Moving to ungrouped (remove from list)
            router.post(
                `/work/work-orders/${workOrderId}/remove-from-list`,
                {},
                { preserveScroll: true }
            );
        }
    };

    const findContainer = (id: string): string | null => {
        if (id === 'ungrouped') return 'ungrouped';

        // Check if it's a list id
        if (lists.some((l) => l.id === id)) return id;

        // Check if it's a work order in ungrouped
        if (ungrouped.some((wo) => wo.id === id)) return 'ungrouped';

        // Check if it's a work order in a list
        for (const list of lists) {
            if (list.workOrders.some((wo) => wo.id === id)) {
                return list.id;
            }
        }

        return null;
    };

    const saveWorkOrdersOrder = (listId: string | null, workOrderIds: string[]) => {
        router.post(
            `/work/projects/${projectId}/work-orders/reorder`,
            {
                listId,
                workOrderIds,
            },
            { preserveScroll: true }
        );
    };

    const totalWorkOrders =
        lists.reduce((acc, l) => acc + l.workOrders.length, 0) + ungrouped.length;

    return (
        <div className="mb-8">
            <div className="flex items-center justify-between mb-4">
                <h2 className="text-lg font-bold text-foreground">
                    Work Orders ({totalWorkOrders})
                </h2>
                <div className="flex items-center gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() => setCreateListDialogOpen(true)}
                    >
                        <Plus className="h-4 w-4 mr-2" />
                        Add List
                    </Button>
                    <Button size="sm" onClick={() => onCreateWorkOrder()}>
                        <Plus className="h-4 w-4 mr-2" />
                        Add Work Order
                    </Button>
                </div>
            </div>

            {totalWorkOrders === 0 && lists.length === 0 ? (
                <div className="text-center py-12 bg-muted/50 rounded-xl">
                    <p className="text-muted-foreground mb-4">
                        No work orders yet. Create one to get started.
                    </p>
                    <Button onClick={() => onCreateWorkOrder()}>Create Work Order</Button>
                </div>
            ) : (
                <DndContext
                    sensors={sensors}
                    collisionDetection={pointerWithin}
                    onDragStart={handleDragStart}
                    onDragOver={handleDragOver}
                    onDragEnd={handleDragEnd}
                >
                    <div className="space-y-4">
                        {lists.map((list) => (
                            <WorkOrderListGroup
                                key={list.id}
                                list={list}
                                projectId={projectId}
                                onCreateWorkOrder={() => onCreateWorkOrder(list.id)}
                            />
                        ))}

                        {/* Ungrouped Work Orders */}
                        <WorkOrderListGroup
                            list={{
                                id: 'ungrouped',
                                name: 'Ungrouped',
                                description: null,
                                color: null,
                                position: 999999,
                                workOrders: ungrouped,
                            }}
                            projectId={projectId}
                            onCreateWorkOrder={() => onCreateWorkOrder()}
                            isUngrouped
                        />
                    </div>

                    <DragOverlay>
                        {activeItem ? (
                            <WorkOrderListItem
                                workOrder={activeItem}
                                isDragOverlay
                            />
                        ) : null}
                    </DragOverlay>
                </DndContext>
            )}

            <CreateListDialog
                open={createListDialogOpen}
                onOpenChange={setCreateListDialogOpen}
                projectId={projectId}
                projectName={projectName}
            />
        </div>
    );
}
