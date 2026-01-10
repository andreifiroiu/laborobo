// =============================================================================
// Data Types
// =============================================================================

// SOP-specific types
export interface SOPStep {
    id: string;
    order: number;
    title: string;
    description: string;
    evidenceRequired: 'document' | 'link' | 'screenshot' | 'approval' | 'calendar-invite';
    evidenceDescription: string;
    assignedRole: string;
}

export interface SOPContent {
    triggerConditions: string;
    steps: SOPStep[];
    rolesInvolved: string[];
    estimatedTimeMinutes: number;
    definitionOfDone: string;
}

export interface SOP {
    id: string;
    type: 'sop';
    name: string;
    description: string;
    content: SOPContent;
    tags: string[];
    timesApplied: number;
    lastUsed: string | null;
    createdBy: string;
    createdByName: string;
    lastModified: string;
    aiGenerated: boolean;
    usedByWorkOrders: string[];
}

// Checklist-specific types
export interface ChecklistItem {
    id: string;
    label: string;
    completed: boolean;
    assignedRole?: string;
    evidenceRequired?: boolean;
    evidenceDescription?: string;
}

export interface ChecklistContent {
    items: ChecklistItem[];
}

export interface Checklist {
    id: string;
    type: 'checklist';
    name: string;
    description: string;
    content: ChecklistContent;
    tags: string[];
    timesApplied: number;
    lastUsed: string | null;
    createdBy: string;
    createdByName: string;
    lastModified: string;
    aiGenerated: boolean;
    usedByWorkOrders: string[];
}

// Template-specific types
export type TemplateType = 'project' | 'work-order' | 'document';

export interface ProjectTemplateStructure {
    milestones: Array<{
        name: string;
        durationDays: number;
        workOrders: string[];
    }>;
    defaultTeamRoles: string[];
    estimatedTotalDays: number;
}

export interface WorkOrderTemplateStructure {
    prefilledScope: string;
    attachedSOPs: string[];
    attachedChecklists: string[];
    attachedAcceptanceCriteria: string[];
    estimatedHours: number;
    defaultTasks: string[];
}

export interface DocumentTemplateStructure {
    sections: Array<{
        heading: string;
        description: string;
    }>;
    outputFormat: string;
}

export interface TemplateContent {
    templateType: TemplateType;
    structure: ProjectTemplateStructure | WorkOrderTemplateStructure | DocumentTemplateStructure;
}

export interface Template {
    id: string;
    type: 'template';
    name: string;
    description: string;
    content: TemplateContent;
    tags: string[];
    timesApplied: number;
    lastUsed: string | null;
    createdBy: string;
    createdByName: string;
    lastModified: string;
    aiGenerated: boolean;
    usedByWorkOrders: string[];
}

// Acceptance Criteria-specific types
export interface CriteriaRule {
    id: string;
    rule: string;
    validationType: 'automated' | 'manual';
    validationTool: string | null;
}

export interface AcceptanceCriteriaContent {
    criteria: CriteriaRule[];
}

export interface AcceptanceCriteria {
    id: string;
    type: 'acceptance_criteria';
    name: string;
    description: string;
    content: AcceptanceCriteriaContent;
    tags: string[];
    timesApplied: number;
    lastUsed: string | null;
    createdBy: string;
    createdByName: string;
    lastModified: string;
    aiGenerated: boolean;
    usedByWorkOrders: string[];
}

// Version history
export interface VersionHistoryEntry {
    version: number;
    modifiedBy: string;
    modifiedAt: string;
    changeDescription: string;
}

// Union type for all playbook types
export type Playbook = SOP | Checklist | Template | AcceptanceCriteria;

export interface WorkOrder {
    id: string;
    title: string;
    projectName: string;
}

// =============================================================================
// View Types
// =============================================================================

export type PlaybookType = 'sop' | 'checklist' | 'template' | 'acceptance_criteria';

export type PlaybookTab = 'all' | PlaybookType;

export interface PlaybookFilters {
    tab: PlaybookTab;
    search?: string;
    tags?: string[];
    sortBy?: 'recent' | 'popular' | 'alphabetical';
}

// =============================================================================
// Page Props (Inertia)
// =============================================================================

export interface PlaybooksPageProps {
    playbooks: Playbook[];
    workOrders: WorkOrder[];
}

// =============================================================================
// Component Props
// =============================================================================

export interface PlaybooksViewProps {
    playbooks: Playbook[];
    workOrders: WorkOrder[];
    activeTab: PlaybookTab;
    onTabChange: (tab: PlaybookTab) => void;
    searchQuery: string;
    onSearchChange: (query: string) => void;
    selectedTags: string[];
    onTagsChange: (tags: string[]) => void;
    sortBy: 'recent' | 'popular' | 'alphabetical';
    onSortChange: (sort: 'recent' | 'popular' | 'alphabetical') => void;
    onViewPlaybook: (id: string) => void;
    onCreatePlaybook: (type: PlaybookType) => void;
}

export interface PlaybookCardProps {
    playbook: Playbook;
    onClick: () => void;
}

export interface PlaybookTabsProps {
    activeTab: PlaybookTab;
    onTabChange: (tab: PlaybookTab) => void;
    counts: Record<PlaybookTab, number>;
}

export interface EmptyStateProps {
    tab: PlaybookTab;
    onCreatePlaybook: (type: PlaybookType) => void;
}

export interface PlaybookDetailPanelProps {
    playbook: Playbook;
    workOrders: WorkOrder[];
    onClose: () => void;
    onEdit: (playbook: Playbook) => void;
}

export interface PlaybookFormPanelProps {
    open: boolean;
    playbook?: Playbook;
    type: PlaybookType;
    onClose: () => void;
}
