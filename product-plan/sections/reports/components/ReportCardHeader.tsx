import { ReactNode } from 'react'
import type { TimeRange } from '@/../product/sections/reports/types'

interface ReportCardHeaderProps {
  title: string
  icon: ReactNode
  timeRange: TimeRange
  onTimeRangeChange?: (range: TimeRange) => void
  onExport?: () => void
}

export function ReportCardHeader({
  title,
  icon,
  timeRange,
  onTimeRangeChange,
  onExport,
}: ReportCardHeaderProps) {
  return (
    <div className="flex items-start justify-between mb-6">
      <div className="flex items-center gap-3">
        <div className="p-2 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-lg">
          {icon}
        </div>
        <h3 className="text-lg font-semibold text-slate-900 dark:text-slate-50">{title}</h3>
      </div>
      <div className="flex items-center gap-2">
        {/* Time Range Selector */}
        <select
          value={timeRange}
          onChange={(e) => onTimeRangeChange?.(e.target.value as TimeRange)}
          className="text-xs px-2 py-1 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500"
        >
          <option value="last-7-days">Last 7 days</option>
          <option value="last-30-days">Last 30 days</option>
          <option value="this-month">This month</option>
          <option value="custom">Custom</option>
        </select>
        {onExport && (
          <button
            onClick={onExport}
            className="p-1.5 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-50 hover:bg-slate-100 dark:hover:bg-slate-800 rounded transition-colors"
            title="Export"
          >
            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
              />
            </svg>
          </button>
        )}
      </div>
    </div>
  )
}
