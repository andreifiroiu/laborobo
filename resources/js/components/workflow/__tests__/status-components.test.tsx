import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import { StatusBadge, type TaskStatus, type WorkOrderStatus } from '@/components/ui/status-badge';
import { TransitionButton } from '../transition-button';
import { TransitionDialog } from '../transition-dialog';

describe('StatusBadge', () => {
    it('renders correct color and label for each task status', () => {
        const taskStatuses: TaskStatus[] = [
            'todo',
            'in_progress',
            'in_review',
            'approved',
            'done',
            'blocked',
            'cancelled',
            'revision_requested',
        ];

        const expectedLabels: Record<TaskStatus, string> = {
            todo: 'To Do',
            in_progress: 'In Progress',
            in_review: 'In Review',
            approved: 'Approved',
            done: 'Done',
            blocked: 'Blocked',
            cancelled: 'Cancelled',
            revision_requested: 'Revision Requested',
        };

        for (const status of taskStatuses) {
            const { unmount } = render(<StatusBadge status={status} variant="task" />);
            expect(screen.getByText(expectedLabels[status])).toBeInTheDocument();
            unmount();
        }
    });

    it('renders correct color and label for each work order status', () => {
        const workOrderStatuses: WorkOrderStatus[] = [
            'draft',
            'active',
            'in_review',
            'approved',
            'delivered',
            'blocked',
            'cancelled',
            'revision_requested',
        ];

        const expectedLabels: Record<WorkOrderStatus, string> = {
            draft: 'Draft',
            active: 'Active',
            in_review: 'In Review',
            approved: 'Approved',
            delivered: 'Delivered',
            blocked: 'Blocked',
            cancelled: 'Cancelled',
            revision_requested: 'Revision Requested',
        };

        for (const status of workOrderStatuses) {
            const { unmount } = render(<StatusBadge status={status} variant="work_order" />);
            expect(screen.getByText(expectedLabels[status])).toBeInTheDocument();
            unmount();
        }
    });
});

describe('TransitionButton', () => {
    it('shows only valid transitions in dropdown menu', async () => {
        const user = userEvent.setup();
        const onTransition = vi.fn();
        const allowedTransitions = [
            { value: 'in_progress', label: 'In Progress' },
            { value: 'cancelled', label: 'Cancelled' },
        ];

        render(
            <TransitionButton
                currentStatus="todo"
                allowedTransitions={allowedTransitions}
                onTransition={onTransition}
            />
        );

        const triggerButton = screen.getByRole('button', { name: /change status/i });
        await user.click(triggerButton);

        const menu = await screen.findByRole('menu');
        expect(within(menu).getByRole('menuitem', { name: /in progress/i })).toBeInTheDocument();
        expect(within(menu).getByRole('menuitem', { name: /cancelled/i })).toBeInTheDocument();
        expect(within(menu).queryByRole('menuitem', { name: /approved/i })).not.toBeInTheDocument();
    });

    it('calls onTransition when a transition is selected', async () => {
        const user = userEvent.setup();
        const onTransition = vi.fn();
        const allowedTransitions = [{ value: 'in_progress', label: 'In Progress' }];

        render(
            <TransitionButton
                currentStatus="todo"
                allowedTransitions={allowedTransitions}
                onTransition={onTransition}
            />
        );

        const triggerButton = screen.getByRole('button', { name: /change status/i });
        await user.click(triggerButton);

        const menuItem = await screen.findByRole('menuitem', { name: /in progress/i });
        await user.click(menuItem);

        expect(onTransition).toHaveBeenCalledWith('in_progress');
    });
});

describe('TransitionDialog', () => {
    it('requires comment when target status is revision_requested', async () => {
        const user = userEvent.setup();
        const onConfirm = vi.fn();
        const onCancel = vi.fn();

        render(
            <TransitionDialog
                isOpen={true}
                targetStatus="revision_requested"
                targetLabel="Revision Requested"
                onConfirm={onConfirm}
                onCancel={onCancel}
            />
        );

        const confirmButton = screen.getByRole('button', { name: /confirm/i });
        expect(confirmButton).toBeDisabled();

        const textarea = screen.getByRole('textbox', { name: /comment/i });
        await user.type(textarea, 'Please fix the formatting issues');

        expect(confirmButton).toBeEnabled();
        await user.click(confirmButton);

        expect(onConfirm).toHaveBeenCalledWith('Please fix the formatting issues');
    });

    it('does not require comment for non-rejection transitions', async () => {
        const user = userEvent.setup();
        const onConfirm = vi.fn();
        const onCancel = vi.fn();

        render(
            <TransitionDialog
                isOpen={true}
                targetStatus="approved"
                targetLabel="Approved"
                onConfirm={onConfirm}
                onCancel={onCancel}
            />
        );

        const confirmButton = screen.getByRole('button', { name: /confirm/i });
        expect(confirmButton).toBeEnabled();

        await user.click(confirmButton);
        expect(onConfirm).toHaveBeenCalledWith(undefined);
    });

    it('calls onCancel when cancel button is clicked', async () => {
        const user = userEvent.setup();
        const onConfirm = vi.fn();
        const onCancel = vi.fn();

        render(
            <TransitionDialog
                isOpen={true}
                targetStatus="approved"
                targetLabel="Approved"
                onConfirm={onConfirm}
                onCancel={onCancel}
            />
        );

        const cancelButton = screen.getByRole('button', { name: /cancel/i });
        await user.click(cancelButton);

        expect(onCancel).toHaveBeenCalled();
        expect(onConfirm).not.toHaveBeenCalled();
    });
});
