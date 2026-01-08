import { Calendar, TrendingUp, ChevronRight } from 'lucide-react'
import type { UpcomingDeadline } from '@/../product/sections/today/types'

interface UpcomingDeadlinesCardProps {
  deadlines: UpcomingDeadline[]
  onViewWorkOrder?: (id: string) => void
}

export function UpcomingDeadlinesCard({ deadlines, onViewWorkOrder }: UpcomingDeadlinesCardProps) {
  const statusColors = {
    draft: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400',
    planning: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950/30 dark:text-indigo-400',
    in_progress: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400',
    review: 'bg-amber-100 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400',
    completed: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400',
  }

  const statusLabels = {
    draft: 'Draft',
    planning: 'Planning',
    in_progress: 'In Progress',
    review: 'Review',
    completed: 'Completed',
  }

  return (
    <div className="rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 overflow-hidden">
      <div className="p-6 border-b border-slate-200 dark:border-slate-800">
        <div className="flex items-center gap-3">
          <div className="w-10 h-10 rounded-lg bg-amber-100 dark:bg-amber-950/30 text-amber-600 dark:text-amber-400 flex items-center justify-center">
            <Calendar className="w-5 h-5" />
          </div>
          <div>
            <h3 className="text-lg font-semibold text-slate-900 dark:text-white">Upcoming Deadlines</h3>
            <p className="text-sm text-slate-600 dark:text-slate-400">Next 7 days</p>
          </div>
        </div>
      </div>

      <div className="divide-y divide-slate-200 dark:divide-slate-800">
        {deadlines.length === 0 ? (
          <div className="p-8 text-center">
            <div className="w-16 h-16 rounded-full bg-emerald-100 dark:bg-emerald-950/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mx-auto mb-3">
              <Calendar className="w-8 h-8" />
            </div>
            <p className="text-slate-600 dark:text-slate-400 font-medium">No upcoming deadlines</p>
            <p className="text-sm text-slate-500 dark:text-slate-500">Clear schedule ahead!</p>
          </div>
        ) : (
          deadlines.map((deadline) => (
            <button
              key={deadline.id}
              onClick={() => onViewWorkOrder?.(deadline.id)}
              className="w-full p-4 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors text-left group"
            >
              <div className="flex items-start justify-between gap-4">
                <div className="flex-1 min-w-0">
                  <div className="flex items-center gap-2 mb-2 flex-wrap">
                    <span className={`inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium ${statusColors[deadline.status]}`}>
                      {statusLabels[deadline.status]}
                    </span>
                    <span className="text-xs text-slate-600 dark:text-slate-400 font-medium">
                      Due in {deadline.daysUntilDue} {deadline.daysUntilDue === 1 ? 'day' : 'days'}
                    </span>
                  </div>
                  <h4 className="font-medium text-slate-900 dark:text-white mb-1">
                    {deadline.title}
                  </h4>
                  <p className="text-sm text-slate-600 dark:text-slate-400 mb-3">
                    {deadline.projectTitle}
                  </p>

                  {/* Progress bar */}
                  <div className="mb-2">
                    <div className="flex items-center justify-between text-xs text-slate-600 dark:text-slate-400 mb-1">
                      <span>Progress</span>
                      <span className="font-medium">{deadline.progress}%</span>
                    </div>
                    <div className="h-2 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                      <div
                        className="h-full bg-gradient-to-r from-emerald-500 to-emerald-400 rounded-full transition-all"
                        style={{ width: `${deadline.progress}%` }}
                      />
                    </div>
                  </div>

                  <div className="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-500">
                    <TrendingUp className="w-3 h-3" />
                    <span>{deadline.assignedTeam.join(', ')}</span>
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
