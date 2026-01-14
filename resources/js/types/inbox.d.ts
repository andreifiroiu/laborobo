// =============================================================================
// Inbox Section Types
// =============================================================================

import type { TeamMember, Project, WorkOrder } from './work';

export interface InboxItem {
    id: string;
    type: 'agent_draft' | 'approval' | 'flag' | 'mention';
    title: string;
    contentPreview: string;
    fullContent: string;
    sourceId: string;
    sourceName: string;
    sourceType: 'human' | 'ai_agent';
    relatedWorkOrderId: string | null;
    relatedWorkOrderTitle: string | null;
    relatedProjectId: string | null;
    relatedProjectName: string | null;
    urgency: 'urgent' | 'high' | 'normal';
    aiConfidence: 'high' | 'medium' | 'low' | null;
    qaValidation: 'passed' | 'failed' | null;
    createdAt: string;
    waitingHours: number;
}

// =============================================================================
// View Types
// =============================================================================

export type InboxTab = 'all' | 'agent_drafts' | 'approvals' | 'flagged' | 'mentions';

export interface InboxCounts {
    all: number;
    agent_drafts: number;
    approvals: number;
    flagged: number;
    mentions: number;
}

// =============================================================================
// Page Props
// =============================================================================

export interface InboxPageProps {
    inboxItems: InboxItem[];
    teamMembers: TeamMember[];
    projects: Project[];
    workOrders: WorkOrder[];
}

// =============================================================================
// Component Props
// =============================================================================

export interface InboxTabsProps {
    currentTab: InboxTab;
    counts: InboxCounts;
    onTabChange: (tab: InboxTab) => void;
}

export interface InboxSearchBarProps {
    searchQuery: string;
    onSearchChange: (query: string) => void;
}

export interface InboxListProps {
    items: InboxItem[];
    selectedIds: string[];
    onSelectItems: (ids: string[]) => void;
    onViewItem: (id: string) => void;
}

export interface InboxListItemProps {
    item: InboxItem;
    isSelected: boolean;
    onSelect: () => void;
    onView: () => void;
}

export interface InboxSidePanelProps {
    item: InboxItem | null;
    onClose: () => void;
}

export interface InboxBulkActionsProps {
    selectedCount: number;
    selectedIds: string[];
    onClearSelection: () => void;
}

// =============================================================================
// Form Types
// =============================================================================

export interface RejectFeedbackForm {
    feedback: string;
}

// =============================================================================
// Action Types
// =============================================================================

export type InboxAction = 'approve' | 'defer' | 'archive';

export interface BulkActionPayload {
    itemIds: string[];
    action: InboxAction;
}
