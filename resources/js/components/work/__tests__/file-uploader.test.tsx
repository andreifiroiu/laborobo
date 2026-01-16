import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import { FileUploader } from '../file-uploader';

describe('FileUploader', () => {
    it('shows drag-and-drop zone', () => {
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

    it('shows upload progress bar during upload', () => {
        render(
            <FileUploader
                onUpload={vi.fn()}
                isUploading={true}
                progress={45}
            />
        );

        const progressContainer = screen.getByTestId('file-uploader-progress');
        expect(progressContainer).toBeInTheDocument();
        expect(screen.getByText('Uploading...')).toBeInTheDocument();
        expect(screen.getByText('45%')).toBeInTheDocument();
    });
});
