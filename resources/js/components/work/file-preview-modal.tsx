import { Download, X } from 'lucide-react';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { FilePreview } from './file-preview';

interface FilePreviewModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    fileUrl: string;
    mimeType: string;
    fileName: string;
}

export function FilePreviewModal({
    open,
    onOpenChange,
    fileUrl,
    mimeType,
    fileName,
}: FilePreviewModalProps) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[90vh] max-w-4xl overflow-hidden p-0">
                <DialogHeader className="flex flex-row items-center justify-between border-b border-border px-6 py-4">
                    <DialogTitle className="truncate pr-4">{fileName}</DialogTitle>
                    <div className="flex shrink-0 items-center gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <a href={fileUrl} download={fileName}>
                                <Download className="mr-2 h-4 w-4" aria-hidden="true" />
                                Download
                            </a>
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => onOpenChange(false)}
                            aria-label="Close preview"
                        >
                            <X className="h-4 w-4" aria-hidden="true" />
                        </Button>
                    </div>
                </DialogHeader>
                <div className="max-h-[calc(90vh-5rem)] overflow-auto p-6">
                    <FilePreview
                        fileUrl={fileUrl}
                        mimeType={mimeType}
                        fileName={fileName}
                        inModal
                        className="min-h-[300px]"
                    />
                </div>
            </DialogContent>
        </Dialog>
    );
}
