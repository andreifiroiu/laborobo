import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { ContactDetail } from './contact-detail';
import type { Contact, Party } from '@/types/directory';

interface ContactDetailPanelProps {
    contact: Contact;
    party?: Party;
    onClose: () => void;
    onEdit: (contact: Contact) => void;
}

export function ContactDetailPanel({
    contact,
    party,
    onClose,
    onEdit,
}: ContactDetailPanelProps) {
    return (
        <Sheet open={true} onOpenChange={(open) => !open && onClose()}>
            <SheetContent side="right" className="w-full overflow-y-auto p-0 sm:max-w-2xl">
                <div className="p-6">
                    <SheetHeader className="p-0">
                        <SheetTitle>Contact Details</SheetTitle>
                    </SheetHeader>
                </div>
                <div className="px-6 pb-6">
                    <ContactDetail contact={contact} party={party} onEdit={onEdit} />
                </div>
            </SheetContent>
        </Sheet>
    );
}
