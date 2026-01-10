import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { PartyDetail } from './party-detail';
import type { Party, Contact, Project } from '@/types/directory';

interface PartyDetailPanelProps {
    party: Party;
    contacts: Contact[];
    projects: Project[];
    onClose: () => void;
    onEdit: (party: Party) => void;
}

export function PartyDetailPanel({
    party,
    contacts,
    projects,
    onClose,
    onEdit,
}: PartyDetailPanelProps) {
    return (
        <Sheet open={true} onOpenChange={(open) => !open && onClose()}>
            <SheetContent side="right" className="w-full overflow-y-auto p-0 sm:max-w-2xl">
                <div className="p-6">
                    <SheetHeader className="p-0">
                        <SheetTitle>Party Details</SheetTitle>
                    </SheetHeader>
                </div>
                <div className="px-6 pb-6">
                    <PartyDetail
                        party={party}
                        contacts={contacts}
                        projects={projects}
                        onEdit={onEdit}
                    />
                </div>
            </SheetContent>
        </Sheet>
    );
}
