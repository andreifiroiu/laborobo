// =============================================================================
// Work Section Types
// =============================================================================

export interface Party {
    id: string;
    name: string;
    type: 'client' | 'vendor' | 'department' | 'team_member';
    contactName: string | null;
    contactEmail: string | null;
}

export interface TeamMember {
    id: string;
    name: string;
    type: 'human' | 'ai_agent';
    role: string;
    avatarUrl: string | null;
    skills: string[];
}

export interface Project {
    id: string;
    name: string;
    description: string | null;
    partyId: string;
    partyName: string;
    ownerId: string;
    ownerName: string;
    status: 'active' | 'on_hold' | 'completed' | 'archived';
    startDate: string;
    targetEndDate: string | null;
    budgetHours: number | null;
    actualHours: number;
    progress: number;
    tags: string[];
}

export interface WorkOrder {
    id: string;
    title: string;
    description: string | null;
    projectId: string;
    projectName: string;
    assignedToId: string | null;
    assignedToName: string;
    status: 'draft' | 'active' | 'in_review' | 'approved' | 'delivered';
    priority: 'low' | 'medium' | 'high' | 'urgent';
    dueDate: string;
    estimatedHours: number;
    actualHours: number;
    acceptanceCriteria: string[];
    sopAttached: boolean;
    sopName: string | null;
    partyContactId: string | null;
    createdBy: string;
    createdByName: string;
}

export interface ChecklistItem {
    id: string;
    text: string;
    completed: boolean;
}

export interface Task {
    id: string;
    title: string;
    description: string | null;
    workOrderId: string;
    workOrderTitle: string;
    projectId: string;
    assignedToId: string | null;
    assignedToName: string;
    status: 'todo' | 'in_progress' | 'done';
    dueDate: string;
    estimatedHours: number;
    actualHours: number;
    checklistItems: ChecklistItem[];
    dependencies: string[];
    isBlocked: boolean;
}

export interface Deliverable {
    id: string;
    title: string;
    description: string | null;
    workOrderId: string;
    workOrderTitle: string;
    projectId: string;
    type: 'document' | 'design' | 'report' | 'code' | 'other';
    status: 'draft' | 'in_review' | 'approved' | 'delivered';
    version: string;
    createdDate: string;
    deliveredDate: string | null;
    fileUrl: string | null;
    acceptanceCriteria: string[];
}

export interface CommunicationThread {
    id: string;
    workItemType: 'project' | 'workOrder' | 'task';
    workItemId: string;
    workItemTitle: string;
    messageCount: number;
    lastActivity: string | null;
}

export interface Message {
    id: string;
    threadId?: string;
    authorId: string;
    authorName: string;
    authorType: 'human' | 'ai_agent';
    timestamp: string;
    content: string;
    type: 'note' | 'suggestion' | 'decision' | 'question';
}

export interface Document {
    id: string;
    name: string;
    type: 'reference' | 'artifact' | 'evidence' | 'template';
    fileUrl: string;
    attachedToType?: 'project' | 'workOrder' | 'task' | 'deliverable';
    attachedToId?: string;
    uploadedBy?: string;
    uploadedDate?: string;
    fileSize: string | null;
}

export interface TimeEntry {
    id: string;
    taskId?: string;
    userId: string;
    userName: string;
    hours: number;
    date: string;
    mode: 'manual' | 'timer' | 'ai_estimation';
    note: string | null;
    startedAt: string | null;
    stoppedAt: string | null;
}

// =============================================================================
// View Types
// =============================================================================

export type WorkView = 'all_projects' | 'my_work' | 'by_status' | 'calendar' | 'archive';

export interface QuickAddData {
    type: 'project' | 'workOrder' | 'task';
    title: string;
    parentId?: string;
}

// =============================================================================
// Page Props
// =============================================================================

export interface WorkPageProps {
    projects: Project[];
    workOrders: WorkOrder[];
    tasks: Task[];
    deliverables: Deliverable[];
    parties: Party[];
    teamMembers: TeamMember[];
    communicationThreads: CommunicationThread[];
    currentView: WorkView;
    currentUserId: string;
}

export interface ProjectDetailProps {
    project: Project;
    workOrders: Array<{
        id: string;
        title: string;
        status: string;
        priority: string;
        dueDate: string;
        assignedToName: string;
        tasksCount: number;
        completedTasksCount: number;
    }>;
    documents: Document[];
    communicationThread: {
        id: string;
        messageCount: number;
        lastActivity: string | null;
    } | null;
    messages: Message[];
    parties: Array<{ id: string; name: string }>;
}

export interface WorkOrderDetailProps {
    workOrder: WorkOrder;
    tasks: Array<{
        id: string;
        title: string;
        status: string;
        dueDate: string;
        assignedToName: string;
        estimatedHours: number;
        actualHours: number;
        checklistItems: ChecklistItem[];
        isBlocked: boolean;
    }>;
    deliverables: Array<{
        id: string;
        title: string;
        type: string;
        status: string;
        version: string;
        fileUrl: string | null;
    }>;
    documents: Document[];
    communicationThread: {
        id: string;
        messageCount: number;
    } | null;
    messages: Message[];
    teamMembers: Array<{ id: string; name: string }>;
}

export interface TaskDetailProps {
    task: Task;
    timeEntries: TimeEntry[];
    activeTimer: {
        id: string;
        startedAt: string;
    } | null;
    teamMembers: Array<{ id: string; name: string }>;
}

// =============================================================================
// Component Props
// =============================================================================

export interface ViewTabsProps {
    currentView: WorkView;
    onViewChange: (view: WorkView) => void;
}

export interface QuickAddBarProps {
    onQuickAdd: (data: QuickAddData) => void;
    parentType?: 'project' | 'workOrder';
    parentId?: string;
}

export interface ProjectTreeItemProps {
    project: Project;
    workOrders: WorkOrder[];
    tasks: Task[];
    onViewProject: (projectId: string) => void;
    onCreateWorkOrder: (projectId: string) => void;
    onViewWorkOrder: (workOrderId: string) => void;
    onCreateTask: (workOrderId: string) => void;
    onViewTask: (taskId: string) => void;
}

export interface MyWorkViewProps {
    workOrders: WorkOrder[];
    tasks: Task[];
    currentUserId: string;
    onViewWorkOrder: (workOrderId: string) => void;
    onViewTask: (taskId: string) => void;
    onUpdateWorkOrderStatus: (workOrderId: string, status: WorkOrder['status']) => void;
    onUpdateTaskStatus: (taskId: string, status: Task['status']) => void;
}

export interface KanbanViewProps {
    workOrders: WorkOrder[];
    onViewWorkOrder: (workOrderId: string) => void;
    onCreateWorkOrder: (data: QuickAddData) => void;
    onUpdateWorkOrderStatus: (workOrderId: string, status: WorkOrder['status']) => void;
}

export interface CalendarViewProps {
    projects: Project[];
    workOrders: WorkOrder[];
    onViewProject: (projectId: string) => void;
    onViewWorkOrder: (workOrderId: string) => void;
}

export interface ArchiveViewProps {
    projects: Project[];
    workOrders: WorkOrder[];
    tasks: Task[];
    onViewProject: (projectId: string) => void;
    onRestoreProject: (projectId: string) => void;
}

export interface CommunicationPanelProps {
    threadId: string | null;
    messages: Message[];
    onAddMessage: (content: string, type: Message['type']) => void;
    isLoading?: boolean;
}

export interface TimeTrackerProps {
    taskId: string;
    activeTimer: { id: string; startedAt: string } | null;
    onStartTimer: () => void;
    onStopTimer: () => void;
    onLogManual: (hours: number, date: string, note?: string) => void;
}
