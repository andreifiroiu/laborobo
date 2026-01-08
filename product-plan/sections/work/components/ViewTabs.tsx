import { LayoutGrid, User, Kanban, Calendar, Archive } from 'lucide-react'
import type { WorkView } from '@/../product/sections/work/types'

interface ViewTabsProps {
  currentView: WorkView
  onViewChange?: (view: WorkView) => void
}

export function ViewTabs({ currentView, onViewChange }: ViewTabsProps) {
  const views: Array<{ value: WorkView; label: string; icon: React.ComponentType<{ size?: number }> }> = [
    { value: 'all_projects', label: 'All Projects', icon: LayoutGrid },
    { value: 'my_work', label: 'My Work', icon: User },
    { value: 'by_status', label: 'By Status', icon: Kanban },
    { value: 'calendar', label: 'Calendar', icon: Calendar },
    { value: 'archive', label: 'Archive', icon: Archive },
  ]

  return (
    <div className="border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
      <div className="flex gap-1 px-6">
        {views.map(view => {
          const Icon = view.icon
          const isActive = currentView === view.value

          return (
            <button
              key={view.value}
              onClick={() => onViewChange?.(view.value)}
              className={`
                flex items-center gap-2 px-4 py-3 text-sm font-medium border-b-2 transition-colors
                ${
                  isActive
                    ? 'border-indigo-600 dark:border-indigo-400 text-indigo-600 dark:text-indigo-400'
                    : 'border-transparent text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200'
                }
              `}
            >
              <Icon size={16} />
              {view.label}
            </button>
          )
        })}
      </div>
    </div>
  )
}
