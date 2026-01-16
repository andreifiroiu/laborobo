import { router } from '@inertiajs/react';
import { useState } from 'react';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { FileUploader } from './file-uploader';

interface VersionUploadDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    deliverableId: string;
    onSuccess: () => void;
}

export function VersionUploadDialog({
    open,
    onOpenChange,
    deliverableId,
    onSuccess,
}: VersionUploadDialogProps) {
    const [isUploading, setIsUploading] = useState(false);
    const [progress, setProgress] = useState(0);
    const [error, setError] = useState<string | undefined>();

    const handleUpload = (file: File, notes?: string) => {
        setIsUploading(true);
        setProgress(0);
        setError(undefined);

        const formData = new FormData();
        formData.append('file', file);
        if (notes) {
            formData.append('notes', notes);
        }

        router.post(`/work/deliverables/${deliverableId}/versions`, formData, {
            forceFormData: true,
            preserveScroll: true,
            onProgress: (progressEvent) => {
                if (progressEvent && progressEvent.total) {
                    const percent = (progressEvent.loaded / progressEvent.total) * 100;
                    setProgress(percent);
                }
            },
            onSuccess: () => {
                setIsUploading(false);
                setProgress(100);
                onOpenChange(false);
                onSuccess();
            },
            onError: (errors) => {
                setIsUploading(false);
                setProgress(0);
                const errorMessage =
                    errors.file ||
                    errors.notes ||
                    Object.values(errors)[0] ||
                    'Upload failed. Please try again.';
                setError(errorMessage as string);
            },
        });
    };

    const handleOpenChange = (newOpen: boolean) => {
        if (!isUploading) {
            onOpenChange(newOpen);
            if (!newOpen) {
                setProgress(0);
                setError(undefined);
            }
        }
    };

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent className="max-w-md">
                <DialogHeader>
                    <DialogTitle>Upload New Version</DialogTitle>
                    <DialogDescription>
                        Upload a new version of this deliverable. The new version will
                        automatically become the current version.
                    </DialogDescription>
                </DialogHeader>
                <div className="mt-4">
                    <FileUploader
                        onUpload={handleUpload}
                        isUploading={isUploading}
                        progress={progress}
                        maxSizeMB={50}
                        error={error}
                    />
                </div>
            </DialogContent>
        </Dialog>
    );
}
