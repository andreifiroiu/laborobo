import * as React from 'react';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

export interface TransitionDialogProps {
    /** Whether the dialog is open */
    isOpen: boolean;
    /** The target status being transitioned to */
    targetStatus: string;
    /** Human-readable label for the target status */
    targetLabel: string;
    /** Callback when transition is confirmed, with optional comment */
    onConfirm: (comment?: string) => void;
    /** Callback when dialog is cancelled */
    onCancel: () => void;
    /** Whether the confirmation is in progress */
    isLoading?: boolean;
    /** Custom title for the dialog */
    title?: string;
    /** Custom description for the dialog */
    description?: string;
    /** Error message to display */
    error?: string | null;
}

/**
 * Statuses that require a comment for the transition
 */
const COMMENT_REQUIRED_STATUSES = ['revision_requested'];

/**
 * TransitionDialog displays a confirmation dialog for status transitions.
 * For rejection transitions (RevisionRequested), it requires a comment.
 */
function TransitionDialog({
    isOpen,
    targetStatus,
    targetLabel,
    onConfirm,
    onCancel,
    isLoading = false,
    title,
    description,
    error,
}: TransitionDialogProps) {
    const [comment, setComment] = React.useState('');
    const requiresComment = COMMENT_REQUIRED_STATUSES.includes(targetStatus);
    const isCommentValid = !requiresComment || comment.trim().length > 0;

    // Reset comment when dialog opens/closes or target changes
    React.useEffect(() => {
        if (!isOpen) {
            setComment('');
        }
    }, [isOpen]);

    const handleConfirm = () => {
        if (isCommentValid) {
            onConfirm(requiresComment ? comment.trim() : undefined);
        }
    };

    const handleKeyDown = (e: React.KeyboardEvent) => {
        if (e.key === 'Enter' && e.ctrlKey && isCommentValid && !isLoading) {
            handleConfirm();
        }
    };

    const dialogTitle = title ?? `Transition to ${targetLabel}`;
    const dialogDescription =
        description ??
        (requiresComment
            ? `Please provide feedback explaining why changes are needed. This comment is required.`
            : `Are you sure you want to change the status to "${targetLabel}"?`);

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && onCancel()}>
            <DialogContent className="sm:max-w-[425px]" onKeyDown={handleKeyDown}>
                <DialogHeader>
                    <DialogTitle>{dialogTitle}</DialogTitle>
                    <DialogDescription>{dialogDescription}</DialogDescription>
                </DialogHeader>

                {requiresComment && (
                    <div className="grid gap-2 py-4">
                        <Label htmlFor="transition-comment" className="text-sm font-medium">
                            Comment <span className="text-destructive">*</span>
                        </Label>
                        <Textarea
                            id="transition-comment"
                            aria-label="Comment"
                            placeholder="Explain the reason for requesting changes..."
                            value={comment}
                            onChange={(e) => setComment(e.target.value)}
                            className={cn(
                                'min-h-[100px] resize-none',
                                !isCommentValid && comment.length > 0 && 'border-destructive'
                            )}
                            disabled={isLoading}
                            autoFocus
                        />
                        {!isCommentValid && comment.length === 0 && (
                            <p className="text-muted-foreground text-xs">
                                A comment is required for this transition.
                            </p>
                        )}
                    </div>
                )}

                {error && (
                    <div className="rounded-md border border-red-200 bg-red-50 p-3 dark:border-red-800 dark:bg-red-950">
                        <p className="text-sm text-red-800 dark:text-red-200">{error}</p>
                    </div>
                )}

                <DialogFooter>
                    <Button variant="outline" onClick={onCancel} disabled={isLoading}>
                        Cancel
                    </Button>
                    <Button
                        onClick={handleConfirm}
                        disabled={!isCommentValid || isLoading}
                        variant={requiresComment ? 'destructive' : 'default'}
                    >
                        {isLoading ? 'Confirming...' : 'Confirm'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

export { TransitionDialog, COMMENT_REQUIRED_STATUSES };
