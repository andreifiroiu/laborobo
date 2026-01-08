import type { AIInsight, TimeRange } from '@/../product/sections/reports/types'
import { ReportCardHeader } from './ReportCardHeader'

interface AIInsightsCardProps {
  insights: AIInsight[]
  timeRange: TimeRange
  onTimeRangeChange?: (range: TimeRange) => void
  onViewInsight?: (id: string) => void
  onDismissInsight?: (id: string) => void
}

export function AIInsightsCard({
  insights,
  timeRange,
  onTimeRangeChange,
  onViewInsight,
  onDismissInsight,
}: AIInsightsCardProps) {
  const activeInsights = insights.filter((i) => !i.dismissed)
  const criticalCount = activeInsights.filter((i) => i.severity === 'critical').length
  const warningCount = activeInsights.filter((i) => i.severity === 'warning').length

  const getSeverityColor = (severity: string) => {
    const colors = {
      critical: 'text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-950/30',
      warning: 'text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/30',
      info: 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/30',
    }
    return colors[severity as keyof typeof colors] || colors.info
  }

  const getSeverityIcon = (severity: string) => {
    if (severity === 'critical') {
      return (
        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={2}
            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
          />
        </svg>
      )
    }
    if (severity === 'warning') {
      return (
        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={2}
            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
          />
        </svg>
      )
    }
    return (
      <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path
          strokeLinecap="round"
          strokeLinejoin="round"
          strokeWidth={2}
          d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
        />
      </svg>
    )
  }

  return (
    <div className="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-6 hover:shadow-lg transition-shadow">
      <ReportCardHeader
        title="AI Insights"
        icon={
          <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"
            />
          </svg>
        }
        timeRange={timeRange}
        onTimeRangeChange={onTimeRangeChange}
      />

      {/* Summary Stats */}
      <div className="grid grid-cols-3 gap-4 mb-6">
        <div>
          <p className="text-2xl font-semibold text-slate-900 dark:text-slate-50">
            {activeInsights.length}
          </p>
          <p className="text-xs text-slate-600 dark:text-slate-400">Total Insights</p>
        </div>
        <div>
          <p className="text-2xl font-semibold text-red-600 dark:text-red-400">
            {criticalCount}
          </p>
          <p className="text-xs text-slate-600 dark:text-slate-400">Critical</p>
        </div>
        <div>
          <p className="text-2xl font-semibold text-amber-600 dark:text-amber-400">
            {warningCount}
          </p>
          <p className="text-xs text-slate-600 dark:text-slate-400">Warnings</p>
        </div>
      </div>

      {/* Insights List */}
      <div className="space-y-3 max-h-[400px] overflow-y-auto">
        {activeInsights.length === 0 ? (
          <div className="text-center py-8">
            <svg
              className="w-12 h-12 text-emerald-500 dark:text-emerald-400 mx-auto mb-3"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
              />
            </svg>
            <p className="text-sm text-slate-600 dark:text-slate-400">
              All clear! No issues detected.
            </p>
          </div>
        ) : (
          activeInsights.map((insight) => (
            <div
              key={insight.id}
              className={`p-3 rounded-lg border ${
                insight.severity === 'critical'
                  ? 'border-red-200 dark:border-red-900/50'
                  : insight.severity === 'warning'
                    ? 'border-amber-200 dark:border-amber-900/50'
                    : 'border-blue-200 dark:border-blue-900/50'
              }`}
            >
              <div className="flex items-start gap-3">
                <div className={`p-1.5 rounded ${getSeverityColor(insight.severity)}`}>
                  {getSeverityIcon(insight.severity)}
                </div>
                <div className="flex-1 min-w-0">
                  <div className="flex items-start justify-between gap-2 mb-1">
                    <h4 className="text-sm font-medium text-slate-900 dark:text-slate-50">
                      {insight.title}
                    </h4>
                    <button
                      onClick={() => onDismissInsight?.(insight.id)}
                      className="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors flex-shrink-0"
                      title="Dismiss"
                    >
                      <svg
                        className="w-3.5 h-3.5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                      >
                        <path
                          strokeLinecap="round"
                          strokeLinejoin="round"
                          strokeWidth={2}
                          d="M6 18L18 6M6 6l12 12"
                        />
                      </svg>
                    </button>
                  </div>
                  <p className="text-xs text-slate-600 dark:text-slate-400 mb-2">
                    {insight.description}
                  </p>
                  {insight.actionRequired && (
                    <button
                      onClick={() => onViewInsight?.(insight.id)}
                      className="text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 transition-colors"
                    >
                      Take Action →
                    </button>
                  )}
                </div>
              </div>
            </div>
          ))
        )}
      </div>
    </div>
  )
}
