export interface WorkspaceSettings {
    name: string;
    timezone: string;
    workWeekStart: string;
    defaultProjectStatus: string;
    brandColor: string;
    logo: string | null;
    workingHoursStart: string;
    workingHoursEnd: string;
    dateFormat: string;
    currency: string;
}

export interface TeamMember {
    id: number;
    name: string;
    email: string;
    role: string;
    avatar: string | null;
    joinedAt: string;
    lastActiveAt: string;
}

export interface AgentPermissions {
    canCreateWorkOrders: boolean;
    canModifyTasks: boolean;
    canAccessClientData: boolean;
    canSendEmails: boolean;
    canModifyDeliverables?: boolean;
    canAccessFinancialData?: boolean;
    canModifyPlaybooks?: boolean;
    requiresApproval: boolean;
}

export interface AgentBehaviorSettings {
    verbosityLevel: 'concise' | 'balanced' | 'detailed';
    creativityLevel: 'low' | 'balanced' | 'high';
    riskTolerance: 'low' | 'medium' | 'high';
}

export interface AgentConfiguration {
    enabled: boolean;
    dailyRunLimit: number;
    weeklyRunLimit: number;
    monthlyBudgetCap: number;
    currentMonthSpend: number;
    dailySpend?: number;
    permissions: AgentPermissions;
    behaviorSettings: AgentBehaviorSettings;
    toolPermissions?: Record<string, boolean>;
}

export interface AIAgent {
    id: number;
    code: string;
    name: string;
    type: 'project-management' | 'work-routing' | 'content-creation' | 'quality-assurance' | 'data-analysis';
    description: string;
    capabilities: string[];
    status: 'enabled' | 'disabled';
    configuration: AgentConfiguration | null;
    templateId?: number | null;
    isCustom?: boolean;
}

export interface ApprovalRequirements {
    clientFacingContent: boolean;
    financialData: boolean;
    contractualChanges: boolean;
    workOrderCreation: boolean;
    taskAssignment: boolean;
}

export interface GlobalAISettings {
    totalMonthlyBudget: number;
    currentMonthSpend: number;
    perProjectBudgetCap: number;
    approvalRequirements: ApprovalRequirements;
    retentionDays?: number;
    requireApprovalExternalSends?: boolean;
    requireApprovalFinancial?: boolean;
    requireApprovalContracts?: boolean;
    requireApprovalScopeChanges?: boolean;
}

export interface ToolCall {
    name: string;
    params: Record<string, unknown>;
    result: Record<string, unknown> | string | null;
    durationMs: number;
}

export interface AgentActivityLog {
    id: number;
    agentId: number;
    agentName: string;
    runType: string;
    timestamp: string;
    input: string;
    output: string | null;
    tokensUsed: number;
    cost: number;
    approvalStatus: 'pending' | 'approved' | 'rejected' | 'auto_approved' | 'failed';
    approvedBy: number | null;
    approvedAt: string | null;
    error: string | null;
    toolCalls?: ToolCall[];
    contextAccessed?: string[];
    workflowStateId?: number | null;
    durationMs?: number;
}

export interface AgentTemplate {
    id: number;
    code: string;
    name: string;
    type: string;
    description: string;
    defaultInstructions?: string;
    defaultTools: string[];
    defaultPermissions: string[];
    isActive: boolean;
}

export interface AgentTool {
    name: string;
    description: string;
    category: string;
    requiredPermissions: string[];
    enabled: boolean;
    parameters?: Record<string, {
        type: string;
        description: string;
        required: boolean;
        default?: unknown;
    }>;
}

export interface AgentWorkflowApproval {
    id: number;
    workflowStateId: number;
    agentId: number;
    agentName: string;
    agentCode: string;
    actionDescription: string;
    contextPreview: string;
    pauseReason: string;
    relatedEntityType: string | null;
    relatedEntityId: number | null;
    relatedEntityName: string | null;
    createdAt: string;
    waitingHours: number;
    urgency: 'urgent' | 'high' | 'normal';
}

export interface NotificationCategory {
    email: boolean;
    push: boolean;
    slack: boolean;
}

export interface NotificationPreferences {
    projectUpdates: NotificationCategory;
    taskAssignments: NotificationCategory;
    approvalRequests: NotificationCategory;
    blockers: NotificationCategory;
    deadlines: NotificationCategory;
    weeklyDigest: NotificationCategory;
    agentActivity: NotificationCategory;
}

export interface AuditLogEntry {
    id: number;
    timestamp: string;
    actor: string;
    actorName: string;
    actorType: 'user' | 'agent' | 'system';
    action: string;
    target: string | null;
    targetId: string | null;
    details: string;
    ipAddress: string | null;
}

export interface Integration {
    id: number;
    code: string;
    name: string;
    category: 'communication' | 'storage' | 'crm' | 'analytics' | 'automation';
    description: string;
    icon: string;
    features: string[];
    isActive: boolean;
    connected: boolean;
    connectedAt: string | null;
    lastSyncAt: string | null;
    syncStatus: 'success' | 'error' | 'pending' | null;
    errorMessage: string | null;
}

export interface BillingInfo {
    planName: string;
    planPrice: string;
    billingCycle: 'monthly' | 'annually';
    billingPeriodStart: string;
    billingPeriodEnd: string;
    nextBillingDate: string;
    usersIncluded: number;
    usersCurrent: number;
    projectsIncluded: number;
    projectsCurrent: number;
    storageGbIncluded: string;
    storageGbCurrent: string;
    aiRequestsIncluded: number;
    aiRequestsCurrent: number;
    paymentMethod: string | null;
    cardBrand: string | null;
    cardLast4: string | null;
    cardExpiry: string | null;
    status: 'active' | 'past_due' | 'canceled' | 'trial';
    trialEndsAt: string | null;
}

export interface Invoice {
    id: number;
    invoiceNumber: string;
    invoiceDate: string;
    dueDate: string;
    amount: string;
    status: 'paid' | 'pending' | 'overdue' | 'void';
    paidAt: string | null;
    description: string | null;
    pdfUrl: string | null;
}

export interface SettingsPageProps {
    workspaceSettings: WorkspaceSettings;
    teamMembers: TeamMember[];
    aiAgents: AIAgent[];
    globalAISettings: GlobalAISettings;
    agentActivityLogs: AgentActivityLog[];
    agentTemplates?: AgentTemplate[];
    agentTools?: AgentTool[];
    notificationPreferences: NotificationPreferences;
    auditLogEntries: AuditLogEntry[];
    integrations: Integration[];
    billingInfo: BillingInfo | null;
    invoices: Invoice[];
}
