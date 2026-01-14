import { useMemo } from 'react';
import type { InboxItem, InboxTab, InboxCounts } from '@/types/inbox';

export function useInboxFilters(
    items: InboxItem[],
    tab: InboxTab,
    searchQuery: string
) {
    const filteredItems = useMemo(() => {
        let filtered = items;

        // Filter by tab
        if (tab !== 'all') {
            const typeMap: Record<Exclude<InboxTab, 'all'>, InboxItem['type']> = {
                agent_drafts: 'agent_draft',
                approvals: 'approval',
                flagged: 'flag',
                mentions: 'mention',
            };
            filtered = filtered.filter((item) => item.type === typeMap[tab]);
        }

        // Filter by search
        if (searchQuery) {
            const query = searchQuery.toLowerCase();
            filtered = filtered.filter(
                (item) =>
                    item.title.toLowerCase().includes(query) ||
                    item.contentPreview.toLowerCase().includes(query) ||
                    item.sourceName.toLowerCase().includes(query)
            );
        }

        // Sort by urgency and date
        return filtered.sort((a, b) => {
            const urgencyOrder: Record<InboxItem['urgency'], number> = {
                urgent: 0,
                high: 1,
                normal: 2,
            };
            if (urgencyOrder[a.urgency] !== urgencyOrder[b.urgency]) {
                return urgencyOrder[a.urgency] - urgencyOrder[b.urgency];
            }
            return new Date(b.createdAt).getTime() - new Date(a.createdAt).getTime();
        });
    }, [items, tab, searchQuery]);

    const counts: InboxCounts = useMemo(() => {
        return {
            all: items.length,
            agent_drafts: items.filter((i) => i.type === 'agent_draft').length,
            approvals: items.filter((i) => i.type === 'approval').length,
            flagged: items.filter((i) => i.type === 'flag').length,
            mentions: items.filter((i) => i.type === 'mention').length,
        };
    }, [items]);

    return { filteredItems, counts };
}
