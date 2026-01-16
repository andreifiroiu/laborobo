import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { FilePreview } from '../file-preview';

describe('FilePreview', () => {
    it('renders image preview for image mime types', () => {
        render(
            <FilePreview
                fileUrl="/test-image.png"
                mimeType="image/png"
                fileName="test-image.png"
            />
        );

        const preview = screen.getByTestId('file-preview-image');
        expect(preview).toBeInTheDocument();

        const img = screen.getByRole('img');
        expect(img).toHaveAttribute('src', '/test-image.png');
        expect(img).toHaveAttribute('alt', 'test-image.png');
    });

    it('renders PDF embed for pdf mime type', () => {
        render(
            <FilePreview
                fileUrl="/test-document.pdf"
                mimeType="application/pdf"
                fileName="test-document.pdf"
            />
        );

        const preview = screen.getByTestId('file-preview-pdf');
        expect(preview).toBeInTheDocument();

        const embed = preview.querySelector('embed');
        expect(embed).toHaveAttribute('src', '/test-document.pdf');
        expect(embed).toHaveAttribute('type', 'application/pdf');
    });

    it('renders video player for video mime types', () => {
        render(
            <FilePreview
                fileUrl="/test-video.mp4"
                mimeType="video/mp4"
                fileName="test-video.mp4"
            />
        );

        const preview = screen.getByTestId('file-preview-video');
        expect(preview).toBeInTheDocument();

        const video = preview.querySelector('video');
        expect(video).toHaveAttribute('src', '/test-video.mp4');
        expect(video).toHaveAttribute('controls');
    });

    it('renders fallback for unsupported mime types', () => {
        render(
            <FilePreview
                fileUrl="/test-file.zip"
                mimeType="application/zip"
                fileName="test-file.zip"
            />
        );

        const preview = screen.getByTestId('file-preview-fallback');
        expect(preview).toBeInTheDocument();

        expect(screen.getByText('test-file.zip')).toBeInTheDocument();
        expect(screen.getByText('Preview not available for this file type')).toBeInTheDocument();
        expect(screen.getByRole('link', { name: /download/i })).toBeInTheDocument();
    });
});
