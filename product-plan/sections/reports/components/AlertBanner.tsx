import type { AIInsight } from '@/../product/sections/reports/types'

interface AlertBannerProps {
  insight: AIInsight
  onView?: () => void
  onDismiss?: () => void
}

export function AlertBanner({ insight, onView, onDismiss }: AlertBannerProps) {
  return (
    <div className="flex items-start gap-4 p-4 bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-900/50 rounded-lg">
      <svg
        className="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          strokeLinecap="round"
          strokeLinejoin="round"
          strokeWidth={2}
          d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
        />
      </svg>
      <div className="flex-1 min-w-0">
        <h3 className="text-sm font-semibold text-red-900 dark:text-red-100 mb-1">
          {insight.title}
        </h3>
        <p className="text-sm text-red-800 dark:text-red-200">{insight.description}</p>
        {insight.recommendation && (
          <p className="text-sm text-red-700 dark:text-red-300 mt-2">
            <span className="font-medium">Recommended:</span> {insight.recommendation}
          </p>
        )}
      </div>
      <div className="flex items-center gap-2 flex-shrink-0">
        <button
          onClick={onView}
          className="text-sm font-medium text-red-700 dark:text-red-300 hover:text-red-900 dark:hover:text-red-100 transition-colors"
        >
          View Details
        </button>
        <button
          onClick={onDismiss}
          className="p-1 text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-100 hover:bg-red-100 dark:hover:bg-red-900/30 rounded transition-colors"
          title="Dismiss"
        >
          <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M6 18L18 6M6 6l12 12"
            />
          </svg>
        </button>
      </div>
    </div>
  )
}
