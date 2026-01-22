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
import type { TeamMember } from '@/types/settings';

interface RemoveMemberDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    member: TeamMember | null;
}

export function RemoveMemberDialog({ open, onOpenChange, member }: RemoveMemberDialogProps) {
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleRemove = () => {
        if (!member) return;

        setIsSubmitting(true);

        router.delete(`/settings/team-members/${member.id}`, {
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
                    <AlertDialogTitle>Remove Team Member</AlertDialogTitle>
                    <AlertDialogDescription>
                        Are you sure you want to remove <strong>{member?.name}</strong> from your
                        team? They will lose access to all team resources and will need to be
                        invited again to rejoin.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel disabled={isSubmitting}>Cancel</AlertDialogCancel>
                    <AlertDialogAction
                        onClick={handleRemove}
                        disabled={isSubmitting}
                        className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                    >
                        {isSubmitting ? 'Removing...' : 'Remove Member'}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}
