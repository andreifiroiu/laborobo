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
    permissions: AgentPermissions;
    behaviorSettings: AgentBehaviorSettings;
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
}

export interface SettingsPageProps {
    workspaceSettings: WorkspaceSettings;
    teamMembers: TeamMember[];
    aiAgents: AIAgent[];
    globalAISettings: GlobalAISettings;
    agentActivityLogs: AgentActivityLog[];
}
