import { cn } from '@/lib/utils';
import { Download, File } from 'lucide-react';
import { Button } from '@/components/ui/button';

interface FilePreviewProps {
    fileUrl: string;
    mimeType: string;
    fileName: string;
    className?: string;
    inModal?: boolean;
}

const IMAGE_MIME_TYPES = [
    'image/png',
    'image/jpeg',
    'image/jpg',
    'image/gif',
    'image/svg+xml',
    'image/webp',
];

const VIDEO_MIME_TYPES = ['video/mp4', 'video/webm', 'video/quicktime'];

const PDF_MIME_TYPE = 'application/pdf';

export function FilePreview({
    fileUrl,
    mimeType,
    fileName,
    className,
    inModal = false,
}: FilePreviewProps) {
    const isImage = IMAGE_MIME_TYPES.includes(mimeType);
    const isVideo = VIDEO_MIME_TYPES.includes(mimeType);
    const isPdf = mimeType === PDF_MIME_TYPE;

    if (isImage) {
        return (
            <div
                className={cn(
                    'flex items-center justify-center overflow-hidden rounded-lg bg-muted',
                    inModal ? 'h-full w-full' : 'max-h-96',
                    className
                )}
                data-testid="file-preview-image"
            >
                <img
                    src={fileUrl}
                    alt={fileName}
                    className={cn('max-h-full max-w-full object-contain', inModal && 'h-full w-full')}
                />
            </div>
        );
    }

    if (isPdf) {
        return (
            <div
                className={cn(
                    'overflow-hidden rounded-lg',
                    inModal ? 'h-full w-full' : 'h-96 w-full',
                    className
                )}
                data-testid="file-preview-pdf"
            >
                <embed
                    src={fileUrl}
                    type="application/pdf"
                    className="h-full w-full"
                    title={fileName}
                />
            </div>
        );
    }

    if (isVideo) {
        return (
            <div
                className={cn(
                    'overflow-hidden rounded-lg bg-black',
                    inModal ? 'h-full w-full' : 'max-h-96',
                    className
                )}
                data-testid="file-preview-video"
            >
                <video
                    src={fileUrl}
                    controls
                    className={cn('max-h-full w-full', inModal && 'h-full')}
                    title={fileName}
                >
                    <track kind="captions" />
                    Your browser does not support the video element.
                </video>
            </div>
        );
    }

    return (
        <div
            className={cn(
                'flex flex-col items-center justify-center gap-4 rounded-lg border border-dashed border-border bg-muted p-8',
                className
            )}
            data-testid="file-preview-fallback"
        >
            <File className="h-12 w-12 text-muted-foreground" aria-hidden="true" />
            <div className="text-center">
                <p className="font-medium text-foreground">{fileName}</p>
                <p className="mt-1 text-sm text-muted-foreground">
                    Preview not available for this file type
                </p>
            </div>
            <Button variant="outline" asChild>
                <a href={fileUrl} download={fileName}>
                    <Download className="mr-2 h-4 w-4" aria-hidden="true" />
                    Download
                </a>
            </Button>
        </div>
    );
}
