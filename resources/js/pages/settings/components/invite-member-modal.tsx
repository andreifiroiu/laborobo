import { router } from '@inertiajs/react';
import { useState } from 'react';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import InputError from '@/components/input-error';
import type { TeamRole } from '@/types/settings';

interface InviteMemberModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    roles: TeamRole[];
}

export function InviteMemberModal({ open, onOpenChange, roles }: InviteMemberModalProps) {
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});

    const [email, setEmail] = useState('');
    const [roleId, setRoleId] = useState<string>('');

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);
        setErrors({});

        router.post('/settings/team-members', {
            email,
            role_id: roleId ? parseInt(roleId, 10) : null,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                setIsSubmitting(false);
                setEmail('');
                setRoleId('');
                onOpenChange(false);
            },
            onError: (formErrors) => {
                setIsSubmitting(false);
                setErrors(formErrors as Record<string, string>);
            },
        });
    };

    const handleOpenChange = (newOpen: boolean) => {
        if (!isSubmitting) {
            if (!newOpen) {
                setEmail('');
                setRoleId('');
                setErrors({});
            }
            onOpenChange(newOpen);
        }
    };

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent className="sm:max-w-md">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>Invite Team Member</DialogTitle>
                        <DialogDescription>
                            Send an invitation to join your team. They will receive an email with a
                            link to accept the invitation.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 py-4">
                        <div className="grid gap-2">
                            <Label htmlFor="email">Email Address *</Label>
                            <Input
                                id="email"
                                type="email"
                                value={email}
                                onChange={(e) => setEmail(e.target.value)}
                                placeholder="colleague@example.com"
                                autoComplete="off"
                            />
                            <InputError message={errors.email} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="role">Role *</Label>
                            <Select value={roleId} onValueChange={setRoleId}>
                                <SelectTrigger id="role">
                                    <SelectValue placeholder="Select a role" />
                                </SelectTrigger>
                                <SelectContent>
                                    {roles.map((role) => (
                                        <SelectItem key={role.id} value={role.id.toString()}>
                                            {role.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.role_id} />
                        </div>
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => handleOpenChange(false)}
                            disabled={isSubmitting}
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={isSubmitting || !email || !roleId}>
                            {isSubmitting ? 'Sending...' : 'Send Invitation'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
