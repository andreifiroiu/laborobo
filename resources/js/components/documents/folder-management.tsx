import { cn } from '@/lib/utils';
import {
    Edit2,
    Folder,
    FolderPlus,
    MoreVertical,
    Trash2,
    Move,
    FileText,
} from 'lucide-react';
import { useState, useCallback } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { FolderNode } from './folder-tree';

interface FolderManagementProps {
    folders: FolderNode[];
    selectedFolder: FolderNode | null;
    onCreateFolder: (name: string, parentId?: string) => Promise<void>;
    onRenameFolder: (folderId: string, name: string) => Promise<void>;
    onDeleteFolder: (folderId: string) => Promise<void>;
    onMoveFolder: (folderId: string, newParentId: string | null) => Promise<void>;
    className?: string;
    isLoading?: boolean;
}

type DialogMode = 'create' | 'rename' | 'move' | null;

/**
 * Flatten folders into a list for the move dropdown, excluding current folder and its descendants.
 */
function flattenFolders(
    folders: FolderNode[],
    excludeId: string | null,
    prefix = ''
): Array<{ id: string; name: string; depth: number }> {
    const result: Array<{ id: string; name: string; depth: number }> = [];

    for (const folder of folders) {
        // Skip if this is the folder we're moving or its descendants
        if (folder.id === excludeId) {
            continue;
        }

        result.push({
            id: folder.id,
            name: prefix + folder.name,
            depth: folder.depth,
        });

        if (folder.children && folder.children.length > 0 && folder.canHaveChildren) {
            result.push(...flattenFolders(folder.children, excludeId, prefix + '  '));
        }
    }

    return result;
}

/**
 * FolderManagement component for CRUD operations on folders.
 * Supports create, rename, delete, and move/reorganization.
 * Displays folder metadata and document count.
 */
export function FolderManagement({
    folders,
    selectedFolder,
    onCreateFolder,
    onRenameFolder,
    onDeleteFolder,
    onMoveFolder,
    className,
    isLoading = false,
}: FolderManagementProps) {
    const [dialogMode, setDialogMode] = useState<DialogMode>(null);
    const [folderName, setFolderName] = useState('');
    const [selectedParentId, setSelectedParentId] = useState<string | null>(null);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);

    const handleOpenCreateDialog = useCallback(() => {
        setFolderName('');
        setSelectedParentId(selectedFolder?.id ?? null);
        setError(null);
        setDialogMode('create');
    }, [selectedFolder]);

    const handleOpenRenameDialog = useCallback(() => {
        if (!selectedFolder) return;
        setFolderName(selectedFolder.name);
        setError(null);
        setDialogMode('rename');
    }, [selectedFolder]);

    const handleOpenMoveDialog = useCallback(() => {
        if (!selectedFolder) return;
        setSelectedParentId(selectedFolder.parentId);
        setError(null);
        setDialogMode('move');
    }, [selectedFolder]);

    const handleCloseDialog = useCallback(() => {
        setDialogMode(null);
        setFolderName('');
        setSelectedParentId(null);
        setError(null);
    }, []);

    const handleCreate = useCallback(async () => {
        if (!folderName.trim()) {
            setError('Folder name is required');
            return;
        }

        setIsSubmitting(true);
        setError(null);

        try {
            await onCreateFolder(
                folderName.trim(),
                selectedParentId ?? undefined
            );
            handleCloseDialog();
        } catch (err) {
            setError(
                err instanceof Error ? err.message : 'Failed to create folder'
            );
        } finally {
            setIsSubmitting(false);
        }
    }, [folderName, selectedParentId, onCreateFolder, handleCloseDialog]);

    const handleRename = useCallback(async () => {
        if (!selectedFolder || !folderName.trim()) {
            setError('Folder name is required');
            return;
        }

        setIsSubmitting(true);
        setError(null);

        try {
            await onRenameFolder(selectedFolder.id, folderName.trim());
            handleCloseDialog();
        } catch (err) {
            setError(
                err instanceof Error ? err.message : 'Failed to rename folder'
            );
        } finally {
            setIsSubmitting(false);
        }
    }, [selectedFolder, folderName, onRenameFolder, handleCloseDialog]);

    const handleMove = useCallback(async () => {
        if (!selectedFolder) return;

        setIsSubmitting(true);
        setError(null);

        try {
            await onMoveFolder(selectedFolder.id, selectedParentId);
            handleCloseDialog();
        } catch (err) {
            setError(
                err instanceof Error ? err.message : 'Failed to move folder'
            );
        } finally {
            setIsSubmitting(false);
        }
    }, [selectedFolder, selectedParentId, onMoveFolder, handleCloseDialog]);

    const handleDelete = useCallback(async () => {
        if (!selectedFolder) return;

        setIsSubmitting(true);

        try {
            await onDeleteFolder(selectedFolder.id);
            setShowDeleteConfirm(false);
        } catch (err) {
            setError(
                err instanceof Error ? err.message : 'Failed to delete folder'
            );
        } finally {
            setIsSubmitting(false);
        }
    }, [selectedFolder, onDeleteFolder]);

    const handleKeyDown = useCallback(
        (e: React.KeyboardEvent) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (dialogMode === 'create') {
                    handleCreate();
                } else if (dialogMode === 'rename') {
                    handleRename();
                }
            }
        },
        [dialogMode, handleCreate, handleRename]
    );

    const availableFolders = flattenFolders(folders, selectedFolder?.id ?? null);
    const canCreateSubfolder = selectedFolder ? selectedFolder.canHaveChildren : true;

    return (
        <div className={cn('space-y-4', className)} data-testid="folder-management">
            {/* Toolbar */}
            <div className="flex items-center justify-between gap-2">
                <h2 className="text-lg font-semibold">Folders</h2>
                <Button
                    variant="outline"
                    size="sm"
                    onClick={handleOpenCreateDialog}
                    disabled={isLoading}
                >
                    <FolderPlus className="mr-2 h-4 w-4" />
                    New Folder
                </Button>
            </div>

            {/* Selected folder info */}
            {selectedFolder && (
                <div
                    className="rounded-lg border bg-card p-4"
                    data-testid="folder-details"
                >
                    <div className="flex items-start justify-between">
                        <div className="flex items-center gap-3">
                            <div className="rounded-lg bg-primary/10 p-2">
                                <Folder className="h-5 w-5 text-primary" />
                            </div>
                            <div>
                                <h3 className="font-medium">
                                    {selectedFolder.name}
                                </h3>
                                <div className="mt-1 flex items-center gap-4 text-sm text-muted-foreground">
                                    <span className="flex items-center gap-1">
                                        <FileText className="h-3.5 w-3.5" />
                                        {selectedFolder.documentsCount} document{selectedFolder.documentsCount !== 1 ? 's' : ''}
                                    </span>
                                    {selectedFolder.depth > 1 && (
                                        <span>
                                            Level {selectedFolder.depth}
                                        </span>
                                    )}
                                </div>
                            </div>
                        </div>

                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="h-8 w-8"
                                    aria-label="Folder actions"
                                >
                                    <MoreVertical className="h-4 w-4" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                {canCreateSubfolder && (
                                    <DropdownMenuItem onClick={handleOpenCreateDialog}>
                                        <FolderPlus className="mr-2 h-4 w-4" />
                                        Create Subfolder
                                    </DropdownMenuItem>
                                )}
                                <DropdownMenuItem onClick={handleOpenRenameDialog}>
                                    <Edit2 className="mr-2 h-4 w-4" />
                                    Rename
                                </DropdownMenuItem>
                                <DropdownMenuItem onClick={handleOpenMoveDialog}>
                                    <Move className="mr-2 h-4 w-4" />
                                    Move
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem
                                    onClick={() => setShowDeleteConfirm(true)}
                                    className="text-destructive focus:text-destructive"
                                >
                                    <Trash2 className="mr-2 h-4 w-4" />
                                    Delete
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </div>
            )}

            {/* Empty state */}
            {!selectedFolder && folders.length === 0 && (
                <div className="flex flex-col items-center justify-center gap-4 rounded-lg border border-dashed p-8 text-center">
                    <Folder className="h-12 w-12 text-muted-foreground" />
                    <div>
                        <p className="font-medium">No folders yet</p>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Create folders to organize your documents
                        </p>
                    </div>
                    <Button onClick={handleOpenCreateDialog}>
                        <FolderPlus className="mr-2 h-4 w-4" />
                        Create First Folder
                    </Button>
                </div>
            )}

            {/* Create/Rename Dialog */}
            <Dialog
                open={dialogMode === 'create' || dialogMode === 'rename'}
                onOpenChange={(open) => !open && handleCloseDialog()}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {dialogMode === 'create'
                                ? 'Create New Folder'
                                : 'Rename Folder'}
                        </DialogTitle>
                        <DialogDescription>
                            {dialogMode === 'create'
                                ? 'Enter a name for your new folder.'
                                : 'Enter a new name for this folder.'}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-4 py-4">
                        <div className="space-y-2">
                            <Label htmlFor="folder-name">Folder Name</Label>
                            <Input
                                id="folder-name"
                                value={folderName}
                                onChange={(e) => setFolderName(e.target.value)}
                                placeholder="Enter folder name..."
                                autoFocus
                                onKeyDown={handleKeyDown}
                            />
                        </div>

                        {dialogMode === 'create' && folders.length > 0 && (
                            <div className="space-y-2">
                                <Label htmlFor="parent-folder">
                                    Parent Folder (optional)
                                </Label>
                                <Select
                                    value={selectedParentId ?? '__root__'}
                                    onValueChange={(value) =>
                                        setSelectedParentId(
                                            value === '__root__' ? null : value
                                        )
                                    }
                                >
                                    <SelectTrigger id="parent-folder">
                                        <SelectValue placeholder="Select parent folder..." />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="__root__">
                                            Root (no parent)
                                        </SelectItem>
                                        {availableFolders
                                            .filter((f) => f.depth < 3)
                                            .map((folder) => (
                                                <SelectItem
                                                    key={folder.id}
                                                    value={folder.id}
                                                >
                                                    {folder.name}
                                                </SelectItem>
                                            ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        )}

                        {error && (
                            <p className="text-sm text-destructive" role="alert">
                                {error}
                            </p>
                        )}
                    </div>

                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={handleCloseDialog}
                            disabled={isSubmitting}
                        >
                            Cancel
                        </Button>
                        <Button
                            onClick={
                                dialogMode === 'create'
                                    ? handleCreate
                                    : handleRename
                            }
                            disabled={isSubmitting || !folderName.trim()}
                        >
                            {isSubmitting
                                ? 'Saving...'
                                : dialogMode === 'create'
                                  ? 'Create Folder'
                                  : 'Rename'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Move Dialog */}
            <Dialog
                open={dialogMode === 'move'}
                onOpenChange={(open) => !open && handleCloseDialog()}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Move Folder</DialogTitle>
                        <DialogDescription>
                            Select a new location for "{selectedFolder?.name}".
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-4 py-4">
                        <div className="space-y-2">
                            <Label htmlFor="move-to-folder">Move To</Label>
                            <Select
                                value={selectedParentId ?? '__root__'}
                                onValueChange={(value) =>
                                    setSelectedParentId(
                                        value === '__root__' ? null : value
                                    )
                                }
                            >
                                <SelectTrigger id="move-to-folder">
                                    <SelectValue placeholder="Select destination..." />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="__root__">
                                        Root (no parent)
                                    </SelectItem>
                                    {availableFolders
                                        .filter((f) => f.depth < 3)
                                        .map((folder) => (
                                            <SelectItem
                                                key={folder.id}
                                                value={folder.id}
                                            >
                                                {folder.name}
                                            </SelectItem>
                                        ))}
                                </SelectContent>
                            </Select>
                        </div>

                        {error && (
                            <p className="text-sm text-destructive" role="alert">
                                {error}
                            </p>
                        )}
                    </div>

                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={handleCloseDialog}
                            disabled={isSubmitting}
                        >
                            Cancel
                        </Button>
                        <Button onClick={handleMove} disabled={isSubmitting}>
                            {isSubmitting ? 'Moving...' : 'Move Folder'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Delete Confirmation */}
            <AlertDialog
                open={showDeleteConfirm}
                onOpenChange={setShowDeleteConfirm}
            >
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Delete Folder</AlertDialogTitle>
                        <AlertDialogDescription>
                            Are you sure you want to delete "{selectedFolder?.name}"?
                            {selectedFolder && selectedFolder.documentsCount > 0 && (
                                <span className="mt-2 block font-medium">
                                    {selectedFolder.documentsCount} document
                                    {selectedFolder.documentsCount !== 1 ? 's' : ''}{' '}
                                    will be moved to the root level.
                                </span>
                            )}
                            This action cannot be undone.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel disabled={isSubmitting}>
                            Cancel
                        </AlertDialogCancel>
                        <AlertDialogAction
                            onClick={handleDelete}
                            disabled={isSubmitting}
                            className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                        >
                            {isSubmitting ? 'Deleting...' : 'Delete'}
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </div>
    );
}
