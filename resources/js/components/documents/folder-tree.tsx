import { cn } from '@/lib/utils';
import { ChevronRight, Folder, FolderOpen } from 'lucide-react';
import { useState, useCallback } from 'react';
import { Button } from '@/components/ui/button';

/**
 * Folder data structure matching the API response format.
 */
export interface FolderNode {
    id: string;
    name: string;
    projectId: string | null;
    parentId: string | null;
    depth: number;
    canHaveChildren: boolean;
    documentsCount: number;
    children: FolderNode[];
}

interface FolderTreeProps {
    folders: FolderNode[];
    selectedFolderId: string | null;
    onSelectFolder: (folderId: string | null) => void;
    className?: string;
    title?: string;
    showRootOption?: boolean;
}

interface FolderItemProps {
    folder: FolderNode;
    selectedFolderId: string | null;
    onSelectFolder: (folderId: string | null) => void;
    level?: number;
}

/**
 * Individual folder item with expand/collapse functionality.
 */
function FolderItem({
    folder,
    selectedFolderId,
    onSelectFolder,
    level = 0,
}: FolderItemProps) {
    const [isExpanded, setIsExpanded] = useState(false);
    const hasChildren = folder.children && folder.children.length > 0;
    const isSelected = selectedFolderId === folder.id;

    const handleToggleExpand = useCallback((e: React.MouseEvent) => {
        e.stopPropagation();
        setIsExpanded((prev) => !prev);
    }, []);

    const handleSelect = useCallback(() => {
        onSelectFolder(folder.id);
    }, [folder.id, onSelectFolder]);

    // Calculate indentation based on depth (max 3 levels as per spec)
    const paddingLeft = level * 16;

    return (
        <div data-testid="folder-item">
            <div
                className={cn(
                    'group flex items-center gap-1 rounded-md px-2 py-1.5 text-sm transition-colors hover:bg-accent',
                    isSelected && 'bg-accent text-accent-foreground'
                )}
                style={{ paddingLeft: `${paddingLeft + 8}px` }}
            >
                {hasChildren ? (
                    <Button
                        variant="ghost"
                        size="icon"
                        className="h-5 w-5 shrink-0 p-0"
                        onClick={handleToggleExpand}
                        aria-label={isExpanded ? 'Collapse folder' : 'Expand folder'}
                        aria-expanded={isExpanded}
                    >
                        <ChevronRight
                            className={cn(
                                'h-4 w-4 transition-transform',
                                isExpanded && 'rotate-90'
                            )}
                        />
                    </Button>
                ) : (
                    <span className="h-5 w-5 shrink-0" aria-hidden="true" />
                )}

                <button
                    type="button"
                    className="flex flex-1 items-center gap-2 truncate text-left"
                    onClick={handleSelect}
                    aria-current={isSelected ? 'page' : undefined}
                >
                    {isExpanded || isSelected ? (
                        <FolderOpen className="h-4 w-4 shrink-0 text-primary" aria-hidden="true" />
                    ) : (
                        <Folder className="h-4 w-4 shrink-0 text-muted-foreground" aria-hidden="true" />
                    )}
                    <span className="truncate">{folder.name}</span>
                    {folder.documentsCount > 0 && (
                        <span className="ml-auto text-xs text-muted-foreground">
                            {folder.documentsCount}
                        </span>
                    )}
                </button>
            </div>

            {hasChildren && isExpanded && (
                <div role="group" aria-label={`${folder.name} subfolders`}>
                    {folder.children.map((child) => (
                        <FolderItem
                            key={child.id}
                            folder={child}
                            selectedFolderId={selectedFolderId}
                            onSelectFolder={onSelectFolder}
                            level={level + 1}
                        />
                    ))}
                </div>
            )}
        </div>
    );
}

/**
 * FolderTree component for sidebar navigation with hierarchical folder structure.
 * Supports expand/collapse, project-scoped and team-scoped folders.
 * Respects the 3-level nesting depth limit.
 *
 * Width follows existing sidebar pattern (280px on desktop).
 */
export function FolderTree({
    folders,
    selectedFolderId,
    onSelectFolder,
    className,
    title = 'Folders',
    showRootOption = true,
}: FolderTreeProps) {
    const handleSelectRoot = useCallback(() => {
        onSelectFolder(null);
    }, [onSelectFolder]);

    return (
        <nav
            className={cn('w-full', className)}
            aria-label="Folder navigation"
            data-testid="folder-tree"
        >
            {title && (
                <h3 className="mb-2 px-2 text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                    {title}
                </h3>
            )}

            <div className="space-y-0.5">
                {showRootOption && (
                    <button
                        type="button"
                        className={cn(
                            'flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-sm transition-colors hover:bg-accent',
                            selectedFolderId === null && 'bg-accent text-accent-foreground'
                        )}
                        onClick={handleSelectRoot}
                        aria-current={selectedFolderId === null ? 'page' : undefined}
                    >
                        <Folder className="h-4 w-4 shrink-0 text-muted-foreground" aria-hidden="true" />
                        <span>All Documents</span>
                    </button>
                )}

                {folders.map((folder) => (
                    <FolderItem
                        key={folder.id}
                        folder={folder}
                        selectedFolderId={selectedFolderId}
                        onSelectFolder={onSelectFolder}
                    />
                ))}

                {folders.length === 0 && (
                    <p className="px-2 py-4 text-center text-sm text-muted-foreground">
                        No folders yet
                    </p>
                )}
            </div>
        </nav>
    );
}
