import { router } from '@inertiajs/react';
import { useState } from 'react';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import type { AIAgent } from '@/types/settings';

interface RemoveAgentDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    agent: AIAgent | null;
}

export function RemoveAgentDialog({ open, onOpenChange, agent }: RemoveAgentDialogProps) {
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleRemove = () => {
        if (!agent) return;

        setIsSubmitting(true);

        router.delete(`/settings/agents/${agent.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                setIsSubmitting(false);
                onOpenChange(false);
            },
            onError: () => {
                setIsSubmitting(false);
            },
        });
    };

    return (
        <AlertDialog open={open} onOpenChange={onOpenChange}>
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Remove Agent</AlertDialogTitle>
                    <AlertDialogDescription>
                        Are you sure you want to remove <strong>{agent?.name}</strong>? This will
                        delete the agent's configuration, activity logs, and all associated data.
                        This action cannot be undone.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel disabled={isSubmitting}>Cancel</AlertDialogCancel>
                    <AlertDialogAction
                        onClick={handleRemove}
                        disabled={isSubmitting}
                        className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                    >
                        {isSubmitting ? 'Removing...' : 'Remove Agent'}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}
