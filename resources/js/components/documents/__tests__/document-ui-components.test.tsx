import { fireEvent, render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import { FilePreview, OFFICE_MIME_TYPES } from '../../work/file-preview';
import { FolderTree } from '../folder-tree';
import { FileUploader } from '../../work/file-uploader';

describe('FilePreview - Office Document Support', () => {
    it('renders Office document iframe embed for Word documents', () => {
        render(
            <FilePreview
                fileUrl="https://example.com/document.docx"
                mimeType="application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                fileName="document.docx"
            />
        );

        const preview = screen.getByTestId('file-preview-office');
        expect(preview).toBeInTheDocument();

        const iframe = preview.querySelector('iframe');
        expect(iframe).toBeInTheDocument();
        expect(iframe?.src).toContain('view.officeapps.live.com');
        expect(iframe?.src).toContain(encodeURIComponent('https://example.com/document.docx'));
    });

    it('renders Office document iframe embed for Excel documents', () => {
        render(
            <FilePreview
                fileUrl="https://example.com/spreadsheet.xlsx"
                mimeType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                fileName="spreadsheet.xlsx"
            />
        );

        const preview = screen.getByTestId('file-preview-office');
        expect(preview).toBeInTheDocument();

        const iframe = preview.querySelector('iframe');
        expect(iframe).toBeInTheDocument();
        expect(iframe?.src).toContain('view.officeapps.live.com');
    });

    it('detects Office documents via OFFICE_MIME_TYPES constant', () => {
        // Verify the constant contains expected MIME types
        expect(OFFICE_MIME_TYPES).toContain('application/msword');
        expect(OFFICE_MIME_TYPES).toContain('application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        expect(OFFICE_MIME_TYPES).toContain('application/vnd.ms-excel');
        expect(OFFICE_MIME_TYPES).toContain('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        expect(OFFICE_MIME_TYPES).toContain('application/vnd.ms-powerpoint');
        expect(OFFICE_MIME_TYPES).toContain('application/vnd.openxmlformats-officedocument.presentationml.presentation');
    });

    it('shows fallback download button for unsupported formats', () => {
        render(
            <FilePreview
                fileUrl="/test-file.xyz"
                mimeType="application/unknown"
                fileName="test-file.xyz"
            />
        );

        const preview = screen.getByTestId('file-preview-fallback');
        expect(preview).toBeInTheDocument();

        expect(screen.getByText('Preview not available for this file type')).toBeInTheDocument();
        expect(screen.getByRole('link', { name: /download/i })).toBeInTheDocument();
    });
});

describe('FolderTree - Expand/Collapse Navigation', () => {
    const mockFolders = [
        {
            id: '1',
            name: 'Documents',
            projectId: null,
            parentId: null,
            depth: 1,
            canHaveChildren: true,
            documentsCount: 5,
            children: [
                {
                    id: '2',
                    name: 'Reports',
                    projectId: null,
                    parentId: '1',
                    depth: 2,
                    canHaveChildren: true,
                    documentsCount: 3,
                    children: [],
                },
            ],
        },
        {
            id: '3',
            name: 'Archives',
            projectId: null,
            parentId: null,
            depth: 1,
            canHaveChildren: true,
            documentsCount: 10,
            children: [],
        },
    ];

    it('displays hierarchical folder structure', () => {
        render(
            <FolderTree
                folders={mockFolders}
                selectedFolderId={null}
                onSelectFolder={vi.fn()}
            />
        );

        expect(screen.getByText('Documents')).toBeInTheDocument();
        expect(screen.getByText('Archives')).toBeInTheDocument();
    });

    it('expands and collapses folders on click', async () => {
        const user = userEvent.setup();
        render(
            <FolderTree
                folders={mockFolders}
                selectedFolderId={null}
                onSelectFolder={vi.fn()}
            />
        );

        // Find the expand button for "Documents" folder
        const documentsItem = screen.getByText('Documents').closest('[data-testid="folder-item"]') as HTMLElement;
        expect(documentsItem).toBeInTheDocument();

        const expandButton = within(documentsItem).getByRole('button', { name: /expand/i });

        // Child folder should not be visible initially (collapsed by default)
        expect(screen.queryByText('Reports')).not.toBeInTheDocument();

        // Expand the folder
        await user.click(expandButton);

        // Child folder should now be visible
        expect(screen.getByText('Reports')).toBeInTheDocument();

        // Collapse the folder
        await user.click(expandButton);

        // Child folder should be hidden again
        expect(screen.queryByText('Reports')).not.toBeInTheDocument();
    });

    it('calls onSelectFolder when folder is selected', async () => {
        const onSelectFolder = vi.fn();
        const user = userEvent.setup();

        render(
            <FolderTree
                folders={mockFolders}
                selectedFolderId={null}
                onSelectFolder={onSelectFolder}
            />
        );

        const archivesFolder = screen.getByText('Archives');
        await user.click(archivesFolder);

        expect(onSelectFolder).toHaveBeenCalledWith('3');
    });
});

describe('FileUploader - Folder Selection', () => {
    it('shows folder dropdown after file is selected when folders are provided', async () => {
        const mockFolders = [
            { id: '1', name: 'Documents' },
            { id: '2', name: 'Images' },
        ];

        render(
            <FileUploader
                onUpload={vi.fn()}
                isUploading={false}
                folders={mockFolders}
            />
        );

        // Folder selector should NOT be visible before file selection
        expect(screen.queryByLabelText(/folder/i)).not.toBeInTheDocument();

        // Select a file
        const dropzone = screen.getByTestId('file-uploader-dropzone');
        const input = dropzone.querySelector('input[type="file"]') as HTMLInputElement;
        const file = new File(['test content'], 'test.pdf', { type: 'application/pdf' });
        fireEvent.change(input, { target: { files: [file] } });

        // Now folder selector should be visible (check for the label)
        expect(screen.getByText('Folder (optional)')).toBeInTheDocument();
        // And the combobox trigger should be present
        expect(screen.getByRole('combobox')).toBeInTheDocument();
    });

    it('maintains existing drag-and-drop behavior', () => {
        render(
            <FileUploader
                onUpload={vi.fn()}
                isUploading={false}
            />
        );

        const dropzone = screen.getByTestId('file-uploader-dropzone');
        expect(dropzone).toBeInTheDocument();
        expect(screen.getByText('Drop a file here or click to browse')).toBeInTheDocument();
    });

    it('uploads file without folder when no folder is selected', async () => {
        const user = userEvent.setup();
        const onUpload = vi.fn();
        const mockFolders = [
            { id: '1', name: 'Documents' },
            { id: '2', name: 'Images' },
        ];

        render(
            <FileUploader
                onUpload={onUpload}
                isUploading={false}
                folders={mockFolders}
            />
        );

        // Simulate file selection
        const dropzone = screen.getByTestId('file-uploader-dropzone');
        const input = dropzone.querySelector('input[type="file"]') as HTMLInputElement;
        const file = new File(['test content'], 'test.pdf', { type: 'application/pdf' });
        fireEvent.change(input, { target: { files: [file] } });

        // Upload the file without selecting a folder
        const uploadButton = screen.getByRole('button', { name: /upload file/i });
        await user.click(uploadButton);

        // Verify onUpload was called with undefined folder ID (default: no folder)
        expect(onUpload).toHaveBeenCalledWith(
            expect.any(File),
            undefined, // no notes
            undefined // no folder ID (default)
        );
    });
});
