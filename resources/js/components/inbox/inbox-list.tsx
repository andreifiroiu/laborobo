import { router } from '@inertiajs/react';
import type { InboxListProps, InboxItem } from '@/types/inbox';
import { InboxListItem } from './inbox-list-item';
import { ApprovalListItem } from './approval-list-item';
import { AgentDraftInboxItem } from './AgentDraftInboxItem';
import { Inbox as InboxIcon, CheckCircle, Bot } from 'lucide-react';
import { Checkbox } from '@/components/ui/checkbox';
import type { AIConfidence } from '@/types/pm-copilot.d';
import type { CommunicationType } from '@/types/client-comms.d';

/**
 * Returns the appropriate list item component based on item type.
 * Uses ApprovalListItem for approval items with enhanced context display.
 * Uses AgentDraftInboxItem for agent draft items.
 */
function getListItemComponent(item: InboxItem) {
    if (item.type === 'approval') {
        return ApprovalListItem;
    }
    if (item.type === 'agent_draft') {
        return AgentDraftInboxItem;
    }
    return InboxListItem;
}

/**
 * Adapter to convert InboxItem to AgentDraftInboxItem props format
 */
function adaptToAgentDraftItem(item: InboxItem) {
    return {
        id: item.id,
        title: item.title,
        contentPreview: item.description || '',
        fullContent: item.contentFull || item.description || '',
        communicationType: (item.communicationType || null) as CommunicationType | null,
        confidence: (item.aiConfidence || 'medium') as AIConfidence,
        recipientName: item.recipientName || null,
        recipientEmail: item.recipientEmail || null,
        relatedWorkOrderId: item.relatedWorkOrderId || null,
        relatedWorkOrderTitle: item.relatedWorkOrderTitle || null,
        relatedProjectId: item.relatedProjectId || null,
        relatedProjectName: item.relatedProjectName || null,
        createdAt: item.timestamp,
        waitingHours: item.waitingHours,
    };
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

    // Quick approve draft
    const handleQuickApprove = (itemId: string) => {
        router.post(`/inbox/drafts/${itemId}/approve`, {}, {
            preserveScroll: true,
        });
    };

    // Quick reject draft (shows rejection dialog in side panel)
    const handleQuickReject = (itemId: string) => {
        // Open the item in view mode to enter rejection reason
        onViewItem(itemId);
    };

    // Count approvals and drafts for empty state messaging
    const approvalCount = items.filter((item) => item.type === 'approval').length;
    const draftCount = items.filter((item) => item.type === 'agent_draft').length;

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

                    <div className="flex items-center gap-4">
                        {/* Agent draft count indicator */}
                        {draftCount > 0 && (
                            <div className="flex items-center gap-1.5 text-xs text-muted-foreground">
                                <Bot className="w-3.5 h-3.5 text-blue-500" aria-hidden="true" />
                                <span>{draftCount} draft{draftCount !== 1 ? 's' : ''} to review</span>
                            </div>
                        )}

                        {/* Approval count indicator */}
                        {approvalCount > 0 && (
                            <div className="flex items-center gap-1.5 text-xs text-muted-foreground">
                                <CheckCircle className="w-3.5 h-3.5 text-amber-500" aria-hidden="true" />
                                <span>{approvalCount} pending approval{approvalCount !== 1 ? 's' : ''}</span>
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {/* Items */}
            <div className="divide-y divide-border" role="list" aria-label="Inbox items">
                {items.map((item) => {
                    // Handle agent draft items with custom component
                    if (item.type === 'agent_draft') {
                        const adaptedItem = adaptToAgentDraftItem(item);
                        return (
                            <AgentDraftInboxItem
                                key={item.id}
                                item={adaptedItem}
                                isSelected={selectedIds.includes(item.id)}
                                onSelect={() => toggleSelection(item.id)}
                                onView={() => onViewItem(item.id)}
                                onQuickApprove={() => handleQuickApprove(item.id)}
                                onQuickReject={() => handleQuickReject(item.id)}
                            />
                        );
                    }

                    // Handle other item types
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
