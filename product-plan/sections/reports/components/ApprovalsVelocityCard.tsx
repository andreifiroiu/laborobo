import type {
  ApprovalsVelocityReport,
  ReportViewMode,
  TimeRange,
} from '@/../product/sections/reports/types'
import { ReportCardHeader } from './ReportCardHeader'

interface ApprovalsVelocityCardProps {
  reports: ApprovalsVelocityReport[]
  viewMode: ReportViewMode
  timeRange: TimeRange
  onTimeRangeChange?: (range: TimeRange) => void
  onView?: (approvalId: string) => void
  onDrillDown?: (entityType: string, entityId: string) => void
  onExport?: () => void
}

export function ApprovalsVelocityCard({
  reports,
  timeRange,
  onTimeRangeChange,
  onView,
  onExport,
}: ApprovalsVelocityCardProps) {
  const pending = reports.filter((r) => r.status === 'pending').length
  const breached = reports.filter((r) => r.slaStatus === 'breached').length

  return (
    <div className="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-6 hover:shadow-lg transition-shadow">
      <ReportCardHeader
        title="Approvals Velocity"
        icon={
          <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
            />
          </svg>
        }
        timeRange={timeRange}
        onTimeRangeChange={onTimeRangeChange}
        onExport={onExport}
      />

      <div className="grid grid-cols-2 gap-4 mb-6">
        <div>
          <p className="text-2xl font-semibold text-slate-900 dark:text-slate-50">{pending}</p>
          <p className="text-xs text-slate-600 dark:text-slate-400">Pending</p>
        </div>
        <div>
          <p className="text-2xl font-semibold text-red-600 dark:text-red-400">{breached}</p>
          <p className="text-xs text-slate-600 dark:text-slate-400">SLA Breached</p>
        </div>
      </div>

      <div className="space-y-2">
        {reports.slice(0, 5).map((report) => (
          <button
            key={report.id}
            onClick={() => onView?.(report.approvalId)}
            className="w-full flex items-start justify-between gap-2 p-3 bg-slate-50 dark:bg-slate-800/50 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors text-left"
          >
            <div className="flex-1 min-w-0">
              <p className="text-sm font-medium text-slate-900 dark:text-slate-50 truncate mb-1">
                {report.itemName}
              </p>
              <p className="text-xs text-slate-600 dark:text-slate-400">
                {report.daysInReview}d in review • {report.approver}
              </p>
            </div>
            {report.slaStatus === 'breached' && (
              <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 dark:bg-red-950/30 text-red-700 dark:text-red-300 flex-shrink-0">
                Overdue
              </span>
            )}
          </button>
        ))}
      </div>
    </div>
  )
}
