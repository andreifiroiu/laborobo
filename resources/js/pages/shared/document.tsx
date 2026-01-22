import { Head } from '@inertiajs/react';
import { SharedDocumentPage } from '@/components/documents/shared-document-page';
import type { SharedDocument } from '@/types/documents.d';

interface DocumentPageProps {
    document?: SharedDocument;
    token: string;
}

export default function Document({ document, token }: DocumentPageProps) {
    return (
        <>
            <Head title={document?.name || 'Shared Document'} />
            <SharedDocumentPage
                document={document}
                token={token}
            />
        </>
    );
}
