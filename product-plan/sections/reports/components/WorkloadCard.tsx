import type {
  WorkloadReport,
  ReportViewMode,
  TimeRange,
} from '@/../product/sections/reports/types'
import { ReportCardHeader } from './ReportCardHeader'

interface WorkloadCardProps {
  reports: WorkloadReport[]
  viewMode: ReportViewMode
  timeRange: TimeRange
  onTimeRangeChange?: (range: TimeRange) => void
  onView?: (teamMemberId: string) => void
  onDrillDown?: (entityType: string, entityId: string) => void
  onExport?: () => void
}

export function WorkloadCard({
  reports,
  timeRange,
  onTimeRangeChange,
  onView,
  onExport,
}: WorkloadCardProps) {
  const overloaded = reports.filter((r) => r.status === 'overloaded').length
  const avgUtilization = Math.round(
    reports.reduce((sum, r) => sum + r.utilizationPercentage, 0) / reports.length
  )

  const getStatusColor = (utilizationPercentage: number) => {
    if (utilizationPercentage >= 100) return 'bg-red-600 dark:bg-red-500'
    if (utilizationPercentage >= 90) return 'bg-amber-600 dark:bg-amber-500'
    return 'bg-emerald-600 dark:bg-emerald-500'
  }

  return (
    <div className="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-6 hover:shadow-lg transition-shadow">
      <ReportCardHeader
        title="Workload"
        icon={
          <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
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
            {avgUtilization}%
          </p>
          <p className="text-xs text-slate-600 dark:text-slate-400">Avg Utilization</p>
        </div>
        <div>
          <p className="text-2xl font-semibold text-red-600 dark:text-red-400">{overloaded}</p>
          <p className="text-xs text-slate-600 dark:text-slate-400">Overloaded</p>
        </div>
      </div>

      <div className="space-y-3">
        {reports.slice(0, 5).map((report) => (
          <button
            key={report.id}
            onClick={() => onView?.(report.teamMemberId)}
            className="w-full text-left group"
          >
            <div className="flex items-center justify-between mb-1.5">
              <p className="text-sm font-medium text-slate-900 dark:text-slate-50 truncate">
                {report.teamMemberName}
              </p>
              <span
                className={`text-xs font-semibold ${
                  report.utilizationPercentage >= 100
                    ? 'text-red-600 dark:text-red-400'
                    : report.utilizationPercentage >= 90
                      ? 'text-amber-600 dark:text-amber-400'
                      : 'text-emerald-600 dark:text-emerald-400'
                }`}
              >
                {report.utilizationPercentage}%
              </span>
            </div>
            <div className="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2 mb-1">
              <div
                className={`h-2 rounded-full transition-all ${getStatusColor(report.utilizationPercentage)}`}
                style={{
                  width: `${Math.min(report.utilizationPercentage, 100)}%`,
                }}
              />
            </div>
            <p className="text-xs text-slate-600 dark:text-slate-400">
              {report.assignedHoursPerWeek}h / {report.capacityHoursPerWeek}h per week
            </p>
          </button>
        ))}
      </div>
    </div>
  )
}
