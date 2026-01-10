import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { ContactForm } from './contact-form';
import type { Contact, Party } from '@/types/directory';

interface ContactFormPanelProps {
    open: boolean;
    contact?: Contact;
    parties: Party[];
    onClose: () => void;
}

export function ContactFormPanel({ open, contact, parties, onClose }: ContactFormPanelProps) {
    return (
        <Sheet open={open} onOpenChange={(isOpen) => !isOpen && onClose()}>
            <SheetContent side="right" className="w-full overflow-y-auto p-0 sm:max-w-2xl">
                <div className="p-6">
                    <SheetHeader className="p-0">
                        <SheetTitle>{contact ? 'Edit Contact' : 'New Contact'}</SheetTitle>
                    </SheetHeader>
                </div>
                <div className="px-6 pb-6">
                    <ContactForm contact={contact} parties={parties} onClose={onClose} />
                </div>
            </SheetContent>
        </Sheet>
    );
}
