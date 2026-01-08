import { Activity as ActivityIcon, CheckCircle2, FileCheck, MessageSquare, AlertTriangle, Clock, Folder } from 'lucide-react'
import type { Activity } from '@/../product/sections/today/types'

interface ActivityFeedProps {
  activities: Activity[]
  onViewActivity?: (id: string) => void
}

export function ActivityFeed({ activities, onViewActivity }: ActivityFeedProps) {
  const getActivityIcon = (type: Activity['type']) => {
    switch (type) {
      case 'task_completed':
        return <CheckCircle2 className="w-4 h-4" />
      case 'approval_created':
        return <FileCheck className="w-4 h-4" />
      case 'comment_added':
        return <MessageSquare className="w-4 h-4" />
      case 'deliverable_submitted':
        return <FileCheck className="w-4 h-4" />
      case 'blocker_flagged':
        return <AlertTriangle className="w-4 h-4" />
      case 'task_started':
        return <Clock className="w-4 h-4" />
      case 'time_logged':
        return <Clock className="w-4 h-4" />
      case 'work_order_created':
        return <Folder className="w-4 h-4" />
      default:
        return <ActivityIcon className="w-4 h-4" />
    }
  }

  const getActivityColor = (type: Activity['type']) => {
    switch (type) {
      case 'task_completed':
        return 'bg-emerald-100 text-emerald-600 dark:bg-emerald-950/30 dark:text-emerald-400'
      case 'approval_created':
        return 'bg-indigo-100 text-indigo-600 dark:bg-indigo-950/30 dark:text-indigo-400'
      case 'comment_added':
        return 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'
      case 'deliverable_submitted':
        return 'bg-emerald-100 text-emerald-600 dark:bg-emerald-950/30 dark:text-emerald-400'
      case 'blocker_flagged':
        return 'bg-red-100 text-red-600 dark:bg-red-950/30 dark:text-red-400'
      case 'task_started':
        return 'bg-indigo-100 text-indigo-600 dark:bg-indigo-950/30 dark:text-indigo-400'
      case 'time_logged':
        return 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'
      case 'work_order_created':
        return 'bg-amber-100 text-amber-600 dark:bg-amber-950/30 dark:text-amber-400'
      default:
        return 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'
    }
  }

  const formatTimestamp = (timestamp: string) => {
    const date = new Date(timestamp)
    const now = new Date()
    const diffMs = now.getTime() - date.getTime()
    const diffMins = Math.floor(diffMs / 60000)
    const diffHours = Math.floor(diffMs / 3600000)
    const diffDays = Math.floor(diffMs / 86400000)

    if (diffMins < 1) return 'Just now'
    if (diffMins < 60) return `${diffMins}m ago`
    if (diffHours < 24) return `${diffHours}h ago`
    if (diffDays < 7) return `${diffDays}d ago`
    return date.toLocaleDateString()
  }

  return (
    <div className="rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 overflow-hidden">
      <div className="p-6 border-b border-slate-200 dark:border-slate-800">
        <div className="flex items-center gap-3">
          <div className="w-10 h-10 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 flex items-center justify-center">
            <ActivityIcon className="w-5 h-5" />
          </div>
          <div>
            <h3 className="text-lg font-semibold text-slate-900 dark:text-white">Recent Activity</h3>
            <p className="text-sm text-slate-600 dark:text-slate-400">What's happened recently</p>
          </div>
        </div>
      </div>

      <div className="divide-y divide-slate-200 dark:divide-slate-800 max-h-96 overflow-y-auto">
        {activities.length === 0 ? (
          <div className="p-8 text-center">
            <div className="w-16 h-16 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center mx-auto mb-3">
              <ActivityIcon className="w-8 h-8" />
            </div>
            <p className="text-slate-600 dark:text-slate-400 font-medium">No recent activity</p>
            <p className="text-sm text-slate-500 dark:text-slate-500">Activity will appear here</p>
          </div>
        ) : (
          activities.map((activity) => (
            <button
              key={activity.id}
              onClick={() => onViewActivity?.(activity.id)}
              className="w-full p-4 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors text-left group"
            >
              <div className="flex items-start gap-3">
                <div className={`flex-shrink-0 w-8 h-8 rounded-lg ${getActivityColor(activity.type)} flex items-center justify-center mt-0.5`}>
                  {getActivityIcon(activity.type)}
                </div>
                <div className="flex-1 min-w-0">
                  <div className="flex items-start justify-between gap-2 mb-1">
                    <h4 className="font-medium text-slate-900 dark:text-white text-sm">
                      {activity.title}
                    </h4>
                    <span className="text-xs text-slate-500 dark:text-slate-500 whitespace-nowrap">
                      {formatTimestamp(activity.timestamp)}
                    </span>
                  </div>
                  <p className="text-sm text-slate-600 dark:text-slate-400 mb-2">
                    {activity.description}
                  </p>
                  <div className="flex items-center gap-3 text-xs text-slate-500 dark:text-slate-500">
                    <span>{activity.projectTitle}</span>
                    <span>•</span>
                    <span>{activity.workOrderTitle}</span>
                  </div>
                </div>
              </div>
            </button>
          ))
        )}
      </div>
    </div>
  )
}
