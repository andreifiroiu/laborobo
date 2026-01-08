import { Clock, AlertCircle } from 'lucide-react'
import type { Project, WorkOrder } from '@/../product/sections/work/types'

interface CalendarEventProps {
  item: Project | WorkOrder
  type: 'project' | 'workOrder'
  onView?: () => void
}

export function CalendarEvent({ item, type, onView }: CalendarEventProps) {
  const isProject = type === 'project'
  const isWorkOrder = type === 'workOrder'

  // Calculate if overdue
  const dueDate = isProject
    ? (item as Project).targetEndDate
    : (item as WorkOrder).dueDate

  const isOverdue = dueDate ? new Date(dueDate) < new Date() : false

  // Get priority for work orders
  const priority = isWorkOrder ? (item as WorkOrder).priority : null

  // Color coding
  const getColorClasses = () => {
    if (isOverdue) {
      return 'bg-red-100 dark:bg-red-950/30 border-red-300 dark:border-red-800 text-red-900 dark:text-red-300'
    }

    if (isWorkOrder) {
      const wo = item as WorkOrder
      if (wo.priority === 'urgent') {
        return 'bg-red-50 dark:bg-red-950/20 border-red-200 dark:border-red-900 text-red-900 dark:text-red-300'
      }
      if (wo.priority === 'high') {
        return 'bg-orange-50 dark:bg-orange-950/20 border-orange-200 dark:border-orange-900 text-orange-900 dark:text-orange-300'
      }
      if (wo.status === 'in_review') {
        return 'bg-amber-50 dark:bg-amber-950/20 border-amber-200 dark:border-amber-900 text-amber-900 dark:text-amber-300'
      }
      if (wo.status === 'delivered') {
        return 'bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400'
      }
    }

    if (isProject) {
      return 'bg-indigo-50 dark:bg-indigo-950/20 border-indigo-200 dark:border-indigo-900 text-indigo-900 dark:text-indigo-300'
    }

    return 'bg-emerald-50 dark:bg-emerald-950/20 border-emerald-200 dark:border-emerald-900 text-emerald-900 dark:text-emerald-300'
  }

  const title = isProject ? (item as Project).name : (item as WorkOrder).title
  const status = isProject
    ? (item as Project).status
    : (item as WorkOrder).status

  return (
    <button
      onClick={onView}
      className={`w-full text-left px-2 py-1 mb-1 rounded border text-xs font-medium transition-all hover:scale-[1.02] hover:shadow-sm ${getColorClasses()}`}
    >
      <div className="flex items-start gap-1">
        {isOverdue && <AlertCircle className="w-3 h-3 shrink-0 mt-0.5" />}
        <div className="flex-1 min-w-0">
          <div className="truncate font-semibold">{title}</div>
          <div className="flex items-center gap-1 mt-0.5 opacity-75">
            <Clock className="w-2.5 h-2.5" />
            <span className="text-[10px] uppercase tracking-wide">{status}</span>
            {priority && (
              <>
                <span className="text-[8px]">•</span>
                <span className="text-[10px] uppercase tracking-wide">{priority}</span>
              </>
            )}
          </div>
        </div>
      </div>
    </button>
  )
}
