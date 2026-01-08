import type {
  TaskAgingReport,
  ReportViewMode,
  TimeRange,
} from '@/../product/sections/reports/types'
import { ReportCardHeader } from './ReportCardHeader'

interface TaskAgingCardProps {
  reports: TaskAgingReport[]
  viewMode: ReportViewMode
  timeRange: TimeRange
  onTimeRangeChange?: (range: TimeRange) => void
  onView?: (taskId: string) => void
  onDrillDown?: (entityType: string, entityId: string) => void
  onExport?: () => void
}

export function TaskAgingCard({
  reports,
  timeRange,
  onTimeRangeChange,
  onView,
  onExport,
}: TaskAgingCardProps) {
  const critical = reports.filter((r) => r.severity === 'critical').length
  const avgAge = Math.round(
    reports.reduce((sum, r) => sum + r.daysInCurrentStatus, 0) / reports.length
  )

  return (
    <div className="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-6 hover:shadow-lg transition-shadow">
      <ReportCardHeader
        title="Task Aging"
        icon={
          <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
            />
          </svg>
        }
        timeRange={timeRange}
        onTimeRangeChange={onTimeRangeChange}
        onExport={onExport}
      />

      <div className="grid grid-cols-2 gap-4 mb-6">
        <div>
          <p className="text-2xl font-semibold text-slate-900 dark:text-slate-50">{avgAge}d</p>
          <p className="text-xs text-slate-600 dark:text-slate-400">Avg Age</p>
        </div>
        <div>
          <p className="text-2xl font-semibold text-red-600 dark:text-red-400">{critical}</p>
          <p className="text-xs text-slate-600 dark:text-slate-400">Critical</p>
        </div>
      </div>

      <div className="space-y-2">
        {reports.slice(0, 5).map((report) => (
          <button
            key={report.id}
            onClick={() => onView?.(report.taskId)}
            className="w-full flex items-start gap-3 p-3 bg-slate-50 dark:bg-slate-800/50 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors text-left"
          >
            <div
              className={`w-2 h-2 rounded-full mt-1.5 flex-shrink-0 ${
                report.severity === 'critical'
                  ? 'bg-red-500'
                  : report.severity === 'warning'
                    ? 'bg-amber-500'
                    : 'bg-slate-400'
              }`}
            />
            <div className="flex-1 min-w-0">
              <p className="text-sm font-medium text-slate-900 dark:text-slate-50 truncate mb-1">
                {report.taskName}
              </p>
              <p className="text-xs text-slate-600 dark:text-slate-400">
                {report.daysInCurrentStatus}d in {report.status} • {report.assignedTo}
              </p>
            </div>
          </button>
        ))}
      </div>
    </div>
  )
}
