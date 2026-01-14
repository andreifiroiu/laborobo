import type { InboxListProps } from '@/types/inbox';
import { InboxListItem } from './inbox-list-item';
import { Inbox as InboxIcon } from 'lucide-react';
import { Checkbox } from '@/components/ui/checkbox';

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
                <label className="flex items-center gap-3 cursor-pointer">
                    <Checkbox
                        checked={selectedIds.length === items.length && items.length > 0}
                        onCheckedChange={handleSelectAll}
                    />
                    <span className="text-sm font-medium text-foreground">
                        Select all ({items.length} items)
                    </span>
                </label>
            </div>

            {/* Items */}
            <div className="divide-y divide-border">
                {items.map((item) => (
                    <InboxListItem
                        key={item.id}
                        item={item}
                        isSelected={selectedIds.includes(item.id)}
                        onSelect={() => toggleSelection(item.id)}
                        onView={() => onViewItem(item.id)}
                    />
                ))}
            </div>
        </div>
    );
}
