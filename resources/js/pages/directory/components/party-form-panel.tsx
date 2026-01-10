import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { PartyForm } from './party-form';
import type { Party, Contact } from '@/types/directory';

interface PartyFormPanelProps {
    open: boolean;
    party?: Party;
    contacts: Contact[];
    onClose: () => void;
}

export function PartyFormPanel({ open, party, contacts, onClose }: PartyFormPanelProps) {
    return (
        <Sheet open={open} onOpenChange={(isOpen) => !isOpen && onClose()}>
            <SheetContent side="right" className="w-full overflow-y-auto p-0 sm:max-w-2xl">
                <div className="p-6">
                    <SheetHeader className="p-0">
                        <SheetTitle>{party ? 'Edit Party' : 'New Party'}</SheetTitle>
                    </SheetHeader>
                </div>
                <div className="px-6 pb-6">
                    <PartyForm party={party} contacts={contacts} onClose={onClose} />
                </div>
            </SheetContent>
        </Sheet>
    );
}
