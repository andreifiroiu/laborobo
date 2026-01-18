import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import { TimerConfirmationDialog } from '../timer-confirmation-dialog';

describe('TimerConfirmationDialog', () => {
    it('renders dialog with status change message when open', () => {
        render(
            <TimerConfirmationDialog
                isOpen={true}
                currentStatus="done"
                onConfirm={vi.fn()}
                onCancel={vi.fn()}
            />
        );

        expect(screen.getByRole('alertdialog')).toBeInTheDocument();
        expect(screen.getByText(/start timer/i)).toBeInTheDocument();
        expect(screen.getByText(/done/i)).toBeInTheDocument();
        expect(screen.getByText(/in progress/i)).toBeInTheDocument();
    });

    it('calls onConfirm when user confirms timer start', async () => {
        const user = userEvent.setup();
        const onConfirm = vi.fn();
        const onCancel = vi.fn();

        render(
            <TimerConfirmationDialog
                isOpen={true}
                currentStatus="in_review"
                onConfirm={onConfirm}
                onCancel={onCancel}
            />
        );

        const confirmButton = screen.getByRole('button', { name: /confirm/i });
        await user.click(confirmButton);

        expect(onConfirm).toHaveBeenCalledTimes(1);
        expect(onCancel).not.toHaveBeenCalled();
    });

    it('calls onCancel when user cancels', async () => {
        const user = userEvent.setup();
        const onConfirm = vi.fn();
        const onCancel = vi.fn();

        render(
            <TimerConfirmationDialog
                isOpen={true}
                currentStatus="approved"
                onConfirm={onConfirm}
                onCancel={onCancel}
            />
        );

        const cancelButton = screen.getByRole('button', { name: /cancel/i });
        await user.click(cancelButton);

        expect(onCancel).toHaveBeenCalledTimes(1);
        expect(onConfirm).not.toHaveBeenCalled();
    });

    it('does not render when isOpen is false', () => {
        render(
            <TimerConfirmationDialog
                isOpen={false}
                currentStatus="done"
                onConfirm={vi.fn()}
                onCancel={vi.fn()}
            />
        );

        expect(screen.queryByRole('alertdialog')).not.toBeInTheDocument();
    });
});
