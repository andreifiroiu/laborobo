import { Users, Mail, Phone } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import type { Contact } from '@/types/directory';

interface ContactsListProps {
    contacts: Contact[];
    onContactClick: (contactId: string) => void;
    searchQuery: string;
}

const engagementTypeLabels: Record<string, string> = {
    requester: 'Requester',
    approver: 'Approver',
    stakeholder: 'Stakeholder',
    billing: 'Billing',
};

const engagementTypeColors: Record<string, string> = {
    requester: 'bg-blue-500/10 text-blue-700 dark:text-blue-400 border-blue-500/20',
    approver: 'bg-purple-500/10 text-purple-700 dark:text-purple-400 border-purple-500/20',
    stakeholder: 'bg-amber-500/10 text-amber-700 dark:text-amber-400 border-amber-500/20',
    billing: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-500/20',
};

export function ContactsList({ contacts, onContactClick, searchQuery }: ContactsListProps) {
    if (contacts.length === 0) {
        return (
            <div className="flex h-[50vh] items-center justify-center">
                <div className="text-center">
                    <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-muted">
                        <Users className="h-8 w-8 text-muted-foreground" />
                    </div>
                    <h3 className="mb-2 text-lg font-semibold text-foreground">
                        {searchQuery ? 'No contacts found' : 'No contacts yet'}
                    </h3>
                    <p className="mb-6 text-sm text-muted-foreground">
                        {searchQuery
                            ? 'Try adjusting your search query'
                            : 'Add contacts to your parties'}
                    </p>
                </div>
            </div>
        );
    }

    return (
        <div className="overflow-hidden rounded-xl border border-border bg-card">
            {/* Table Header - Hidden on mobile */}
            <div className="hidden border-b border-border bg-muted/50 md:block">
                <div className="grid grid-cols-12 gap-4 px-6 py-3 text-xs font-medium text-muted-foreground">
                    <div className="col-span-3">Name / Title</div>
                    <div className="col-span-2">Party</div>
                    <div className="col-span-2">Role</div>
                    <div className="col-span-2">Engagement</div>
                    <div className="col-span-2">Contact</div>
                    <div className="col-span-1">Status</div>
                </div>
            </div>

            {/* Table Body */}
            <div className="divide-y divide-border">
                {contacts.map((contact) => (
                    <div
                        key={contact.id}
                        onClick={() => onContactClick(contact.id)}
                        className="cursor-pointer transition-colors hover:bg-accent"
                    >
                        {/* Desktop View */}
                        <div className="hidden grid-cols-12 gap-4 px-6 py-4 md:grid">
                            {/* Name / Title */}
                            <div className="col-span-3">
                                <div className="font-medium text-foreground">{contact.name}</div>
                                {contact.title && (
                                    <div className="text-sm text-muted-foreground">
                                        {contact.title}
                                    </div>
                                )}
                            </div>

                            {/* Party */}
                            <div className="col-span-2 flex items-center text-sm text-muted-foreground">
                                {contact.partyName}
                            </div>

                            {/* Role */}
                            <div className="col-span-2 flex items-center text-sm text-muted-foreground">
                                {contact.role || '-'}
                            </div>

                            {/* Engagement */}
                            <div className="col-span-2 flex items-center">
                                <Badge
                                    variant="outline"
                                    className={
                                        engagementTypeColors[contact.engagementType] || ''
                                    }
                                >
                                    {engagementTypeLabels[contact.engagementType] ||
                                        contact.engagementType}
                                </Badge>
                            </div>

                            {/* Contact */}
                            <div className="col-span-2 space-y-1 text-sm">
                                <div className="flex items-center gap-1 text-muted-foreground">
                                    <Mail className="h-3 w-3 shrink-0" />
                                    <span className="truncate">{contact.email}</span>
                                </div>
                                {contact.phone && (
                                    <div className="flex items-center gap-1 text-muted-foreground">
                                        <Phone className="h-3 w-3 shrink-0" />
                                        <span className="truncate">{contact.phone}</span>
                                    </div>
                                )}
                            </div>

                            {/* Status */}
                            <div className="col-span-1 flex items-center">
                                <Badge
                                    variant={contact.status === 'active' ? 'default' : 'secondary'}
                                >
                                    {contact.status}
                                </Badge>
                            </div>
                        </div>

                        {/* Mobile View */}
                        <div className="block space-y-2 p-4 md:hidden">
                            <div className="flex items-start justify-between">
                                <div>
                                    <div className="font-medium text-foreground">
                                        {contact.name}
                                    </div>
                                    {contact.title && (
                                        <div className="text-sm text-muted-foreground">
                                            {contact.title}
                                        </div>
                                    )}
                                </div>
                                <Badge
                                    variant={contact.status === 'active' ? 'default' : 'secondary'}
                                >
                                    {contact.status}
                                </Badge>
                            </div>

                            <div className="text-sm text-muted-foreground">
                                {contact.partyName}
                            </div>

                            <div className="flex flex-wrap gap-2">
                                <Badge
                                    variant="outline"
                                    className={
                                        engagementTypeColors[contact.engagementType] || ''
                                    }
                                >
                                    {engagementTypeLabels[contact.engagementType] ||
                                        contact.engagementType}
                                </Badge>
                                {contact.role && (
                                    <Badge variant="secondary">{contact.role}</Badge>
                                )}
                            </div>

                            <div className="space-y-1 text-sm text-muted-foreground">
                                <div className="flex items-center gap-1">
                                    <Mail className="h-3 w-3 shrink-0" />
                                    <span className="truncate">{contact.email}</span>
                                </div>
                                {contact.phone && (
                                    <div className="flex items-center gap-1">
                                        <Phone className="h-3 w-3 shrink-0" />
                                        <span className="truncate">{contact.phone}</span>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}
