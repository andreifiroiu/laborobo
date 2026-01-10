import {
    Building2,
    Mail,
    Phone,
    User,
    Tag as TagIcon,
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Card } from '@/components/ui/card';
import type { Party } from '@/types/directory';

interface PartiesListProps {
    parties: Party[];
    onPartyClick: (partyId: string) => void;
    searchQuery: string;
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

export function PartiesList({ parties, onPartyClick, searchQuery }: PartiesListProps) {
    if (parties.length === 0) {
        return (
            <div className="flex h-[50vh] items-center justify-center">
                <div className="text-center">
                    <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-muted">
                        <Building2 className="h-8 w-8 text-muted-foreground" />
                    </div>
                    <h3 className="mb-2 text-lg font-semibold text-foreground">
                        {searchQuery ? 'No parties found' : 'No parties yet'}
                    </h3>
                    <p className="mb-6 text-sm text-muted-foreground">
                        {searchQuery
                            ? 'Try adjusting your search query'
                            : 'Add your first client, vendor, or partner to get started'}
                    </p>
                </div>
            </div>
        );
    }

    return (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {parties.map((party) => (
                <Card
                    key={party.id}
                    className="cursor-pointer p-4 transition-colors hover:bg-accent"
                    onClick={() => onPartyClick(party.id)}
                >
                    {/* Header */}
                    <div className="mb-3 flex items-start justify-between">
                        <div className="flex-1">
                            <h3 className="mb-1 font-semibold text-foreground">{party.name}</h3>
                            <div className="flex flex-wrap gap-2">
                                <Badge
                                    variant="outline"
                                    className={partyTypeColors[party.type] || ''}
                                >
                                    {partyTypeLabels[party.type] || party.type}
                                </Badge>
                                <Badge
                                    variant={party.status === 'active' ? 'default' : 'secondary'}
                                >
                                    {party.status}
                                </Badge>
                            </div>
                        </div>
                    </div>

                    {/* Contact Info */}
                    <div className="space-y-2 text-sm">
                        {party.primaryContactName && (
                            <div className="flex items-center gap-2 text-muted-foreground">
                                <User className="h-4 w-4 shrink-0" />
                                <span className="truncate">{party.primaryContactName}</span>
                            </div>
                        )}
                        {party.email && (
                            <div className="flex items-center gap-2 text-muted-foreground">
                                <Mail className="h-4 w-4 shrink-0" />
                                <span className="truncate">{party.email}</span>
                            </div>
                        )}
                        {party.phone && (
                            <div className="flex items-center gap-2 text-muted-foreground">
                                <Phone className="h-4 w-4 shrink-0" />
                                <span className="truncate">{party.phone}</span>
                            </div>
                        )}
                    </div>

                    {/* Tags */}
                    {party.tags && party.tags.length > 0 && (
                        <div className="mt-3 flex items-center gap-2">
                            <TagIcon className="h-3 w-3 shrink-0 text-muted-foreground" />
                            <div className="flex flex-wrap gap-1">
                                {party.tags.slice(0, 3).map((tag) => (
                                    <Badge key={tag} variant="secondary" className="text-xs">
                                        {tag}
                                    </Badge>
                                ))}
                                {party.tags.length > 3 && (
                                    <Badge variant="secondary" className="text-xs">
                                        +{party.tags.length - 3}
                                    </Badge>
                                )}
                            </div>
                        </div>
                    )}
                </Card>
            ))}
        </div>
    );
}
