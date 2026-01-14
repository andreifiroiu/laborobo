import { useState } from 'react';
import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import type { InboxPageProps, InboxTab, InboxItem } from '@/types/inbox';
import { InboxTabs } from '@/components/inbox/inbox-tabs';
import { InboxList } from '@/components/inbox/inbox-list';
import { InboxSidePanel } from '@/components/inbox/inbox-side-panel';
import { InboxSearchBar } from '@/components/inbox/inbox-search-bar';
import { InboxBulkActions } from '@/components/inbox/inbox-bulk-actions';
import { useInboxFilters } from '@/hooks/use-inbox-filters';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Inbox', href: '/inbox' },
];

export default function Inbox({
    inboxItems,
    teamMembers,
    projects,
    workOrders,
}: InboxPageProps) {
    const [selectedTab, setSelectedTab] = useState<InboxTab>('all');
    const [selectedIds, setSelectedIds] = useState<string[]>([]);
    const [selectedItemId, setSelectedItemId] = useState<string | null>(null);
    const [searchQuery, setSearchQuery] = useState('');

    // Filter items by tab and search
    const { filteredItems, counts } = useInboxFilters(inboxItems, selectedTab, searchQuery);

    const selectedItem = selectedItemId
        ? inboxItems.find((item) => item.id === selectedItemId) || null
        : null;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Inbox" />

            <div className="flex h-full flex-1 flex-col">
                {/* Header */}
                <div className="px-6 py-6 border-b border-sidebar-border/70 dark:border-sidebar-border">
                    <h1 className="text-2xl font-bold text-foreground mb-2">Inbox</h1>
                    <p className="text-muted-foreground">
                        Review agent drafts, approvals, flagged items, and mentions
                    </p>
                </div>

                {/* Tabs */}
                <InboxTabs
                    currentTab={selectedTab}
                    counts={counts}
                    onTabChange={setSelectedTab}
                />

                {/* Content */}
                <div className="flex-1 overflow-auto p-6">
                    {/* Search and Bulk Actions */}
                    <div className="mb-6 flex flex-col sm:flex-row gap-4">
                        <InboxSearchBar
                            searchQuery={searchQuery}
                            onSearchChange={setSearchQuery}
                        />

                        {selectedIds.length > 0 && (
                            <InboxBulkActions
                                selectedCount={selectedIds.length}
                                selectedIds={selectedIds}
                                onClearSelection={() => setSelectedIds([])}
                            />
                        )}
                    </div>

                    {/* List */}
                    <InboxList
                        items={filteredItems}
                        selectedIds={selectedIds}
                        onSelectItems={setSelectedIds}
                        onViewItem={setSelectedItemId}
                    />
                </div>

                {/* Side Panel */}
                <InboxSidePanel
                    item={selectedItem}
                    onClose={() => setSelectedItemId(null)}
                />
            </div>
        </AppLayout>
    );
}
