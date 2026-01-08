import { Calendar, Clock, Tag, User, CheckCircle2 } from 'lucide-react'
import type { Project } from '@/../product/sections/work/types'

interface ArchiveProjectCardProps {
  project: Project
  workOrderCount: number
  taskCount: number
  onView?: () => void
  onRestore?: () => void
}

export function ArchiveProjectCard({
  project,
  workOrderCount,
  taskCount,
  onView,
  onRestore,
}: ArchiveProjectCardProps) {
  const statusColors = {
    completed: 'bg-emerald-100 dark:bg-emerald-950/30 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-900',
    archived: 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700',
    on_hold: 'bg-amber-100 dark:bg-amber-950/30 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-900',
    active: 'bg-indigo-100 dark:bg-indigo-950/30 text-indigo-700 dark:text-indigo-300 border-indigo-200 dark:border-indigo-900',
  }

  const progressPercentage = Math.round(project.progress * 100)
  const isCompleted = project.status === 'completed'
  const completedDate = project.targetEndDate
    ? new Date(project.targetEndDate).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
      })
    : null

  const duration = project.startDate && project.targetEndDate
    ? Math.ceil(
        (new Date(project.targetEndDate).getTime() - new Date(project.startDate).getTime()) /
          (1000 * 60 * 60 * 24)
      )
    : null

  const budgetVariance = project.budgetHours
    ? ((project.actualHours - project.budgetHours) / project.budgetHours) * 100
    : null

  return (
    <div className="group relative bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-6 hover:border-slate-300 dark:hover:border-slate-700 transition-all hover:shadow-lg">
      {/* Status Badge */}
      <div className="absolute top-4 right-4">
        <span
          className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium border ${
            statusColors[project.status]
          }`}
        >
          {isCompleted && <CheckCircle2 className="w-3.5 h-3.5" />}
          {project.status === 'completed' ? 'Completed' : 'Archived'}
        </span>
      </div>

      {/* Header */}
      <div className="pr-24 mb-4">
        <button
          onClick={onView}
          className="text-lg font-bold text-slate-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors text-left mb-1"
        >
          {project.name}
        </button>
        <p className="text-sm text-slate-600 dark:text-slate-400 line-clamp-2">
          {project.description}
        </p>
      </div>

      {/* Metadata Grid */}
      <div className="grid grid-cols-2 gap-4 mb-4 pb-4 border-b border-slate-100 dark:border-slate-800">
        <div className="flex items-center gap-2 text-sm">
          <User className="w-4 h-4 text-slate-400 shrink-0" />
          <div className="min-w-0">
            <div className="text-xs text-slate-500 dark:text-slate-500 mb-0.5">Client</div>
            <div className="font-medium text-slate-900 dark:text-white truncate">
              {project.partyName}
            </div>
          </div>
        </div>

        <div className="flex items-center gap-2 text-sm">
          <User className="w-4 h-4 text-slate-400 shrink-0" />
          <div className="min-w-0">
            <div className="text-xs text-slate-500 dark:text-slate-500 mb-0.5">Owner</div>
            <div className="font-medium text-slate-900 dark:text-white truncate">
              {project.ownerName}
            </div>
          </div>
        </div>

        {completedDate && (
          <div className="flex items-center gap-2 text-sm">
            <Calendar className="w-4 h-4 text-slate-400 shrink-0" />
            <div className="min-w-0">
              <div className="text-xs text-slate-500 dark:text-slate-500 mb-0.5">Completed</div>
              <div className="font-medium text-slate-900 dark:text-white">{completedDate}</div>
            </div>
          </div>
        )}

        {duration && (
          <div className="flex items-center gap-2 text-sm">
            <Clock className="w-4 h-4 text-slate-400 shrink-0" />
            <div className="min-w-0">
              <div className="text-xs text-slate-500 dark:text-slate-500 mb-0.5">Duration</div>
              <div className="font-medium text-slate-900 dark:text-white">
                {duration} {duration === 1 ? 'day' : 'days'}
              </div>
            </div>
          </div>
        )}
      </div>

      {/* Stats */}
      <div className="grid grid-cols-3 gap-4 mb-4">
        <div className="text-center">
          <div className="text-2xl font-bold text-indigo-600 dark:text-indigo-400">
            {workOrderCount}
          </div>
          <div className="text-xs text-slate-500 dark:text-slate-500">Work Orders</div>
        </div>
        <div className="text-center">
          <div className="text-2xl font-bold text-emerald-600 dark:text-emerald-400">
            {taskCount}
          </div>
          <div className="text-xs text-slate-500 dark:text-slate-500">Tasks</div>
        </div>
        <div className="text-center">
          <div className="text-2xl font-bold text-slate-900 dark:text-white">
            {project.actualHours}h
          </div>
          <div className="text-xs text-slate-500 dark:text-slate-500">Actual Hours</div>
        </div>
      </div>

      {/* Budget Variance */}
      {budgetVariance !== null && (
        <div className="mb-4">
          <div className="flex items-center justify-between text-xs mb-1">
            <span className="text-slate-600 dark:text-slate-400">Budget Performance</span>
            <span
              className={`font-medium ${
                budgetVariance <= 0
                  ? 'text-emerald-600 dark:text-emerald-400'
                  : budgetVariance <= 10
                  ? 'text-amber-600 dark:text-amber-400'
                  : 'text-red-600 dark:text-red-400'
              }`}
            >
              {budgetVariance > 0 ? '+' : ''}
              {budgetVariance.toFixed(0)}%
            </span>
          </div>
          <div className="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-500">
            <span>Budget: {project.budgetHours}h</span>
            <span>•</span>
            <span>Actual: {project.actualHours}h</span>
          </div>
        </div>
      )}

      {/* Progress Bar */}
      <div className="mb-4">
        <div className="flex items-center justify-between text-xs mb-2">
          <span className="text-slate-600 dark:text-slate-400">Progress</span>
          <span className="font-medium text-slate-900 dark:text-white">{progressPercentage}%</span>
        </div>
        <div className="h-2 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
          <div
            className="h-full bg-emerald-500 dark:bg-emerald-400 rounded-full transition-all"
            style={{ width: `${progressPercentage}%` }}
          />
        </div>
      </div>

      {/* Tags */}
      {project.tags.length > 0 && (
        <div className="flex flex-wrap gap-2 mb-4">
          {project.tags.map(tag => (
            <span
              key={tag}
              className="inline-flex items-center gap-1 px-2 py-0.5 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded text-xs"
            >
              <Tag className="w-3 h-3" />
              {tag}
            </span>
          ))}
        </div>
      )}

      {/* Actions */}
      <div className="flex items-center gap-2 pt-4 border-t border-slate-100 dark:border-slate-800">
        <button
          onClick={onView}
          className="flex-1 px-4 py-2 bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white text-sm font-medium rounded-lg hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors"
        >
          View Details
        </button>
        {project.status === 'archived' && (
          <button
            onClick={onRestore}
            className="px-4 py-2 bg-indigo-600 dark:bg-indigo-500 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 transition-colors"
          >
            Restore
          </button>
        )}
      </div>
    </div>
  )
}
