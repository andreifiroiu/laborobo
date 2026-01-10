import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import InputError from '@/components/input-error';
import type { Contact, Party } from '@/types/directory';

interface ContactFormProps {
    contact?: Contact;
    parties: Party[];
    onClose: () => void;
}

export function ContactForm({ contact, parties, onClose }: ContactFormProps) {
    const [tagsInput, setTagsInput] = useState(contact?.tags?.join(', ') || '');
    const [setPrimaryContact, setSetPrimaryContact] = useState(false);

    const form = useForm({
        name: contact?.name || '',
        email: contact?.email || '',
        phone: contact?.phone || '',
        partyId: contact?.partyId || '',
        title: contact?.title || '',
        role: contact?.role || '',
        engagementType: contact?.engagementType || 'requester',
        communicationPreference: contact?.communicationPreference || 'email',
        timezone: contact?.timezone || '',
        notes: contact?.notes || '',
        tags: contact?.tags || [],
        status: contact?.status || 'active',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        // Parse tags from comma-separated input
        const tags = tagsInput
            .split(',')
            .map((t) => t.trim())
            .filter((t) => t.length > 0);

        const data = {
            ...form.data,
            tags,
            setPrimaryContact,
        };

        if (contact) {
            form.put(`/directory/contacts/${contact.id}`, {
                data,
                preserveScroll: true,
                onSuccess: () => onClose(),
            });
        } else {
            form.post('/directory/contacts', {
                data,
                preserveScroll: true,
                onSuccess: () => onClose(),
            });
        }
    };

    return (
        <form onSubmit={handleSubmit} className="space-y-6">
            {/* Name */}
            <div className="space-y-2">
                <Label htmlFor="name">
                    Name <span className="text-red-500">*</span>
                </Label>
                <Input
                    id="name"
                    value={form.data.name}
                    onChange={(e) => form.setData('name', e.target.value)}
                    placeholder="John Doe"
                    autoFocus
                />
                <InputError message={form.errors.name} />
            </div>

            {/* Email */}
            <div className="space-y-2">
                <Label htmlFor="email">
                    Email <span className="text-red-500">*</span>
                </Label>
                <Input
                    id="email"
                    type="email"
                    value={form.data.email}
                    onChange={(e) => form.setData('email', e.target.value)}
                    placeholder="john@example.com"
                />
                <InputError message={form.errors.email} />
            </div>

            {/* Phone */}
            <div className="space-y-2">
                <Label htmlFor="phone">Phone</Label>
                <Input
                    id="phone"
                    type="tel"
                    value={form.data.phone}
                    onChange={(e) => form.setData('phone', e.target.value)}
                    placeholder="+1 (555) 123-4567"
                />
                <InputError message={form.errors.phone} />
            </div>

            {/* Party */}
            <div className="space-y-2">
                <Label htmlFor="partyId">
                    Party / Organization <span className="text-red-500">*</span>
                </Label>
                <Select
                    value={form.data.partyId}
                    onValueChange={(value) => form.setData('partyId', value)}
                >
                    <SelectTrigger>
                        <SelectValue placeholder="Select party" />
                    </SelectTrigger>
                    <SelectContent>
                        {parties.map((party) => (
                            <SelectItem key={party.id} value={party.id}>
                                {party.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={form.errors.partyId} />
            </div>

            {/* Title */}
            <div className="space-y-2">
                <Label htmlFor="title">Title</Label>
                <Input
                    id="title"
                    value={form.data.title}
                    onChange={(e) => form.setData('title', e.target.value)}
                    placeholder="Project Manager"
                />
                <InputError message={form.errors.title} />
            </div>

            {/* Role */}
            <div className="space-y-2">
                <Label htmlFor="role">Role</Label>
                <Input
                    id="role"
                    value={form.data.role}
                    onChange={(e) => form.setData('role', e.target.value)}
                    placeholder="Technical Lead"
                />
                <InputError message={form.errors.role} />
            </div>

            {/* Engagement Type */}
            <div className="space-y-2">
                <Label htmlFor="engagementType">
                    Engagement Type <span className="text-red-500">*</span>
                </Label>
                <Select
                    value={form.data.engagementType}
                    onValueChange={(value) => form.setData('engagementType', value)}
                >
                    <SelectTrigger>
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="requester">Requester</SelectItem>
                        <SelectItem value="approver">Approver</SelectItem>
                        <SelectItem value="stakeholder">Stakeholder</SelectItem>
                        <SelectItem value="billing">Billing</SelectItem>
                    </SelectContent>
                </Select>
                <InputError message={form.errors.engagementType} />
            </div>

            {/* Communication Preference */}
            <div className="space-y-2">
                <Label htmlFor="communicationPreference">
                    Communication Preference <span className="text-red-500">*</span>
                </Label>
                <Select
                    value={form.data.communicationPreference}
                    onValueChange={(value) => form.setData('communicationPreference', value)}
                >
                    <SelectTrigger>
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="email">Email</SelectItem>
                        <SelectItem value="phone">Phone</SelectItem>
                        <SelectItem value="slack">Slack</SelectItem>
                    </SelectContent>
                </Select>
                <InputError message={form.errors.communicationPreference} />
            </div>

            {/* Timezone */}
            <div className="space-y-2">
                <Label htmlFor="timezone">Timezone</Label>
                <Input
                    id="timezone"
                    value={form.data.timezone}
                    onChange={(e) => form.setData('timezone', e.target.value)}
                    placeholder="America/New_York"
                />
                <InputError message={form.errors.timezone} />
            </div>

            {/* Notes */}
            <div className="space-y-2">
                <Label htmlFor="notes">Notes</Label>
                <textarea
                    id="notes"
                    value={form.data.notes}
                    onChange={(e) => form.setData('notes', e.target.value)}
                    placeholder="Additional notes..."
                    rows={4}
                    className="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                />
                <InputError message={form.errors.notes} />
            </div>

            {/* Tags */}
            <div className="space-y-2">
                <Label htmlFor="tags">Tags</Label>
                <Input
                    id="tags"
                    value={tagsInput}
                    onChange={(e) => setTagsInput(e.target.value)}
                    placeholder="vip, technical, decision-maker (comma-separated)"
                />
                <p className="text-xs text-muted-foreground">Enter tags separated by commas</p>
                <InputError message={form.errors.tags} />
            </div>

            {/* Status */}
            <div className="space-y-2">
                <Label htmlFor="status">Status</Label>
                <Select
                    value={form.data.status}
                    onValueChange={(value) => form.setData('status', value)}
                >
                    <SelectTrigger>
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="active">Active</SelectItem>
                        <SelectItem value="inactive">Inactive</SelectItem>
                    </SelectContent>
                </Select>
                <InputError message={form.errors.status} />
            </div>

            {/* Set as Primary Contact */}
            {form.data.partyId && (
                <div className="flex items-center space-x-2">
                    <Checkbox
                        id="setPrimaryContact"
                        checked={setPrimaryContact}
                        onCheckedChange={(checked) =>
                            setSetPrimaryContact(checked === true)
                        }
                    />
                    <Label
                        htmlFor="setPrimaryContact"
                        className="cursor-pointer text-sm font-normal"
                    >
                        Set as primary contact for this party
                    </Label>
                </div>
            )}

            {/* Actions */}
            <div className="flex gap-2 pt-4">
                <Button type="button" variant="outline" onClick={onClose}>
                    Cancel
                </Button>
                <Button type="submit" disabled={form.processing}>
                    {contact ? 'Update Contact' : 'Create Contact'}
                </Button>
            </div>
        </form>
    );
}
