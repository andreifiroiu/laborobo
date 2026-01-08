// =============================================================================
// Data Types
// =============================================================================

export interface TeamMember {
  id: string
  name: string
  type: 'human' | 'ai_agent'
  role: string
  avatarUrl: string | null
  skills: string[]
}

export interface Project {
  id: string
  name: string
  description: string
}

export interface WorkOrder {
  id: string
  title: string
  projectId: string
}

export interface InboxItem {
  id: string
  type: 'agent_draft' | 'approval' | 'flag' | 'mention'
  title: string
  contentPreview: string
  fullContent: string
  sourceId: string
  sourceName: string
  sourceType: 'human' | 'ai_agent'
  relatedWorkOrderId: string | null
  relatedWorkOrderTitle: string | null
  relatedProjectId: string | null
  relatedProjectName: string | null
  urgency: 'urgent' | 'high' | 'normal'
  aiConfidence: 'high' | 'medium' | 'low' | null
  qaValidation: 'passed' | 'failed' | null
  createdAt: string
  waitingHours: number
}

// =============================================================================
// View Types
// =============================================================================

export type InboxTab = 'all' | 'agent_drafts' | 'approvals' | 'flagged' | 'mentions'

export interface InboxFilters {
  tab: InboxTab
  search?: string
  urgency?: InboxItem['urgency']
  sourceType?: 'human' | 'ai_agent'
  sortBy?: 'date' | 'urgency' | 'waiting'
}

export interface BulkActionData {
  itemIds: string[]
  action: 'approve' | 'defer' | 'archive'
}

export interface ActionFeedback {
  itemId: string
  feedback: string
}

// =============================================================================
// Component Props
// =============================================================================

export interface InboxProps {
  /** All inbox items */
  inboxItems: InboxItem[]

  /** Team members (humans and AI agents) */
  teamMembers: TeamMember[]

  /** Projects for context */
  projects: Project[]

  /** Work orders for context */
  workOrders: WorkOrder[]

  /** Currently active tab */
  currentTab?: InboxTab

  /** Currently selected item IDs for bulk actions */
  selectedItemIds?: string[]

  /** Called when user switches tabs */
  onTabChange?: (tab: InboxTab) => void

  /** Called when user clicks an item to view details */
  onViewItem?: (itemId: string) => void

  /** Called when user approves an item */
  onApprove?: (itemId: string) => void

  /** Called when user rejects an item with feedback */
  onReject?: (data: ActionFeedback) => void

  /** Called when user wants to edit an item before approving */
  onEdit?: (itemId: string) => void

  /** Called when user defers an item for later */
  onDefer?: (itemId: string) => void

  /** Called when user selects/deselects items for bulk actions */
  onSelectItems?: (itemIds: string[]) => void

  /** Called when user performs a bulk action */
  onBulkAction?: (data: BulkActionData) => void

  /** Called when user applies filters or search */
  onFilter?: (filters: InboxFilters) => void

  /** Called when user performs a search */
  onSearch?: (query: string) => void

  /** Called when side panel should close */
  onCloseSidePanel?: () => void
}
