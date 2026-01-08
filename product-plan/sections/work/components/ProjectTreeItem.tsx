import { useState } from 'react'
import { ChevronRight, ChevronDown, Folder, MoreVertical, Plus } from 'lucide-react'
import type { Project, WorkOrder, Task } from '@/../product/sections/work/types'

interface ProjectTreeItemProps {
  project: Project
  workOrders: WorkOrder[]
  tasks: Task[]
  onViewProject?: (projectId: string) => void
  onCreateWorkOrder?: (projectId: string) => void
  onViewWorkOrder?: (workOrderId: string) => void
  onCreateTask?: (workOrderId: string) => void
  onViewTask?: (taskId: string) => void
}

export function ProjectTreeItem({
  project,
  workOrders,
  tasks,
  onViewProject,
  onCreateWorkOrder,
  onViewWorkOrder,
  onCreateTask,
  onViewTask,
}: ProjectTreeItemProps) {
  const [isExpanded, setIsExpanded] = useState(true)

  const projectWorkOrders = workOrders.filter(wo => wo.projectId === project.id)

  const statusColors = {
    active: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400',
    on_hold: 'bg-amber-100 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400',
    completed: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400',
    archived: 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-500',
  }

  return (
    <div className="border-l-2 border-slate-200 dark:border-slate-800">
      {/* Project Row */}
      <div className="group relative flex items-center gap-2 py-2 px-3 hover:bg-slate-50 dark:hover:bg-slate-900/50 transition-colors">
        <button
          onClick={() => setIsExpanded(!isExpanded)}
          className="flex-shrink-0 w-5 h-5 flex items-center justify-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors"
        >
          {isExpanded ? <ChevronDown size={16} /> : <ChevronRight size={16} />}
        </button>

        <Folder className="flex-shrink-0 w-5 h-5 text-indigo-500 dark:text-indigo-400" />

        <button
          onClick={() => onViewProject?.(project.id)}
          className="flex-1 min-w-0 text-left"
        >
          <div className="flex items-center gap-2 flex-wrap">
            <span className="font-semibold text-slate-900 dark:text-white truncate">
              {project.name}
            </span>
            <span className={`text-xs px-2 py-0.5 rounded-full font-medium ${statusColors[project.status]}`}>
              {project.status.replace('_', ' ')}
            </span>
            <span className="text-sm text-slate-500 dark:text-slate-400">
              {project.partyName}
            </span>
          </div>
          <div className="flex items-center gap-4 text-xs text-slate-500 dark:text-slate-400 mt-0.5">
            <span>{projectWorkOrders.length} work orders</span>
            {project.budgetHours && (
              <span>{project.actualHours}/{project.budgetHours}h</span>
            )}
            <span>{project.progress}% complete</span>
          </div>
        </button>

        <button
          onClick={() => onCreateWorkOrder?.(project.id)}
          className="opacity-0 group-hover:opacity-100 transition-opacity flex-shrink-0 p-1 rounded hover:bg-slate-200 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400"
          title="Add work order"
        >
          <Plus size={16} />
        </button>

        <button
          className="flex-shrink-0 p-1 rounded hover:bg-slate-200 dark:hover:bg-slate-800 text-slate-400"
          title="More options"
        >
          <MoreVertical size={16} />
        </button>
      </div>

      {/* Work Orders */}
      {isExpanded && projectWorkOrders.length > 0 && (
        <div className="ml-7">
          {projectWorkOrders.map(workOrder => (
            <WorkOrderTreeItem
              key={workOrder.id}
              workOrder={workOrder}
              tasks={tasks.filter(t => t.workOrderId === workOrder.id)}
              onViewWorkOrder={onViewWorkOrder}
              onCreateTask={onCreateTask}
              onViewTask={onViewTask}
            />
          ))}
        </div>
      )}
    </div>
  )
}

interface WorkOrderTreeItemProps {
  workOrder: WorkOrder
  tasks: Task[]
  onViewWorkOrder?: (workOrderId: string) => void
  onCreateTask?: (workOrderId: string) => void
  onViewTask?: (taskId: string) => void
}

function WorkOrderTreeItem({
  workOrder,
  tasks,
  onViewWorkOrder,
  onCreateTask,
  onViewTask,
}: WorkOrderTreeItemProps) {
  const [isExpanded, setIsExpanded] = useState(false)

  const statusColors = {
    draft: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400',
    active: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950/30 dark:text-indigo-400',
    in_review: 'bg-amber-100 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400',
    approved: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400',
    delivered: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400',
  }

  const priorityColors = {
    low: 'text-slate-500',
    medium: 'text-amber-600 dark:text-amber-500',
    high: 'text-orange-600 dark:text-orange-500',
    urgent: 'text-red-600 dark:text-red-500',
  }

  return (
    <div className="border-l-2 border-slate-200 dark:border-slate-800">
      {/* Work Order Row */}
      <div className="group relative flex items-center gap-2 py-2 px-3 hover:bg-slate-50 dark:hover:bg-slate-900/50 transition-colors">
        <button
          onClick={() => setIsExpanded(!isExpanded)}
          className="flex-shrink-0 w-5 h-5 flex items-center justify-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors"
        >
          {tasks.length > 0 ? (
            isExpanded ? <ChevronDown size={16} /> : <ChevronRight size={16} />
          ) : (
            <div className="w-1 h-1 bg-slate-300 dark:bg-slate-700 rounded-full" />
          )}
        </button>

        <button
          onClick={() => onViewWorkOrder?.(workOrder.id)}
          className="flex-1 min-w-0 text-left"
        >
          <div className="flex items-center gap-2 flex-wrap">
            <span className="font-medium text-slate-900 dark:text-white truncate">
              {workOrder.title}
            </span>
            <span className={`text-xs px-2 py-0.5 rounded-full font-medium ${statusColors[workOrder.status]}`}>
              {workOrder.status.replace('_', ' ')}
            </span>
            <span className={`text-xs font-medium ${priorityColors[workOrder.priority]}`}>
              {workOrder.priority}
            </span>
          </div>
          <div className="flex items-center gap-4 text-xs text-slate-500 dark:text-slate-400 mt-0.5">
            <span>{workOrder.assignedToName}</span>
            <span>{tasks.length} tasks</span>
            <span>{workOrder.actualHours}/{workOrder.estimatedHours}h</span>
          </div>
        </button>

        <button
          onClick={() => onCreateTask?.(workOrder.id)}
          className="opacity-0 group-hover:opacity-100 transition-opacity flex-shrink-0 p-1 rounded hover:bg-slate-200 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400"
          title="Add task"
        >
          <Plus size={16} />
        </button>

        <button
          className="flex-shrink-0 p-1 rounded hover:bg-slate-200 dark:hover:bg-slate-800 text-slate-400"
          title="More options"
        >
          <MoreVertical size={16} />
        </button>
      </div>

      {/* Tasks */}
      {isExpanded && tasks.length > 0 && (
        <div className="ml-7">
          {tasks.map(task => (
            <TaskTreeItem
              key={task.id}
              task={task}
              onViewTask={onViewTask}
            />
          ))}
        </div>
      )}
    </div>
  )
}

interface TaskTreeItemProps {
  task: Task
  onViewTask?: (taskId: string) => void
}

function TaskTreeItem({ task, onViewTask }: TaskTreeItemProps) {
  const statusColors = {
    todo: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400',
    in_progress: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950/30 dark:text-indigo-400',
    done: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400',
  }

  const completedItems = task.checklistItems.filter(item => item.completed).length
  const totalItems = task.checklistItems.length

  return (
    <div className="group relative flex items-center gap-2 py-2 px-3 hover:bg-slate-50 dark:hover:bg-slate-900/50 transition-colors">
      <div className="flex-shrink-0 w-5 h-5" />

      <button
        onClick={() => onViewTask?.(task.id)}
        className="flex-1 min-w-0 text-left"
      >
        <div className="flex items-center gap-2 flex-wrap">
          <span className={`text-sm ${task.isBlocked ? 'line-through text-slate-500 dark:text-slate-500' : 'text-slate-900 dark:text-white'}`}>
            {task.title}
          </span>
          <span className={`text-xs px-2 py-0.5 rounded-full font-medium ${statusColors[task.status]}`}>
            {task.status.replace('_', ' ')}
          </span>
          {task.isBlocked && (
            <span className="text-xs px-2 py-0.5 rounded-full font-medium bg-red-100 text-red-700 dark:bg-red-950/30 dark:text-red-400">
              blocked
            </span>
          )}
        </div>
        <div className="flex items-center gap-4 text-xs text-slate-500 dark:text-slate-400 mt-0.5">
          <span>{task.assignedToName}</span>
          {totalItems > 0 && (
            <span>{completedItems}/{totalItems} checklist items</span>
          )}
          <span>{task.actualHours}/{task.estimatedHours}h</span>
        </div>
      </button>

      <button
        className="flex-shrink-0 p-1 rounded hover:bg-slate-200 dark:hover:bg-slate-800 text-slate-400"
        title="More options"
      >
        <MoreVertical size={16} />
      </button>
    </div>
  )
}
