import { cn } from '@/lib/utils';
import { Upload, X, File, FolderPlus } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Progress } from '@/components/ui/progress';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useRef, useState, useCallback } from 'react';

/**
 * Folder option for the folder selector dropdown.
 */
export interface FolderOption {
    id: string;
    name: string;
}

interface FileUploaderProps {
    onUpload: (file: File, notes?: string, folderId?: string) => void;
    isUploading: boolean;
    progress?: number;
    maxSizeMB?: number;
    error?: string;
    className?: string;
    /** Optional list of folders for folder selection during upload. */
    folders?: FolderOption[];
    /** Callback for creating a new folder inline. */
    onCreateFolder?: (name: string) => Promise<FolderOption>;
}

const BLOCKED_EXTENSIONS = [
    '.exe', '.bat', '.cmd', '.com', '.msi', '.dll', '.scr',
    '.vbs', '.vbe', '.js', '.jse', '.ws', '.wsf',
    '.ps1', '.ps1xml', '.psc1', '.psd1', '.psm1',
    '.sh', '.bash', '.zsh', '.csh', '.ksh',
    '.app', '.dmg', '.deb', '.rpm', '.jar',
];

const ACCEPTED_TYPES_HINT = 'PDF, Documents, Images, Videos, Archives (max 50MB)';

export function FileUploader({
    onUpload,
    isUploading,
    progress = 0,
    maxSizeMB = 50,
    error,
    className,
    folders,
    onCreateFolder,
}: FileUploaderProps) {
    const fileInputRef = useRef<HTMLInputElement>(null);
    const [isDragging, setIsDragging] = useState(false);
    const [selectedFile, setSelectedFile] = useState<File | null>(null);
    const [notes, setNotes] = useState('');
    const [selectedFolderId, setSelectedFolderId] = useState<string | undefined>(undefined);
    const [validationError, setValidationError] = useState<string | null>(null);
    const [isCreatingFolder, setIsCreatingFolder] = useState(false);
    const [newFolderName, setNewFolderName] = useState('');
    const [folderCreationError, setFolderCreationError] = useState<string | null>(null);

    const validateFile = useCallback(
        (file: File): string | null => {
            const maxSizeBytes = maxSizeMB * 1024 * 1024;
            if (file.size > maxSizeBytes) {
                return `File size exceeds ${maxSizeMB}MB limit`;
            }

            const fileName = file.name.toLowerCase();
            const hasBlockedExtension = BLOCKED_EXTENSIONS.some((ext) =>
                fileName.endsWith(ext)
            );
            if (hasBlockedExtension) {
                return 'This file type is not allowed for security reasons';
            }

            return null;
        },
        [maxSizeMB]
    );

    const handleFileSelect = useCallback(
        (file: File) => {
            const validationResult = validateFile(file);
            if (validationResult) {
                setValidationError(validationResult);
                setSelectedFile(null);
                return;
            }

            setValidationError(null);
            setSelectedFile(file);
        },
        [validateFile]
    );

    const handleDragEnter = useCallback((e: React.DragEvent) => {
        e.preventDefault();
        e.stopPropagation();
        setIsDragging(true);
    }, []);

    const handleDragLeave = useCallback((e: React.DragEvent) => {
        e.preventDefault();
        e.stopPropagation();
        setIsDragging(false);
    }, []);

    const handleDragOver = useCallback((e: React.DragEvent) => {
        e.preventDefault();
        e.stopPropagation();
    }, []);

    const handleDrop = useCallback(
        (e: React.DragEvent) => {
            e.preventDefault();
            e.stopPropagation();
            setIsDragging(false);

            const files = e.dataTransfer.files;
            if (files.length > 0) {
                handleFileSelect(files[0]);
            }
        },
        [handleFileSelect]
    );

    const handleInputChange = useCallback(
        (e: React.ChangeEvent<HTMLInputElement>) => {
            const files = e.target.files;
            if (files && files.length > 0) {
                handleFileSelect(files[0]);
            }
        },
        [handleFileSelect]
    );

    const handleClickUploadZone = useCallback(() => {
        fileInputRef.current?.click();
    }, []);

    const handleClearSelection = useCallback(() => {
        setSelectedFile(null);
        setNotes('');
        setSelectedFolderId(undefined);
        setValidationError(null);
        setIsCreatingFolder(false);
        setNewFolderName('');
        setFolderCreationError(null);
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    }, []);

    const handleSubmit = useCallback(() => {
        if (selectedFile) {
            onUpload(selectedFile, notes.trim() || undefined, selectedFolderId);
        }
    }, [selectedFile, notes, selectedFolderId, onUpload]);

    const handleFolderSelect = useCallback((value: string) => {
        if (value === '__new__') {
            setIsCreatingFolder(true);
            setSelectedFolderId(undefined);
        } else if (value === '__none__') {
            setSelectedFolderId(undefined);
            setIsCreatingFolder(false);
        } else {
            setSelectedFolderId(value);
            setIsCreatingFolder(false);
        }
    }, []);

    const handleCreateFolder = useCallback(async () => {
        if (!newFolderName.trim() || !onCreateFolder) {
            return;
        }

        setFolderCreationError(null);

        try {
            const newFolder = await onCreateFolder(newFolderName.trim());
            setSelectedFolderId(newFolder.id);
            setIsCreatingFolder(false);
            setNewFolderName('');
        } catch {
            setFolderCreationError('Failed to create folder. Please try again.');
        }
    }, [newFolderName, onCreateFolder]);

    const handleCancelFolderCreation = useCallback(() => {
        setIsCreatingFolder(false);
        setNewFolderName('');
        setFolderCreationError(null);
    }, []);

    const displayError = error || validationError;
    const hasFolders = folders && folders.length > 0;

    return (
        <div className={cn('space-y-4', className)}>
            <div
                className={cn(
                    'relative rounded-lg border-2 border-dashed transition-colors',
                    isDragging
                        ? 'border-primary bg-primary/5'
                        : 'border-border hover:border-primary/50',
                    selectedFile && 'border-solid border-primary bg-primary/5',
                    displayError && 'border-destructive'
                )}
                onDragEnter={handleDragEnter}
                onDragLeave={handleDragLeave}
                onDragOver={handleDragOver}
                onDrop={handleDrop}
                data-testid="file-uploader-dropzone"
            >
                <input
                    ref={fileInputRef}
                    type="file"
                    className="hidden"
                    onChange={handleInputChange}
                    disabled={isUploading}
                    aria-label="Select file to upload"
                />

                {selectedFile ? (
                    <div className="flex items-center gap-3 p-4">
                        <File className="h-8 w-8 shrink-0 text-primary" aria-hidden="true" />
                        <div className="min-w-0 flex-1">
                            <p className="truncate font-medium text-foreground">
                                {selectedFile.name}
                            </p>
                            <p className="text-sm text-muted-foreground">
                                {formatFileSize(selectedFile.size)}
                            </p>
                        </div>
                        {!isUploading && (
                            <Button
                                variant="ghost"
                                size="icon"
                                onClick={handleClearSelection}
                                aria-label="Remove selected file"
                            >
                                <X className="h-4 w-4" />
                            </Button>
                        )}
                    </div>
                ) : (
                    <button
                        type="button"
                        onClick={handleClickUploadZone}
                        disabled={isUploading}
                        className="flex w-full flex-col items-center justify-center gap-2 p-8"
                    >
                        <Upload
                            className={cn(
                                'h-10 w-10',
                                isDragging ? 'text-primary' : 'text-muted-foreground'
                            )}
                            aria-hidden="true"
                        />
                        <div className="text-center">
                            <p className="font-medium text-foreground">
                                Drop a file here or click to browse
                            </p>
                            <p className="mt-1 text-sm text-muted-foreground">
                                {ACCEPTED_TYPES_HINT}
                            </p>
                        </div>
                    </button>
                )}
            </div>

            {displayError && (
                <p className="text-sm text-destructive" role="alert">
                    {displayError}
                </p>
            )}

            {isUploading && (
                <div className="space-y-2" data-testid="file-uploader-progress">
                    <div className="flex items-center justify-between text-sm">
                        <span className="text-muted-foreground">Uploading...</span>
                        <span className="font-medium">{Math.round(progress)}%</span>
                    </div>
                    <Progress value={progress} className="h-2" />
                </div>
            )}

            {selectedFile && !isUploading && (
                <div className="space-y-3">
                    {/* Folder selection dropdown */}
                    {(hasFolders || onCreateFolder) && (
                        <div className="space-y-2">
                            <Label htmlFor="folder-select">Folder (optional)</Label>
                            {isCreatingFolder ? (
                                <div className="flex gap-2">
                                    <Input
                                        id="new-folder-name"
                                        value={newFolderName}
                                        onChange={(e) => setNewFolderName(e.target.value)}
                                        placeholder="New folder name..."
                                        className="flex-1"
                                        autoFocus
                                        onKeyDown={(e) => {
                                            if (e.key === 'Enter') {
                                                e.preventDefault();
                                                handleCreateFolder();
                                            } else if (e.key === 'Escape') {
                                                handleCancelFolderCreation();
                                            }
                                        }}
                                    />
                                    <Button
                                        type="button"
                                        size="sm"
                                        onClick={handleCreateFolder}
                                        disabled={!newFolderName.trim()}
                                    >
                                        Create
                                    </Button>
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="outline"
                                        onClick={handleCancelFolderCreation}
                                    >
                                        Cancel
                                    </Button>
                                </div>
                            ) : (
                                <Select
                                    value={selectedFolderId ?? '__none__'}
                                    onValueChange={handleFolderSelect}
                                >
                                    <SelectTrigger id="folder-select" aria-label="Folder">
                                        <SelectValue placeholder="Select a folder..." />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="__none__">No folder (root)</SelectItem>
                                        {folders?.map((folder) => (
                                            <SelectItem key={folder.id} value={folder.id}>
                                                {folder.name}
                                            </SelectItem>
                                        ))}
                                        {onCreateFolder && (
                                            <SelectItem value="__new__">
                                                <span className="flex items-center gap-2">
                                                    <FolderPlus className="h-4 w-4" />
                                                    Create new folder...
                                                </span>
                                            </SelectItem>
                                        )}
                                    </SelectContent>
                                </Select>
                            )}
                            {folderCreationError && (
                                <p className="text-sm text-destructive" role="alert">
                                    {folderCreationError}
                                </p>
                            )}
                        </div>
                    )}

                    <div className="space-y-2">
                        <Label htmlFor="version-notes">Version Notes (optional)</Label>
                        <Textarea
                            id="version-notes"
                            value={notes}
                            onChange={(e) => setNotes(e.target.value)}
                            placeholder="Describe what changed in this version..."
                            rows={3}
                        />
                    </div>
                    <Button onClick={handleSubmit} className="w-full">
                        <Upload className="mr-2 h-4 w-4" aria-hidden="true" />
                        Upload File
                    </Button>
                </div>
            )}
        </div>
    );
}

function formatFileSize(bytes: number): string {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}
