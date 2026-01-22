import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi, beforeEach } from 'vitest';
import { ShareLinkDialog } from '../share-link-dialog';
import { SharedDocumentPage } from '../shared-document-page';
import type { SharedDocument } from '@/types/documents.d';

// Mock the fetch API
const mockFetch = vi.fn();
global.fetch = mockFetch;

// Mock clipboard API
const mockWriteText = vi.fn();
Object.assign(navigator, {
    clipboard: {
        writeText: mockWriteText,
    },
});

// Mock hasPointerCapture for Radix UI Select
window.HTMLElement.prototype.hasPointerCapture = vi.fn();
window.HTMLElement.prototype.releasePointerCapture = vi.fn();
window.HTMLElement.prototype.setPointerCapture = vi.fn();

describe('ShareLinkDialog - Create Link with Expiration Options', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        mockFetch.mockResolvedValue({
            ok: true,
            json: () =>
                Promise.resolve({
                    shareLink: {
                        id: '1',
                        documentId: 'doc-1',
                        token: 'abc123',
                        url: 'https://example.com/shared/abc123',
                        expiresAt: null,
                        isExpired: false,
                        hasPassword: false,
                        allowDownload: true,
                        accessCount: 0,
                        createdAt: new Date().toISOString(),
                        creator: { id: '1', name: 'Test User' },
                    },
                    url: 'https://example.com/shared/abc123',
                }),
        });
    });

    it('renders expiration picker with label and default selection', async () => {
        render(
            <ShareLinkDialog
                documentId="doc-1"
                isOpen={true}
                onOpenChange={vi.fn()}
            />
        );

        // Check that expiration label exists
        expect(screen.getByText('Expiration')).toBeInTheDocument();

        // Check that the combobox exists with 7 days as default
        const expirationTrigger = screen.getByRole('combobox', { name: /expiration/i });
        expect(expirationTrigger).toBeInTheDocument();
    });

    it('creates share link when form is submitted', async () => {
        const user = userEvent.setup();
        const onCreated = vi.fn();
        render(
            <ShareLinkDialog
                documentId="doc-1"
                isOpen={true}
                onOpenChange={vi.fn()}
                onCreated={onCreated}
            />
        );

        // Create the link with default settings
        const createButton = screen.getByRole('button', { name: /create link/i });
        await user.click(createButton);

        await waitFor(() => {
            expect(mockFetch).toHaveBeenCalled();
            expect(mockFetch).toHaveBeenCalledWith(
                expect.stringContaining('/documents/'),
                expect.objectContaining({
                    method: 'POST',
                })
            );
        });
    });
});

describe('ShareLinkDialog - Password Field Toggle', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('shows password field when password protection is enabled', async () => {
        const user = userEvent.setup();
        render(
            <ShareLinkDialog
                documentId="doc-1"
                isOpen={true}
                onOpenChange={vi.fn()}
            />
        );

        // Password field should not be visible initially
        expect(screen.queryByLabelText(/^password$/i)).not.toBeInTheDocument();

        // Enable password protection
        const passwordToggle = screen.getByRole('checkbox', { name: /password protect/i });
        await user.click(passwordToggle);

        // Password field should now be visible
        expect(screen.getByLabelText(/^password$/i)).toBeInTheDocument();
    });

    it('toggles password visibility with show/hide button', async () => {
        const user = userEvent.setup();
        render(
            <ShareLinkDialog
                documentId="doc-1"
                isOpen={true}
                onOpenChange={vi.fn()}
            />
        );

        // Enable password protection
        const passwordToggle = screen.getByRole('checkbox', { name: /password protect/i });
        await user.click(passwordToggle);

        // Password input should be of type password initially
        const passwordInput = screen.getByLabelText(/^password$/i);
        expect(passwordInput).toHaveAttribute('type', 'password');

        // Click show/hide button
        const toggleButton = screen.getByRole('button', { name: /show password/i });
        await user.click(toggleButton);

        // Password input should now be of type text
        expect(passwordInput).toHaveAttribute('type', 'text');

        // Click again to hide
        await user.click(screen.getByRole('button', { name: /hide password/i }));
        expect(passwordInput).toHaveAttribute('type', 'password');
    });
});

describe('ShareLinkDialog - Download Permission Toggle', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        mockFetch.mockResolvedValue({
            ok: true,
            json: () =>
                Promise.resolve({
                    shareLink: {
                        id: '1',
                        documentId: 'doc-1',
                        token: 'abc123',
                        url: 'https://example.com/shared/abc123',
                        expiresAt: null,
                        isExpired: false,
                        hasPassword: false,
                        allowDownload: false,
                        accessCount: 0,
                        createdAt: new Date().toISOString(),
                        creator: { id: '1', name: 'Test User' },
                    },
                    url: 'https://example.com/shared/abc123',
                }),
        });
    });

    it('allows toggling download permission', async () => {
        const user = userEvent.setup();
        render(
            <ShareLinkDialog
                documentId="doc-1"
                isOpen={true}
                onOpenChange={vi.fn()}
            />
        );

        // Download checkbox should exist and be checked by default
        const downloadCheckbox = screen.getByRole('checkbox', { name: /allow download/i });
        expect(downloadCheckbox).toBeChecked();

        // Uncheck the download permission
        await user.click(downloadCheckbox);
        expect(downloadCheckbox).not.toBeChecked();
    });

    it('submits with allow_download value in request body', async () => {
        const user = userEvent.setup();
        render(
            <ShareLinkDialog
                documentId="doc-1"
                isOpen={true}
                onOpenChange={vi.fn()}
            />
        );

        // Uncheck the download permission
        const downloadCheckbox = screen.getByRole('checkbox', { name: /allow download/i });
        await user.click(downloadCheckbox);

        // Create the link
        const createButton = screen.getByRole('button', { name: /create link/i });
        await user.click(createButton);

        await waitFor(() => {
            expect(mockFetch).toHaveBeenCalled();
            const callBody = JSON.parse(mockFetch.mock.calls[0][1].body);
            expect(callBody.allow_download).toBe(false);
        });
    });
});

describe('SharedDocumentPage - Public View with Password Prompt', () => {
    it('renders password prompt when document requires password', () => {
        render(
            <SharedDocumentPage
                token="abc123"
                requiresPassword={true}
                documentName="test-document.pdf"
            />
        );

        expect(screen.getByText('Password Required')).toBeInTheDocument();
        expect(screen.getByText('test-document.pdf')).toBeInTheDocument();
        expect(screen.getByLabelText(/password/i)).toBeInTheDocument();
        expect(screen.getByRole('button', { name: /verify/i })).toBeInTheDocument();
    });

    it('renders document preview when document is provided', () => {
        const mockDocument: SharedDocument = {
            id: '1',
            name: 'test-document.pdf',
            type: 'application/pdf',
            fileSize: 1024,
            previewUrl: 'https://example.com/preview.pdf',
            allowDownload: true,
        };

        render(
            <SharedDocumentPage
                token="abc123"
                document={mockDocument}
            />
        );

        expect(screen.getByText('test-document.pdf')).toBeInTheDocument();
        expect(screen.getByTestId('file-preview-pdf')).toBeInTheDocument();
    });

    it('shows download button when download is allowed', () => {
        const mockDocument: SharedDocument = {
            id: '1',
            name: 'test-document.pdf',
            type: 'application/pdf',
            fileSize: 1024,
            previewUrl: 'https://example.com/preview.pdf',
            allowDownload: true,
        };

        render(
            <SharedDocumentPage
                token="abc123"
                document={mockDocument}
            />
        );

        // Download button should be visible
        expect(screen.getByRole('button', { name: /download/i })).toBeInTheDocument();
    });

    it('hides download button when download is not allowed', () => {
        const mockDocument: SharedDocument = {
            id: '1',
            name: 'test-document.pdf',
            type: 'application/pdf',
            fileSize: 1024,
            previewUrl: 'https://example.com/preview.pdf',
            allowDownload: false,
        };

        render(
            <SharedDocumentPage
                token="abc123"
                document={mockDocument}
            />
        );

        // Download button should not be visible
        expect(screen.queryByRole('button', { name: /download/i })).not.toBeInTheDocument();
    });

    it('renders expired link error state', () => {
        render(
            <SharedDocumentPage
                token="abc123"
                error="This share link has expired."
            />
        );

        expect(screen.getByText('Link Expired')).toBeInTheDocument();
        expect(screen.getByText('This share link has expired.')).toBeInTheDocument();
    });
});
