import * as React from 'react';
import * as DialogPrimitive from '@radix-ui/react-dialog';
import { AlertTriangle } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

export interface TimerConfirmationDialogProps {
    /** Whether the dialog is open */
    isOpen: boolean;
    /** The current status of the task (e.g., 'done', 'in_review', 'approved') */
    currentStatus: string;
    /** Callback when user confirms to start timer */
    onConfirm: () => void;
    /** Callback when user cancels */
    onCancel: () => void;
    /** Whether the confirmation is in progress */
    isLoading?: boolean;
}

/**
 * Maps status values to human-readable labels
 */
const STATUS_LABELS: Record<string, string> = {
    done: 'Done',
    in_review: 'In Review',
    approved: 'Approved',
    todo: 'To Do',
    in_progress: 'In Progress',
    blocked: 'Blocked',
    cancelled: 'Cancelled',
    revision_requested: 'Revision Requested',
};

/**
 * Gets human-readable label for a status value.
 */
function getStatusLabel(status: string): string {
    return STATUS_LABELS[status] ?? status;
}

/**
 * TimerConfirmationDialog displays an alert dialog asking the user to confirm
 * starting a timer on a task that is in a completed/review state.
 *
 * Uses Radix Dialog primitive with alertdialog role for proper accessibility.
 */
function TimerConfirmationDialog({
    isOpen,
    currentStatus,
    onConfirm,
    onCancel,
    isLoading = false,
}: TimerConfirmationDialogProps) {
    const statusLabel = getStatusLabel(currentStatus);

    return (
        <DialogPrimitive.Root open={isOpen} onOpenChange={(open) => !open && onCancel()}>
            <DialogPrimitive.Portal>
                <DialogPrimitive.Overlay
                    className={cn(
                        'fixed inset-0 z-50 bg-black/80',
                        'data-[state=open]:animate-in data-[state=closed]:animate-out',
                        'data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0'
                    )}
                />
                <DialogPrimitive.Content
                    role="alertdialog"
                    aria-describedby="timer-confirmation-description"
                    aria-labelledby="timer-confirmation-title"
                    onPointerDownOutside={(e) => e.preventDefault()}
                    onEscapeKeyDown={(e) => {
                        if (!isLoading) {
                            onCancel();
                        } else {
                            e.preventDefault();
                        }
                    }}
                    className={cn(
                        'bg-background fixed top-[50%] left-[50%] z-50 grid w-full max-w-[calc(100%-2rem)] translate-x-[-50%] translate-y-[-50%] gap-4 rounded-lg border p-6 shadow-lg duration-200 sm:max-w-md',
                        'data-[state=open]:animate-in data-[state=closed]:animate-out',
                        'data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0',
                        'data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95'
                    )}
                >
                    <div className="flex flex-col gap-2 text-center sm:text-left">
                        <div className="flex items-center gap-3">
                            <div className="flex size-10 shrink-0 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/30">
                                <AlertTriangle className="size-5 text-amber-600 dark:text-amber-500" />
                            </div>
                            <DialogPrimitive.Title
                                id="timer-confirmation-title"
                                className="text-lg font-semibold leading-none"
                            >
                                Start Timer?
                            </DialogPrimitive.Title>
                        </div>
                        <DialogPrimitive.Description
                            id="timer-confirmation-description"
                            className="text-muted-foreground text-sm"
                        >
                            This task is currently marked as{' '}
                            <span className="font-medium text-foreground">{statusLabel}</span>.
                            Starting a timer will move it back to{' '}
                            <span className="font-medium text-foreground">In Progress</span>.
                        </DialogPrimitive.Description>
                    </div>

                    <div className="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <Button
                            variant="outline"
                            onClick={onCancel}
                            disabled={isLoading}
                        >
                            Cancel
                        </Button>
                        <Button
                            onClick={onConfirm}
                            disabled={isLoading}
                        >
                            {isLoading ? 'Starting...' : 'Confirm'}
                        </Button>
                    </div>
                </DialogPrimitive.Content>
            </DialogPrimitive.Portal>
        </DialogPrimitive.Root>
    );
}

export { TimerConfirmationDialog };
