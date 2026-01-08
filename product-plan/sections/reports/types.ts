// =============================================================================
// Data Types
// =============================================================================

export type ProjectHealthStatus = 'on-track' | 'at-risk' | 'overdue'
export type ProjectTrend = 'improving' | 'stable' | 'declining'
export type WorkloadStatus = 'available' | 'optimal' | 'near-capacity' | 'overloaded'
export type TaskAgingSeverity = 'normal' | 'warning' | 'critical'
export type BlockerImpact = 'low' | 'medium' | 'high' | 'critical'
export type BudgetStatus = 'under-budget' | 'on-track' | 'over-budget'
export type ApprovalStatus = 'pending' | 'approved' | 'rejected'
export type SLAStatus = 'on-time' | 'breached'
export type AgentStatus = 'active' | 'paused' | 'inactive'
export type InsightType = 'anomaly' | 'recommendation' | 'positive'
export type InsightSeverity = 'info' | 'warning' | 'critical'
export type InsightCategory =
  | 'budget'
  | 'workload'
  | 'capacity'
  | 'blocker'
  | 'approval'
  | 'performance'
export type ReportViewMode = 'by-project' | 'by-person' | 'by-time-period'
export type TimeRange = 'last-7-days' | 'last-30-days' | 'this-month' | 'custom'

export interface ProjectStatusReport {
  id: string
  projectId: string
  projectName: string
  partyName: string
  status: ProjectHealthStatus
  healthScore: number
  completionPercentage: number
  dueDate: string
  daysUntilDue?: number
  daysOverdue?: number
  workOrdersTotal: number
  workOrdersCompleted: number
  workOrdersInProgress: number
  workOrdersBlocked?: number
  tasksTotal: number
  tasksCompleted: number
  deliverablesTotal: number
  deliverablesApproved: number
  budgetAllocated: number
  budgetSpent: number
  hoursEstimated: number
  hoursLogged: number
  trend: ProjectTrend
  lastUpdated: string
  risks?: string[]
}

export interface WorkloadReport {
  id: string
  teamMemberId: string
  teamMemberName: string
  role: string
  capacityHoursPerWeek: number
  assignedHoursPerWeek: number
  utilizationPercentage: number
  status: WorkloadStatus
  assignedProjects: number
  assignedWorkOrders: number
  assignedTasks: number
  projectNames: string[]
  nextAvailableDate: string
  trend: 'increasing' | 'stable' | 'decreasing'
}

export interface TaskAgingReport {
  id: string
  taskId: string
  taskName: string
  projectName: string
  workOrderName: string
  status: string
  assignedTo: string
  daysInCurrentStatus: number
  createdDate: string
  lastUpdated: string
  estimatedHours: number
  blockedReason?: string
  severity: TaskAgingSeverity
}

export interface BlockerReport {
  id: string
  itemType: 'task' | 'work-order' | 'project'
  itemId: string
  itemName: string
  projectName: string
  blockedReason: string
  blockedBy: string
  assignedTo: string
  blockedDate: string
  daysBlocked: number
  impact: BlockerImpact
  actionRequired: string
}

export interface TimeBudgetReport {
  id: string
  projectId: string
  projectName: string
  partyName: string
  budgetAllocated: number
  budgetSpent: number
  budgetRemaining: number
  budgetUtilization: number
  hoursEstimated: number
  hoursLogged: number
  hoursRemaining: number
  hourlyBurnRate: number
  projectedOverrun: number
  status: BudgetStatus
  weeklySpend: number[]
  lastUpdated: string
}

export interface ApprovalsVelocityReport {
  id: string
  approvalId: string
  itemType: 'deliverable' | 'agent-output'
  itemName: string
  projectName: string
  submittedBy: string
  submittedDate: string
  approver: string
  approvedDate?: string
  status: ApprovalStatus
  daysInReview: number
  priority: 'normal' | 'high' | 'critical'
  sla: number
  slaStatus: SLAStatus
  notes?: string
}

export interface AgentActivityReport {
  id: string
  agentId: string
  agentName: string
  agentType: string
  runsTotal: number
  runsThisWeek: number
  outputsGenerated: number
  outputsApproved: number
  outputsRejected: number
  outputsPending: number
  approvalRate: number
  averageApprovalTime: number
  totalCost: number
  costThisWeek: number
  tasksCompleted: number
  taskCompletionRate: number
  lastRun: string
  status: AgentStatus
}

export interface AIInsight {
  id: string
  type: InsightType
  severity: InsightSeverity
  category: InsightCategory
  title: string
  description: string
  affectedEntity: string | null
  affectedEntityName?: string
  detectedDate: string
  recommendation: string
  actionRequired: boolean
  dismissed: boolean
}

// =============================================================================
// View Types
// =============================================================================

export interface ReportFilters {
  viewMode: ReportViewMode
  timeRange: TimeRange
  customStartDate?: string
  customEndDate?: string
  projectId?: string
  teamMemberId?: string
}

// =============================================================================
// Component Props
// =============================================================================

export interface ReportsProps {
  /** Project status reports with health indicators */
  projectStatusReports: ProjectStatusReport[]

  /** Workload reports for team members */
  workloadReports: WorkloadReport[]

  /** Task aging reports for stuck tasks */
  taskAgingReports: TaskAgingReport[]

  /** Blocker reports for all blocked items */
  blockerReports: BlockerReport[]

  /** Time and budget reports for projects */
  timeBudgetReports: TimeBudgetReport[]

  /** Approvals velocity reports */
  approvalsVelocityReports: ApprovalsVelocityReport[]

  /** Agent activity reports */
  agentActivityReports: AgentActivityReport[]

  /** AI-generated insights and anomalies */
  aiInsights: AIInsight[]

  /** Currently active filters */
  filters?: ReportFilters

  /** Called when user clicks a project status card to see details */
  onViewProjectStatus?: (projectId: string) => void

  /** Called when user clicks a team member in workload report */
  onViewTeamMember?: (teamMemberId: string) => void

  /** Called when user clicks a task in aging report */
  onViewTask?: (taskId: string) => void

  /** Called when user clicks a blocker to see details */
  onViewBlocker?: (blockerId: string) => void

  /** Called when user clicks a project in time/budget report */
  onViewTimeBudget?: (projectId: string) => void

  /** Called when user clicks an approval item */
  onViewApproval?: (approvalId: string) => void

  /** Called when user clicks an agent in activity report */
  onViewAgent?: (agentId: string) => void

  /** Called when user clicks an AI insight */
  onViewInsight?: (insightId: string) => void

  /** Called when user dismisses an AI insight */
  onDismissInsight?: (insightId: string) => void

  /** Called when user changes report filters */
  onFilterChange?: (filters: ReportFilters) => void

  /** Called when user requests to export a report */
  onExportReport?: (reportType: string, format: 'pdf' | 'csv') => void

  /** Called when user requests AI to generate a summary */
  onGenerateSummary?: (reportType: string) => void

  /** Called when user wants to drill down into specific data */
  onDrillDown?: (entityType: string, entityId: string) => void

  /** Called when user wants to resolve a blocker */
  onResolveBlocker?: (blockerId: string) => void

  /** Called when user wants to view project details from any report */
  onViewProject?: (projectId: string) => void
}
