import { CheckSquare, AlertCircle, Clock, ChevronRight } from 'lucide-react'
import type { Task } from '@/../product/sections/today/types'

interface TasksCardProps {
  tasks: Task[]
  onViewTask?: (id: string) => void
}

export function TasksCard({ tasks, onViewTask }: TasksCardProps) {
  const priorityColors = {
    high: 'bg-red-100 text-red-700 dark:bg-red-950/30 dark:text-red-400',
    medium: 'bg-amber-100 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400',
    low: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400',
  }

  // Sort by overdue first, then by priority
  const sortedTasks = [...tasks].sort((a, b) => {
    if (a.isOverdue && !b.isOverdue) return -1
    if (!a.isOverdue && b.isOverdue) return 1
    const priorityOrder = { high: 0, medium: 1, low: 2 }
    return priorityOrder[a.priority] - priorityOrder[b.priority]
  })

  return (
    <div className="rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 overflow-hidden">
      <div className="p-6 border-b border-slate-200 dark:border-slate-800">
        <div className="flex items-center gap-3">
          <div className="w-10 h-10 rounded-lg bg-emerald-100 dark:bg-emerald-950/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
            <CheckSquare className="w-5 h-5" />
          </div>
          <div>
            <h3 className="text-lg font-semibold text-slate-900 dark:text-white">My Tasks Due</h3>
            <p className="text-sm text-slate-600 dark:text-slate-400">Today and overdue</p>
          </div>
        </div>
      </div>

      <div className="divide-y divide-slate-200 dark:divide-slate-800">
        {sortedTasks.length === 0 ? (
          <div className="p-8 text-center">
            <div className="w-16 h-16 rounded-full bg-emerald-100 dark:bg-emerald-950/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mx-auto mb-3">
              <CheckSquare className="w-8 h-8" />
            </div>
            <p className="text-slate-600 dark:text-slate-400 font-medium">No tasks due</p>
            <p className="text-sm text-slate-500 dark:text-slate-500">All caught up!</p>
          </div>
        ) : (
          sortedTasks.map((task) => (
            <button
              key={task.id}
              onClick={() => onViewTask?.(task.id)}
              className="w-full p-4 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors text-left group"
            >
              <div className="flex items-start justify-between gap-4">
                <div className="flex-1 min-w-0">
                  <div className="flex items-center gap-2 mb-1 flex-wrap">
                    {task.isOverdue && (
                      <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-medium bg-red-100 text-red-700 dark:bg-red-950/30 dark:text-red-400">
                        <AlertCircle className="w-3 h-3" />
                        Overdue
                      </span>
                    )}
                    <span className={`inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium ${priorityColors[task.priority]}`}>
                      {task.priority}
                    </span>
                    <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-medium bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400">
                      <Clock className="w-3 h-3" />
                      {task.estimatedHours}h
                    </span>
                  </div>
                  <h4 className="font-medium text-slate-900 dark:text-white mb-1">
                    {task.title}
                  </h4>
                  <p className="text-sm text-slate-600 dark:text-slate-400 line-clamp-1 mb-2">
                    {task.description}
                  </p>
                  <div className="flex items-center gap-4 text-xs text-slate-500 dark:text-slate-500">
                    <span>{task.projectTitle}</span>
                    <span>•</span>
                    <span>{task.workOrderTitle}</span>
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
