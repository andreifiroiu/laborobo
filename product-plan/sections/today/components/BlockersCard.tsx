import { AlertTriangle, Clock as ClockIcon, ChevronRight } from 'lucide-react'
import type { Blocker } from '@/../product/sections/today/types'

interface BlockersCardProps {
  blockers: Blocker[]
  onViewBlocker?: (id: string) => void
}

export function BlockersCard({ blockers, onViewBlocker }: BlockersCardProps) {
  const reasonLabels = {
    waiting_on_external: 'Waiting on External',
    missing_information: 'Missing Info',
    technical_issue: 'Technical Issue',
    waiting_on_approval: 'Waiting on Approval',
  }

  const reasonColors = {
    waiting_on_external: 'bg-amber-100 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400',
    missing_information: 'bg-red-100 text-red-700 dark:bg-red-950/30 dark:text-red-400',
    technical_issue: 'bg-orange-100 text-orange-700 dark:bg-orange-950/30 dark:text-orange-400',
    waiting_on_approval: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950/30 dark:text-indigo-400',
  }

  const formatDuration = (blockedSince: string) => {
    const start = new Date(blockedSince)
    const now = new Date()
    const days = Math.floor((now.getTime() - start.getTime()) / (1000 * 60 * 60 * 24))
    if (days === 0) return 'Today'
    if (days === 1) return '1 day'
    return `${days} days`
  }

  return (
    <div className="rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 overflow-hidden">
      <div className="p-6 border-b border-slate-200 dark:border-slate-800">
        <div className="flex items-center gap-3">
          <div className="w-10 h-10 rounded-lg bg-red-100 dark:bg-red-950/30 text-red-600 dark:text-red-400 flex items-center justify-center">
            <AlertTriangle className="w-5 h-5" />
          </div>
          <div>
            <h3 className="text-lg font-semibold text-slate-900 dark:text-white">Blockers</h3>
            <p className="text-sm text-slate-600 dark:text-slate-400">Items needing attention</p>
          </div>
        </div>
      </div>

      <div className="divide-y divide-slate-200 dark:divide-slate-800">
        {blockers.length === 0 ? (
          <div className="p-8 text-center">
            <div className="w-16 h-16 rounded-full bg-emerald-100 dark:bg-emerald-950/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mx-auto mb-3">
              <AlertTriangle className="w-8 h-8" />
            </div>
            <p className="text-slate-600 dark:text-slate-400 font-medium">No blockers</p>
            <p className="text-sm text-slate-500 dark:text-slate-500">Everything flowing smoothly!</p>
          </div>
        ) : (
          blockers.map((blocker) => (
            <button
              key={blocker.id}
              onClick={() => onViewBlocker?.(blocker.id)}
              className="w-full p-4 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors text-left group"
            >
              <div className="flex items-start justify-between gap-4">
                <div className="flex-1 min-w-0">
                  <div className="flex items-center gap-2 mb-2 flex-wrap">
                    <span className={`inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium ${reasonColors[blocker.reason]}`}>
                      {reasonLabels[blocker.reason]}
                    </span>
                    <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-medium bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400">
                      <ClockIcon className="w-3 h-3" />
                      {formatDuration(blocker.blockedSince)}
                    </span>
                  </div>
                  <h4 className="font-medium text-slate-900 dark:text-white mb-1">
                    {blocker.title}
                  </h4>
                  <p className="text-sm text-slate-600 dark:text-slate-400 mb-2">
                    {blocker.blockerDetails}
                  </p>
                  <div className="flex items-center gap-4 text-xs text-slate-500 dark:text-slate-500">
                    <span>{blocker.projectTitle}</span>
                    <span>•</span>
                    <span>Assigned to {blocker.assignedTo}</span>
                  </div>
                </div>
                <ChevronRight className="w-5 h-5 text-slate-400 group-hover:text-slate-600 dark:group-hover:text-slate-300 transition-colors flex-shrink-0 mt-1" />
              </div>
            </button>
          ))
        )}
      </div>
    </div>
  )
}
