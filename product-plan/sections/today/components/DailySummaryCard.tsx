import { Sparkles, RefreshCw } from 'lucide-react'
import type { DailySummary } from '@/../product/sections/today/types'

interface DailySummaryCardProps {
  summary: DailySummary
  onRefresh?: () => void
}

export function DailySummaryCard({ summary, onRefresh }: DailySummaryCardProps) {
  return (
    <div className="relative overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-500 to-indigo-600 dark:from-indigo-600 dark:dark:to-indigo-700 p-6 shadow-lg">
      {/* Decorative background pattern */}
      <div className="absolute inset-0 opacity-10">
        <div className="absolute top-0 right-0 w-64 h-64 bg-white rounded-full blur-3xl transform translate-x-20 -translate-y-20" />
        <div className="absolute bottom-0 left-0 w-48 h-48 bg-white rounded-full blur-3xl transform -translate-x-10 translate-y-10" />
      </div>

      <div className="relative">
        <div className="flex items-start justify-between mb-4">
          <div className="flex items-center gap-2">
            <Sparkles className="w-6 h-6 text-emerald-300" />
            <h2 className="text-lg font-semibold text-white">Daily Summary</h2>
          </div>
          <button
            onClick={() => onRefresh?.()}
            className="p-2 rounded-lg bg-white/10 hover:bg-white/20 text-white transition-colors"
            aria-label="Refresh summary"
          >
            <RefreshCw className="w-4 h-4" />
          </button>
        </div>

        <p className="text-white/90 leading-relaxed mb-6">{summary.summary}</p>

        {summary.priorities.length > 0 && (
          <div className="space-y-3 mb-4">
            <h3 className="text-sm font-medium text-white/80">Top Priorities</h3>
            <ul className="space-y-2">
              {summary.priorities.map((priority, index) => (
                <li key={index} className="flex items-start gap-3 text-white/90 text-sm">
                  <span className="flex-shrink-0 w-5 h-5 rounded-full bg-emerald-400/20 text-emerald-300 flex items-center justify-center text-xs font-medium mt-0.5">
                    {index + 1}
                  </span>
                  <span className="flex-1">{priority}</span>
                </li>
              ))}
            </ul>
          </div>
        )}

        {summary.suggestedFocus && (
          <div className="pt-4 border-t border-white/20">
            <p className="text-sm text-white/80 italic">{summary.suggestedFocus}</p>
          </div>
        )}
      </div>
    </div>
  )
}
