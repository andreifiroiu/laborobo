import { useState } from 'react'
import type { ReportsProps, ReportViewMode, TimeRange } from '@/../product/sections/reports/types'
import { ProjectStatusCard } from './ProjectStatusCard'
import { WorkloadCard } from './WorkloadCard'
import { TaskAgingCard } from './TaskAgingCard'
import { BlockersCard } from './BlockersCard'
import { TimeBudgetCard } from './TimeBudgetCard'
import { ApprovalsVelocityCard } from './ApprovalsVelocityCard'
import { AgentActivityCard } from './AgentActivityCard'
import { AIInsightsCard } from './AIInsightsCard'
import { AlertBanner } from './AlertBanner'

export function Reports({
  projectStatusReports,
  workloadReports,
  taskAgingReports,
  blockerReports,
  timeBudgetReports,
  approvalsVelocityReports,
  agentActivityReports,
  aiInsights,
  filters,
  onViewProjectStatus,
  onViewTeamMember,
  onViewTask,
  onViewBlocker,
  onViewTimeBudget,
  onViewApproval,
  onViewAgent,
  onViewInsight,
  onDismissInsight,
  onFilterChange,
  onExportReport,
  onGenerateSummary,
  onDrillDown,
  onResolveBlocker,
  onViewProject,
}: ReportsProps) {
  const [viewMode, setViewMode] = useState<ReportViewMode>(
    filters?.viewMode || 'by-project'
  )
  const [timeRange, setTimeRange] = useState<TimeRange>(
    filters?.timeRange || 'last-30-days'
  )

  // Get critical insights for alert banners
  const criticalInsights = aiInsights.filter(
    (insight) => insight.severity === 'critical' && !insight.dismissed
  )

  const handleViewModeChange = (mode: ReportViewMode) => {
    setViewMode(mode)
    onFilterChange?.({ ...filters, viewMode: mode, timeRange })
  }

  const handleTimeRangeChange = (range: TimeRange) => {
    setTimeRange(range)
    onFilterChange?.({ ...filters, viewMode, timeRange: range })
  }

  return (
    <div className="min-h-screen bg-slate-50 dark:bg-slate-950">
      {/* Alert Banners */}
      {criticalInsights.length > 0 && (
        <div className="border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
          <div className="max-w-[1800px] mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div className="space-y-2">
              {criticalInsights.slice(0, 3).map((insight) => (
                <AlertBanner
                  key={insight.id}
                  insight={insight}
                  onView={() => onViewInsight?.(insight.id)}
                  onDismiss={() => onDismissInsight?.(insight.id)}
                />
              ))}
            </div>
          </div>
        </div>
      )}

      {/* Header */}
      <div className="border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
        <div className="max-w-[1800px] mx-auto px-4 sm:px-6 lg:px-8 py-6">
          <div className="flex items-center justify-between mb-6">
            <div>
              <h1 className="text-2xl font-semibold text-slate-900 dark:text-slate-50">
                Reports
              </h1>
              <p className="mt-1 text-sm text-slate-600 dark:text-slate-400">
                Track project health, workload, and operational metrics
              </p>
            </div>
          </div>

          {/* Global View Mode Toggle */}
          <div className="flex items-center gap-2 p-1 bg-slate-100 dark:bg-slate-800 rounded-lg w-fit">
            <button
              onClick={() => handleViewModeChange('by-project')}
              className={`px-3 py-1.5 text-sm font-medium rounded transition-colors ${
                viewMode === 'by-project'
                  ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-50 shadow-sm'
                  : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-50'
              }`}
            >
              By Project
            </button>
            <button
              onClick={() => handleViewModeChange('by-person')}
              className={`px-3 py-1.5 text-sm font-medium rounded transition-colors ${
                viewMode === 'by-person'
                  ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-50 shadow-sm'
                  : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-50'
              }`}
            >
              By Person
            </button>
            <button
              onClick={() => handleViewModeChange('by-time-period')}
              className={`px-3 py-1.5 text-sm font-medium rounded transition-colors ${
                viewMode === 'by-time-period'
                  ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-50 shadow-sm'
                  : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-50'
              }`}
            >
              By Time Period
            </button>
          </div>
        </div>
      </div>

      {/* Dashboard Grid */}
      <div className="max-w-[1800px] mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div className="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
          {/* AI Insights Card - Always first */}
          <AIInsightsCard
            insights={aiInsights}
            onViewInsight={onViewInsight}
            onDismissInsight={onDismissInsight}
            timeRange={timeRange}
            onTimeRangeChange={handleTimeRangeChange}
          />

          {/* Project Status Card */}
          <ProjectStatusCard
            reports={projectStatusReports}
            viewMode={viewMode}
            timeRange={timeRange}
            onTimeRangeChange={handleTimeRangeChange}
            onView={onViewProjectStatus}
            onDrillDown={onDrillDown}
            onExport={() => onExportReport?.('project-status', 'pdf')}
          />

          {/* Workload Card */}
          <WorkloadCard
            reports={workloadReports}
            viewMode={viewMode}
            timeRange={timeRange}
            onTimeRangeChange={handleTimeRangeChange}
            onView={onViewTeamMember}
            onDrillDown={onDrillDown}
            onExport={() => onExportReport?.('workload', 'pdf')}
          />

          {/* Task Aging Card */}
          <TaskAgingCard
            reports={taskAgingReports}
            viewMode={viewMode}
            timeRange={timeRange}
            onTimeRangeChange={handleTimeRangeChange}
            onView={onViewTask}
            onDrillDown={onDrillDown}
            onExport={() => onExportReport?.('task-aging', 'pdf')}
          />

          {/* Blockers Card */}
          <BlockersCard
            reports={blockerReports}
            viewMode={viewMode}
            timeRange={timeRange}
            onTimeRangeChange={handleTimeRangeChange}
            onView={onViewBlocker}
            onResolve={onResolveBlocker}
            onDrillDown={onDrillDown}
            onExport={() => onExportReport?.('blockers', 'pdf')}
          />

          {/* Time & Budget Card */}
          <TimeBudgetCard
            reports={timeBudgetReports}
            viewMode={viewMode}
            timeRange={timeRange}
            onTimeRangeChange={handleTimeRangeChange}
            onView={onViewTimeBudget}
            onViewProject={onViewProject}
            onDrillDown={onDrillDown}
            onExport={() => onExportReport?.('time-budget', 'pdf')}
          />

          {/* Approvals Velocity Card */}
          <ApprovalsVelocityCard
            reports={approvalsVelocityReports}
            viewMode={viewMode}
            timeRange={timeRange}
            onTimeRangeChange={handleTimeRangeChange}
            onView={onViewApproval}
            onDrillDown={onDrillDown}
            onExport={() => onExportReport?.('approvals-velocity', 'pdf')}
          />

          {/* Agent Activity Card */}
          <AgentActivityCard
            reports={agentActivityReports}
            viewMode={viewMode}
            timeRange={timeRange}
            onTimeRangeChange={handleTimeRangeChange}
            onView={onViewAgent}
            onDrillDown={onDrillDown}
            onExport={() => onExportReport?.('agent-activity', 'pdf')}
            onGenerateSummary={() => onGenerateSummary?.('agent-activity')}
          />
        </div>
      </div>
    </div>
  )
}
