import { Head } from '@inertiajs/react';
import { SharedDocumentPage } from '@/components/documents/shared-document-page';

interface PasswordRequiredPageProps {
    token: string;
    documentName: string;
    error?: string;
}

export default function PasswordRequired({
    token,
    documentName,
    error,
}: PasswordRequiredPageProps) {
    return (
        <>
            <Head title="Password Required" />
            <SharedDocumentPage
                token={token}
                requiresPassword={true}
                documentName={documentName}
                error={error}
            />
        </>
    );
}
