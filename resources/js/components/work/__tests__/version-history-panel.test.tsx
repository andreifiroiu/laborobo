import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import { VersionHistoryPanel, DeliverableVersion } from '../version-history-panel';

const mockVersions: DeliverableVersion[] = [
    {
        id: 'version-1',
        versionNumber: 2,
        fileUrl: '/files/v2/document.pdf',
        fileName: 'document-v2.pdf',
        fileSize: '1.5 MB',
        mimeType: 'application/pdf',
        notes: 'Updated formatting',
        uploadedBy: { id: 'user-1', name: 'John Doe' },
        createdAt: '2024-01-15T10:30:00Z',
    },
    {
        id: 'version-2',
        versionNumber: 1,
        fileUrl: '/files/v1/document.pdf',
        fileName: 'document-v1.pdf',
        fileSize: '1.2 MB',
        mimeType: 'application/pdf',
        notes: 'Initial version',
        uploadedBy: { id: 'user-1', name: 'John Doe' },
        createdAt: '2024-01-10T09:00:00Z',
    },
];

describe('VersionHistoryPanel', () => {
    it('displays version list correctly', () => {
        render(
            <VersionHistoryPanel
                deliverableId="deliverable-1"
                versions={mockVersions}
                currentVersionNumber={2}
                onVersionRestore={vi.fn()}
                onVersionDelete={vi.fn()}
            />
        );

        expect(screen.getByText('Version History (2)')).toBeInTheDocument();

        const versionList = screen.getByTestId('version-history-list');
        expect(versionList).toBeInTheDocument();

        expect(screen.getByText('v2')).toBeInTheDocument();
        expect(screen.getByText('v1')).toBeInTheDocument();
        expect(screen.getByText('document-v2.pdf')).toBeInTheDocument();
        expect(screen.getByText('document-v1.pdf')).toBeInTheDocument();
        expect(screen.getByText('1.5 MB')).toBeInTheDocument();
        expect(screen.getByText('1.2 MB')).toBeInTheDocument();

        expect(screen.getByText('Current')).toBeInTheDocument();
    });

    it('displays empty state when no versions', () => {
        render(
            <VersionHistoryPanel
                deliverableId="deliverable-1"
                versions={[]}
                currentVersionNumber={0}
                onVersionRestore={vi.fn()}
                onVersionDelete={vi.fn()}
            />
        );

        expect(screen.getByText('Version History (0)')).toBeInTheDocument();
        expect(screen.getByText('No versions yet')).toBeInTheDocument();
    });
});
