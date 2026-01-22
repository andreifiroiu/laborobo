import { Head } from '@inertiajs/react';
import { SharedDocumentPage } from '@/components/documents/shared-document-page';

export default function NotFound() {
    return (
        <>
            <Head title="Document Not Found" />
            <SharedDocumentPage
                token=""
                error="The document you are looking for could not be found or the share link is invalid."
            />
        </>
    );
}
