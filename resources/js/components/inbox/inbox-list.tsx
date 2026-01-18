import type { InboxListProps, InboxItem } from '@/types/inbox';
import { InboxListItem } from './inbox-list-item';
import { ApprovalListItem } from './approval-list-item';
import { Inbox as InboxIcon, CheckCircle } from 'lucide-react';
import { Checkbox } from '@/components/ui/checkbox';

/**
 * Returns the appropriate list item component based on item type.
 * Uses ApprovalListItem for approval items with enhanced context display.
 */
function getListItemComponent(item: InboxItem) {
    if (item.type === 'approval') {
        return ApprovalListItem;
    }
    return InboxListItem;
}

export function InboxList({ items, selectedIds, onSelectItems, onViewItem }: InboxListProps) {
    const toggleSelection = (itemId: string) => {
        const newSelection = selectedIds.includes(itemId)
            ? selectedIds.filter((id) => id !== itemId)
            : [...selectedIds, itemId];
        onSelectItems(newSelection);
    };

    const handleSelectAll = () => {
        if (selectedIds.length === items.length) {
            onSelectItems([]);
        } else {
            onSelectItems(items.map((item) => item.id));
        }
    };

    // Count approvals for empty state messaging
    const approvalCount = items.filter((item) => item.type === 'approval').length;

    if (items.length === 0) {
        return (
            <div className="bg-card border border-border rounded-xl p-12 text-center">
                <div className="w-16 h-16 rounded-full bg-muted flex items-center justify-center mx-auto mb-4">
                    <InboxIcon className="w-8 h-8 text-muted-foreground" />
                </div>
                <h3 className="text-lg font-semibold text-foreground mb-2">
                    Inbox is empty
                </h3>
                <p className="text-sm text-muted-foreground">
                    All caught up! No items need your attention right now.
                </p>
            </div>
        );
    }

    return (
        <div className="bg-card border border-border rounded-xl overflow-hidden">
            {/* Select All Header */}
            <div className="px-4 py-3 border-b border-border bg-muted/50">
                <div className="flex items-center justify-between">
                    <label className="flex items-center gap-3 cursor-pointer">
                        <Checkbox
                            checked={selectedIds.length === items.length && items.length > 0}
                            onCheckedChange={handleSelectAll}
                            aria-label="Select all items"
                        />
                        <span className="text-sm font-medium text-foreground">
                            Select all ({items.length} items)
                        </span>
                    </label>

                    {/* Approval count indicator */}
                    {approvalCount > 0 && (
                        <div className="flex items-center gap-1.5 text-xs text-muted-foreground">
                            <CheckCircle className="w-3.5 h-3.5 text-amber-500" aria-hidden="true" />
                            <span>{approvalCount} pending approval{approvalCount !== 1 ? 's' : ''}</span>
                        </div>
                    )}
                </div>
            </div>

            {/* Items */}
            <div className="divide-y divide-border" role="list" aria-label="Inbox items">
                {items.map((item) => {
                    const ListItemComponent = getListItemComponent(item);
                    return (
                        <ListItemComponent
                            key={item.id}
                            item={item}
                            isSelected={selectedIds.includes(item.id)}
                            onSelect={() => toggleSelection(item.id)}
                            onView={() => onViewItem(item.id)}
                        />
                    );
                })}
            </div>
        </div>
    );
}
