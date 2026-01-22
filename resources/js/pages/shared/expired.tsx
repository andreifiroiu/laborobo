import { Head } from '@inertiajs/react';
import { SharedDocumentPage } from '@/components/documents/shared-document-page';

interface ExpiredPageProps {
    expiresAt: string;
}

export default function Expired({ expiresAt }: ExpiredPageProps) {
    const formattedDate = new Intl.DateTimeFormat('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(expiresAt));

    return (
        <>
            <Head title="Link Expired" />
            <SharedDocumentPage
                token=""
                error={`This share link expired on ${formattedDate}.`}
            />
        </>
    );
}
