import type {
  TimeBudgetReport,
  ReportViewMode,
  TimeRange,
} from '@/../product/sections/reports/types'
import { ReportCardHeader } from './ReportCardHeader'

interface TimeBudgetCardProps {
  reports: TimeBudgetReport[]
  viewMode: ReportViewMode
  timeRange: TimeRange
  onTimeRangeChange?: (range: TimeRange) => void
  onView?: (projectId: string) => void
  onViewProject?: (projectId: string) => void
  onDrillDown?: (entityType: string, entityId: string) => void
  onExport?: () => void
}

export function TimeBudgetCard({
  reports,
  timeRange,
  onTimeRangeChange,
  onView,
  onExport,
}: TimeBudgetCardProps) {
  const overBudget = reports.filter((r) => r.status === 'over-budget').length
  const totalSpent = reports.reduce((sum, r) => sum + r.budgetSpent, 0)

  return (
    <div className="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-6 hover:shadow-lg transition-shadow">
      <ReportCardHeader
        title="Time & Budget"
        icon={
          <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
            />
          </svg>
        }
        timeRange={timeRange}
        onTimeRangeChange={onTimeRangeChange}
        onExport={onExport}
      />

      <div className="grid grid-cols-2 gap-4 mb-6">
        <div>
          <p className="text-2xl font-semibold text-slate-900 dark:text-slate-50">
            ${(totalSpent / 1000).toFixed(0)}k
          </p>
          <p className="text-xs text-slate-600 dark:text-slate-400">Total Spent</p>
        </div>
        <div>
          <p className="text-2xl font-semibold text-red-600 dark:text-red-400">{overBudget}</p>
          <p className="text-xs text-slate-600 dark:text-slate-400">Over Budget</p>
        </div>
      </div>

      <div className="space-y-3">
        {reports.slice(0, 5).map((report) => (
          <button
            key={report.id}
            onClick={() => onView?.(report.projectId)}
            className="w-full text-left"
          >
            <div className="flex items-center justify-between mb-1.5">
              <p className="text-sm font-medium text-slate-900 dark:text-slate-50 truncate">
                {report.projectName}
              </p>
              <span
                className={`text-xs font-semibold ${
                  report.status === 'over-budget'
                    ? 'text-red-600 dark:text-red-400'
                    : report.status === 'on-track'
                      ? 'text-emerald-600 dark:text-emerald-400'
                      : 'text-blue-600 dark:text-blue-400'
                }`}
              >
                {report.budgetUtilization}%
              </span>
            </div>
            <div className="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2 mb-1">
              <div
                className={`h-2 rounded-full transition-all ${
                  report.budgetUtilization > 100
                    ? 'bg-red-600 dark:bg-red-500'
                    : report.budgetUtilization >= 90
                      ? 'bg-amber-600 dark:bg-amber-500'
                      : 'bg-emerald-600 dark:bg-emerald-500'
                }`}
                style={{
                  width: `${Math.min(report.budgetUtilization, 100)}%`,
                }}
              />
            </div>
            <p className="text-xs text-slate-600 dark:text-slate-400">
              ${(report.budgetSpent / 1000).toFixed(1)}k / $
              {(report.budgetAllocated / 1000).toFixed(1)}k • Burn: {report.hourlyBurnRate}x
            </p>
          </button>
        ))}
      </div>
    </div>
  )
}
