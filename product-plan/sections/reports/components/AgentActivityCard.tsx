import type {
  AgentActivityReport,
  ReportViewMode,
  TimeRange,
} from '@/../product/sections/reports/types'
import { ReportCardHeader } from './ReportCardHeader'

interface AgentActivityCardProps {
  reports: AgentActivityReport[]
  viewMode: ReportViewMode
  timeRange: TimeRange
  onTimeRangeChange?: (range: TimeRange) => void
  onView?: (agentId: string) => void
  onDrillDown?: (entityType: string, entityId: string) => void
  onExport?: () => void
  onGenerateSummary?: () => void
}

export function AgentActivityCard({
  reports,
  timeRange,
  onTimeRangeChange,
  onView,
  onExport,
  onGenerateSummary,
}: AgentActivityCardProps) {
  const totalRuns = reports.reduce((sum, r) => sum + r.runsThisWeek, 0)
  const avgApprovalRate = Math.round(
    reports.reduce((sum, r) => sum + r.approvalRate, 0) / reports.length
  )

  return (
    <div className="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-6 hover:shadow-lg transition-shadow">
      <ReportCardHeader
        title="Agent Activity"
        icon={
          <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"
            />
          </svg>
        }
        timeRange={timeRange}
        onTimeRangeChange={onTimeRangeChange}
        onExport={onExport}
      />

      <div className="grid grid-cols-2 gap-4 mb-6">
        <div>
          <p className="text-2xl font-semibold text-slate-900 dark:text-slate-50">{totalRuns}</p>
          <p className="text-xs text-slate-600 dark:text-slate-400">Runs This Week</p>
        </div>
        <div>
          <p className="text-2xl font-semibold text-emerald-600 dark:text-emerald-400">
            {avgApprovalRate}%
          </p>
          <p className="text-xs text-slate-600 dark:text-slate-400">Avg Approval Rate</p>
        </div>
      </div>

      <div className="space-y-2 mb-4">
        {reports.slice(0, 5).map((report) => (
          <button
            key={report.id}
            onClick={() => onView?.(report.agentId)}
            className="w-full flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-800/50 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors text-left"
          >
            <div className="flex-1 min-w-0">
              <p className="text-sm font-medium text-slate-900 dark:text-slate-50 truncate mb-1">
                {report.agentName}
              </p>
              <p className="text-xs text-slate-600 dark:text-slate-400">
                {report.runsThisWeek} runs • {report.approvalRate}% approved • $
                {report.costThisWeek.toFixed(2)}
              </p>
            </div>
            <div
              className={`w-2 h-2 rounded-full flex-shrink-0 ${
                report.status === 'active' ? 'bg-emerald-500' : 'bg-slate-400'
              }`}
            />
          </button>
        ))}
      </div>

      {onGenerateSummary && (
        <button
          onClick={onGenerateSummary}
          className="w-full px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors"
        >
          Generate Weekly Summary
        </button>
      )}
    </div>
  )
}
