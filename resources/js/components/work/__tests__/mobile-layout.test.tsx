import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/dialog';
import type { WorkOrderInList, WorkOrderList } from '@/types/work';
import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import { ViewTabs } from '../view-tabs';
import { WorkOrderListGroup } from '../work-order-list-group';
import { WorkOrderListItem } from '../work-order-list-item';

vi.mock('@inertiajs/react', () => ({
    Link: ({ children, href }: { children: React.ReactNode; href: string }) => (
        <a href={href}>{children}</a>
    ),
    router: { post: vi.fn(), patch: vi.fn(), delete: vi.fn() },
}));

vi.mock('@dnd-kit/sortable', () => ({
    useSortable: () => ({
        attributes: {},
        listeners: {},
        setNodeRef: vi.fn(),
        transform: null,
        transition: undefined,
        isDragging: false,
    }),
    SortableContext: ({ children }: { children: React.ReactNode }) => (
        <>{children}</>
    ),
    verticalListSortingStrategy: undefined,
}));

vi.mock('@dnd-kit/core', () => ({
    useDroppable: () => ({ setNodeRef: vi.fn(), isOver: false }),
}));

vi.mock('@dnd-kit/utilities', () => ({
    CSS: { Transform: { toString: () => undefined } },
}));

// These dialogs reach for Wayfinder actions and have their own tests; only
// the surrounding layout matters here.
vi.mock('../move-work-order-dialog', () => ({
    MoveWorkOrderDialog: () => null,
}));
vi.mock('../edit-list-dialog', () => ({ EditListDialog: () => null }));
vi.mock('../convert-list-to-project-dialog', () => ({
    ConvertListToProjectDialog: () => null,
}));

const workOrder: WorkOrderInList = {
    id: 'wo-1',
    title: 'Set up the staging environment for the new marketing site',
    status: 'draft',
    priority: 'medium',
    dueDate: '2020-01-01',
    assignedToName: 'Andrei Firoiu',
    tasksCount: 0,
    completedTasksCount: 0,
    positionInList: 0,
};

describe('WorkOrderListItem mobile layout', () => {
    it('gives the title its own row on phones so the badges cannot squeeze it out', () => {
        render(<WorkOrderListItem workOrder={workOrder} />);

        const title = screen.getByText(workOrder.title);
        const titleRow = title.parentElement!;

        // Full width until `sm`: the badges wrap underneath instead of
        // competing with the title for the same line.
        expect(titleRow).toHaveClass('basis-full');
        expect(titleRow).toHaveClass('sm:basis-auto');
        expect(titleRow).toHaveClass('min-w-0');

        // ...and the badge row is allowed to wrap.
        expect(titleRow.parentElement).toHaveClass('flex-wrap');
    });

    it('keeps the badges out of the title row while remaining unshrinkable', () => {
        render(<WorkOrderListItem workOrder={workOrder} />);

        const statusBadge = screen.getByText('draft');
        expect(statusBadge).toHaveClass('shrink-0');
        expect(statusBadge.parentElement).not.toBe(
            screen.getByText(workOrder.title).parentElement,
        );
    });

    it('lets the metadata line wrap on phones and truncate from sm up', () => {
        render(<WorkOrderListItem workOrder={workOrder} />);

        const meta = screen.getByText(/Andrei Firoiu/).closest('div')!;
        expect(meta).toHaveClass('sm:truncate');
        expect(meta).not.toHaveClass('truncate');
    });

    it('labels the icon-only controls for screen readers', () => {
        render(<WorkOrderListItem workOrder={workOrder} />);

        expect(
            screen.getByRole('button', { name: 'Reorder work order' }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'Work order actions' }),
        ).toBeInTheDocument();
    });
});

const list: WorkOrderList = {
    id: 'list-1',
    name: 'A list with a name long enough to push the counter off a phone screen',
    description: null,
    color: '#2563eb',
    position: 0,
    workOrders: [workOrder],
};

describe('WorkOrderListGroup mobile layout', () => {
    it('truncates the list name instead of pushing the count off screen', () => {
        render(
            <WorkOrderListGroup
                list={list}
                projectId="p-1"
                onCreateWorkOrder={vi.fn()}
            />,
        );

        expect(screen.getByText(list.name)).toHaveClass('truncate', 'min-w-0');
    });

    it('drops the "work order(s)" wording below sm, keeping the count', () => {
        render(
            <WorkOrderListGroup
                list={list}
                projectId="p-1"
                onCreateWorkOrder={vi.fn()}
            />,
        );

        expect(screen.getByText('work order')).toHaveClass('hidden');
        expect(screen.getByText('work order')).toHaveClass('sm:inline');
    });
});

describe('ViewTabs mobile layout', () => {
    it('scrolls horizontally rather than wrapping the underlined tabs', () => {
        render(<ViewTabs currentView="all_projects" onViewChange={vi.fn()} />);

        const strip = screen.getByRole('button', {
            name: /All Projects/,
        }).parentElement!;

        expect(strip).toHaveClass('overflow-x-auto');
        expect(strip).not.toHaveClass('flex-wrap');
    });
});

describe('DialogContent mobile sizing', () => {
    it('caps its height to the viewport and scrolls, so long forms stay reachable', () => {
        render(
            <Dialog open>
                <DialogContent>
                    <DialogTitle>Title</DialogTitle>
                    <DialogDescription>Body</DialogDescription>
                </DialogContent>
            </Dialog>,
        );

        const content = screen.getByRole('dialog');
        expect(content).toHaveClass('max-h-[calc(100dvh-2rem)]');
        expect(content).toHaveClass('overflow-y-auto');
    });

    it('uses tighter gutters on phones', () => {
        render(
            <Dialog open>
                <DialogContent>
                    <DialogTitle>Title</DialogTitle>
                    <DialogDescription>Body</DialogDescription>
                </DialogContent>
            </Dialog>,
        );

        const content = screen.getByRole('dialog');
        expect(content).toHaveClass('p-4');
        expect(content).toHaveClass('sm:p-6');
    });

    it('lets a caller opt out of both, for dialogs that scroll their own body', () => {
        render(
            <Dialog open>
                <DialogContent className="flex h-[80vh] flex-col overflow-hidden p-0 sm:p-0">
                    <DialogTitle>Title</DialogTitle>
                    <DialogDescription>Body</DialogDescription>
                </DialogContent>
            </Dialog>,
        );

        const content = screen.getByRole('dialog');
        expect(content).toHaveClass('overflow-hidden');
        expect(content).not.toHaveClass('overflow-y-auto');
        expect(content).not.toHaveClass('p-4');
        expect(content).not.toHaveClass('sm:p-6');
    });
});
