import { CheckSquare, Clock, Archive, X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import type { InboxBulkActionsProps } from '@/types/inbox';
import { useInboxActions } from '@/hooks/use-inbox-actions';

export function InboxBulkActions({
    selectedCount,
    selectedIds,
    onClearSelection,
}: InboxBulkActionsProps) {
    const { bulkAction } = useInboxActions();

    const handleBulkAction = (action: 'approve' | 'defer' | 'archive') => {
        bulkAction({ itemIds: selectedIds, action });
        onClearSelection();
    };

    return (
        <div className="flex items-center gap-2">
            <span className="text-sm text-muted-foreground whitespace-nowrap">
                {selectedCount} selected
            </span>
            <Button
                variant="default"
                size="sm"
                onClick={() => handleBulkAction('approve')}
            >
                <CheckSquare className="w-4 h-4 mr-2" />
                Approve All
            </Button>
            <Button
                variant="outline"
                size="sm"
                onClick={() => handleBulkAction('defer')}
            >
                <Clock className="w-4 h-4 mr-2" />
                Defer
            </Button>
            <Button
                variant="outline"
                size="sm"
                onClick={() => handleBulkAction('archive')}
            >
                <Archive className="w-4 h-4 mr-2" />
                Archive
            </Button>
            <Button
                variant="ghost"
                size="sm"
                onClick={onClearSelection}
            >
                <X className="w-4 h-4" />
            </Button>
        </div>
    );
}
