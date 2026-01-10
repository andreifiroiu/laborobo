import { useForm } from '@inertiajs/react';
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
import type { Party, Contact } from '@/types/directory';
import { useState } from 'react';

interface PartyFormProps {
    party?: Party;
    contacts: Contact[];
    onClose: () => void;
}

export function PartyForm({ party, contacts, onClose }: PartyFormProps) {
    const [tagsInput, setTagsInput] = useState(party?.tags?.join(', ') || '');

    const form = useForm({
        name: party?.name || '',
        type: party?.type || 'client',
        email: party?.email || '',
        phone: party?.phone || '',
        website: party?.website || '',
        address: party?.address || '',
        notes: party?.notes || '',
        tags: party?.tags || [],
        status: party?.status || 'active',
        primaryContactId: party?.primaryContactId || '',
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
            primaryContactId: form.data.primaryContactId || null,
        };

        if (party) {
            form.put(`/directory/parties/${party.id}`, {
                data,
                preserveScroll: true,
                onSuccess: () => onClose(),
            });
        } else {
            form.post('/directory/parties', {
                data,
                preserveScroll: true,
                onSuccess: () => onClose(),
            });
        }
    };

    // Filter contacts to only show those belonging to this party (when editing)
    const partyContacts = party
        ? contacts.filter((c) => c.partyId === party.id)
        : [];

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
                    placeholder="Acme Corporation"
                    autoFocus
                />
                <InputError message={form.errors.name} />
            </div>

            {/* Type */}
            <div className="space-y-2">
                <Label htmlFor="type">
                    Type <span className="text-red-500">*</span>
                </Label>
                <Select
                    value={form.data.type}
                    onValueChange={(value) => form.setData('type', value)}
                >
                    <SelectTrigger>
                        <SelectValue placeholder="Select type" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="client">Client</SelectItem>
                        <SelectItem value="vendor">Vendor</SelectItem>
                        <SelectItem value="partner">Partner</SelectItem>
                        <SelectItem value="internal-department">Internal Department</SelectItem>
                    </SelectContent>
                </Select>
                <InputError message={form.errors.type} />
            </div>

            {/* Email */}
            <div className="space-y-2">
                <Label htmlFor="email">Email</Label>
                <Input
                    id="email"
                    type="email"
                    value={form.data.email}
                    onChange={(e) => form.setData('email', e.target.value)}
                    placeholder="contact@acme.com"
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

            {/* Website */}
            <div className="space-y-2">
                <Label htmlFor="website">Website</Label>
                <Input
                    id="website"
                    type="url"
                    value={form.data.website}
                    onChange={(e) => form.setData('website', e.target.value)}
                    placeholder="https://acme.com"
                />
                <InputError message={form.errors.website} />
            </div>

            {/* Address */}
            <div className="space-y-2">
                <Label htmlFor="address">Address</Label>
                <Input
                    id="address"
                    value={form.data.address}
                    onChange={(e) => form.setData('address', e.target.value)}
                    placeholder="123 Main St, City, State, 12345"
                />
                <InputError message={form.errors.address} />
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
                    placeholder="important, vip, partner (comma-separated)"
                />
                <p className="text-xs text-muted-foreground">
                    Enter tags separated by commas
                </p>
                <InputError message={form.errors.tags} />
            </div>

            {/* Primary Contact */}
            {party && partyContacts.length > 0 && (
                <div className="space-y-2">
                    <Label htmlFor="primaryContactId">Primary Contact</Label>
                    <Select
                        value={form.data.primaryContactId || ''}
                        onValueChange={(value) => form.setData('primaryContactId', value)}
                    >
                        <SelectTrigger>
                            <SelectValue placeholder="Select primary contact" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">None</SelectItem>
                            {partyContacts.map((contact) => (
                                <SelectItem key={contact.id} value={contact.id}>
                                    {contact.name} - {contact.email}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={form.errors.primaryContactId} />
                </div>
            )}

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

            {/* Actions */}
            <div className="flex gap-2 pt-4">
                <Button type="button" variant="outline" onClick={onClose}>
                    Cancel
                </Button>
                <Button type="submit" disabled={form.processing}>
                    {party ? 'Update Party' : 'Create Party'}
                </Button>
            </div>
        </form>
    );
}
