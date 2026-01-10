import { router } from '@inertiajs/react';
import {
    Building2,
    Mail,
    Phone,
    Globe,
    MapPin,
    User,
    Users,
    FolderKanban,
    Tag as TagIcon,
    Calendar,
    Edit,
    Trash2,
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import type { Party, Contact, Project } from '@/types/directory';

interface PartyDetailProps {
    party: Party;
    contacts: Contact[];
    projects: Project[];
    onEdit: (party: Party) => void;
}

const partyTypeLabels: Record<string, string> = {
    client: 'Client',
    vendor: 'Vendor',
    partner: 'Partner',
    'internal-department': 'Internal Dept',
};

const partyTypeColors: Record<string, string> = {
    client: 'bg-blue-500/10 text-blue-700 dark:text-blue-400 border-blue-500/20',
    vendor: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-500/20',
    partner: 'bg-purple-500/10 text-purple-700 dark:text-purple-400 border-purple-500/20',
    'internal-department':
        'bg-amber-500/10 text-amber-700 dark:text-amber-400 border-amber-500/20',
};

export function PartyDetail({ party, contacts, projects, onEdit }: PartyDetailProps) {
    const handleDelete = () => {
        if (
            confirm(
                'Are you sure you want to delete this party? This action cannot be undone.'
            )
        ) {
            router.delete(`/directory/parties/${party.id}`, {
                preserveScroll: true,
            });
        }
    };

    return (
        <div className="space-y-6">
            {/* Header */}
            <div>
                <div className="mb-2 flex items-start justify-between">
                    <div className="flex-1">
                        <h2 className="mb-2 text-2xl font-bold text-foreground">{party.name}</h2>
                        <div className="flex flex-wrap gap-2">
                            <Badge
                                variant="outline"
                                className={partyTypeColors[party.type] || ''}
                            >
                                <Building2 className="mr-1 h-3 w-3" />
                                {partyTypeLabels[party.type] || party.type}
                            </Badge>
                            <Badge variant={party.status === 'active' ? 'default' : 'secondary'}>
                                {party.status}
                            </Badge>
                        </div>
                    </div>
                </div>

                <div className="flex gap-2">
                    <Button variant="outline" size="sm" onClick={() => onEdit(party)}>
                        <Edit className="mr-2 h-4 w-4" />
                        Edit
                    </Button>
                    <Button variant="outline" size="sm" onClick={handleDelete}>
                        <Trash2 className="mr-2 h-4 w-4" />
                        Delete
                    </Button>
                </div>
            </div>

            <Separator />

            {/* Contact Information */}
            <div>
                <h3 className="mb-3 text-sm font-semibold uppercase text-muted-foreground">
                    Contact Information
                </h3>
                <div className="space-y-3">
                    {party.email && (
                        <div className="flex items-start gap-3">
                            <Mail className="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground" />
                            <div>
                                <p className="text-sm font-medium text-foreground">Email</p>
                                <a
                                    href={`mailto:${party.email}`}
                                    className="text-sm text-primary hover:underline"
                                >
                                    {party.email}
                                </a>
                            </div>
                        </div>
                    )}
                    {party.phone && (
                        <div className="flex items-start gap-3">
                            <Phone className="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground" />
                            <div>
                                <p className="text-sm font-medium text-foreground">Phone</p>
                                <a
                                    href={`tel:${party.phone}`}
                                    className="text-sm text-primary hover:underline"
                                >
                                    {party.phone}
                                </a>
                            </div>
                        </div>
                    )}
                    {party.website && (
                        <div className="flex items-start gap-3">
                            <Globe className="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground" />
                            <div>
                                <p className="text-sm font-medium text-foreground">Website</p>
                                <a
                                    href={party.website}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="text-sm text-primary hover:underline"
                                >
                                    {party.website}
                                </a>
                            </div>
                        </div>
                    )}
                    {party.address && (
                        <div className="flex items-start gap-3">
                            <MapPin className="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground" />
                            <div>
                                <p className="text-sm font-medium text-foreground">Address</p>
                                <p className="text-sm text-muted-foreground">{party.address}</p>
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {/* Primary Contact */}
            {party.primaryContactName && (
                <>
                    <Separator />
                    <div>
                        <h3 className="mb-3 text-sm font-semibold uppercase text-muted-foreground">
                            Primary Contact
                        </h3>
                        <div className="flex items-start gap-3">
                            <User className="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground" />
                            <div>
                                <p className="text-sm font-medium text-foreground">
                                    {party.primaryContactName}
                                </p>
                                {party.primaryContactEmail && (
                                    <a
                                        href={`mailto:${party.primaryContactEmail}`}
                                        className="text-sm text-primary hover:underline"
                                    >
                                        {party.primaryContactEmail}
                                    </a>
                                )}
                            </div>
                        </div>
                    </div>
                </>
            )}

            {/* Linked Contacts */}
            {contacts.length > 0 && (
                <>
                    <Separator />
                    <div>
                        <h3 className="mb-3 text-sm font-semibold uppercase text-muted-foreground">
                            <Users className="mr-2 inline-block h-4 w-4" />
                            Contacts ({contacts.length})
                        </h3>
                        <div className="space-y-2">
                            {contacts.map((contact) => (
                                <div
                                    key={contact.id}
                                    className="rounded-lg border border-border bg-muted/50 p-3"
                                >
                                    <p className="font-medium text-foreground">{contact.name}</p>
                                    {contact.title && (
                                        <p className="text-sm text-muted-foreground">
                                            {contact.title}
                                        </p>
                                    )}
                                    <p className="text-sm text-muted-foreground">{contact.email}</p>
                                </div>
                            ))}
                        </div>
                    </div>
                </>
            )}

            {/* Linked Projects */}
            {projects.length > 0 && (
                <>
                    <Separator />
                    <div>
                        <h3 className="mb-3 text-sm font-semibold uppercase text-muted-foreground">
                            <FolderKanban className="mr-2 inline-block h-4 w-4" />
                            Projects ({projects.length})
                        </h3>
                        <div className="space-y-2">
                            {projects.map((project) => (
                                <div
                                    key={project.id}
                                    className="cursor-pointer rounded-lg border border-border bg-muted/50 p-3 transition-colors hover:bg-muted"
                                    onClick={() => router.visit(`/work?project=${project.id}`)}
                                >
                                    <p className="font-medium text-foreground">{project.name}</p>
                                </div>
                            ))}
                        </div>
                    </div>
                </>
            )}

            {/* Notes */}
            {party.notes && (
                <>
                    <Separator />
                    <div>
                        <h3 className="mb-3 text-sm font-semibold uppercase text-muted-foreground">
                            Notes
                        </h3>
                        <p className="whitespace-pre-wrap text-sm text-muted-foreground">
                            {party.notes}
                        </p>
                    </div>
                </>
            )}

            {/* Tags */}
            {party.tags && party.tags.length > 0 && (
                <>
                    <Separator />
                    <div>
                        <h3 className="mb-3 text-sm font-semibold uppercase text-muted-foreground">
                            <TagIcon className="mr-2 inline-block h-4 w-4" />
                            Tags
                        </h3>
                        <div className="flex flex-wrap gap-2">
                            {party.tags.map((tag) => (
                                <Badge key={tag} variant="secondary">
                                    {tag}
                                </Badge>
                            ))}
                        </div>
                    </div>
                </>
            )}

            {/* Metadata */}
            <Separator />
            <div>
                <h3 className="mb-3 text-sm font-semibold uppercase text-muted-foreground">
                    Metadata
                </h3>
                <div className="space-y-2 text-sm">
                    <div className="flex items-center gap-2 text-muted-foreground">
                        <Calendar className="h-4 w-4 shrink-0" />
                        <span>
                            Created {new Date(party.createdAt).toLocaleDateString()}
                        </span>
                    </div>
                    {party.lastActivity && (
                        <div className="flex items-center gap-2 text-muted-foreground">
                            <Calendar className="h-4 w-4 shrink-0" />
                            <span>
                                Last activity {new Date(party.lastActivity).toLocaleDateString()}
                            </span>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
