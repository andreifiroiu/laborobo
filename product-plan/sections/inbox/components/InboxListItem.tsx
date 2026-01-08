import { Check, Clock, AlertTriangle, MessageSquare, FileText, CheckCircle, Flag, User, Sparkles } from 'lucide-react'
import type { InboxItem } from '@/../product/sections/inbox/types'

interface InboxListItemProps {
  item: InboxItem
  isSelected: boolean
  onSelect?: () => void
  onView?: () => void
}

export function InboxListItem({ item, isSelected, onSelect, onView }: InboxListItemProps) {
  // Type icon mapping
  const typeIcons = {
    agent_draft: FileText,
    approval: CheckCircle,
    flag: Flag,
    mention: MessageSquare,
  }

  const TypeIcon = typeIcons[item.type]

  // Type colors
  const typeColors = {
    agent_draft: 'bg-indigo-100 dark:bg-indigo-950/30 text-indigo-700 dark:text-indigo-300 border-indigo-200 dark:border-indigo-900',
    approval: 'bg-emerald-100 dark:bg-emerald-950/30 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-900',
    flag: 'bg-red-100 dark:bg-red-950/30 text-red-700 dark:text-red-300 border-red-200 dark:border-red-900',
    mention: 'bg-amber-100 dark:bg-amber-950/30 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-900',
  }

  // Urgency indicator
  const urgencyColors = {
    urgent: 'bg-red-500',
    high: 'bg-orange-500',
    normal: 'bg-slate-300 dark:bg-slate-600',
  }

  // AI confidence badge colors
  const confidenceColors = {
    high: 'text-emerald-600 dark:text-emerald-400',
    medium: 'text-amber-600 dark:text-amber-400',
    low: 'text-red-600 dark:text-red-400',
  }

  // Format waiting time
  const formatWaitingTime = (hours: number) => {
    if (hours < 1) return 'Just now'
    if (hours === 1) return '1 hour'
    if (hours < 24) return `${hours} hours`
    const days = Math.floor(hours / 24)
    return days === 1 ? '1 day' : `${days} days`
  }

  return (
    <div
      className={`group relative flex items-start gap-4 p-4 border-b border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-900/50 transition-colors cursor-pointer ${
        isSelected ? 'bg-indigo-50 dark:bg-indigo-950/20' : ''
      }`}
      onClick={onView}
    >
      {/* Urgency Indicator */}
      <div className={`absolute left-0 top-0 bottom-0 w-1 ${urgencyColors[item.urgency]}`} />

      {/* Checkbox */}
      <div className="pl-3 pt-1" onClick={(e) => e.stopPropagation()}>
        <input
          type="checkbox"
          checked={isSelected}
          onChange={onSelect}
          className="w-4 h-4 rounded border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-indigo-500 focus:ring-offset-0"
        />
      </div>

      {/* Type Icon */}
      <div className={`flex-shrink-0 p-2 rounded-lg border ${typeColors[item.type]}`}>
        <TypeIcon className="w-4 h-4" />
      </div>

      {/* Content */}
      <div className="flex-1 min-w-0">
        {/* Header */}
        <div className="flex items-start gap-2 mb-2">
          <h3 className="flex-1 font-semibold text-slate-900 dark:text-white text-sm line-clamp-1">
            {item.title}
          </h3>
          {item.qaValidation === 'passed' && (
            <div className="flex-shrink-0 flex items-center gap-1 px-2 py-0.5 bg-emerald-50 dark:bg-emerald-950/20 text-emerald-700 dark:text-emerald-300 rounded text-xs">
              <Check className="w-3 h-3" />
              <span>QA Pass</span>
            </div>
          )}
        </div>

        {/* Preview */}
        <p className="text-sm text-slate-600 dark:text-slate-400 line-clamp-2 mb-3">
          {item.contentPreview}
        </p>

        {/* Metadata */}
        <div className="flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-slate-500 dark:text-slate-500">
          {/* Source */}
          <div className="flex items-center gap-1.5">
            {item.sourceType === 'ai_agent' ? (
              <Sparkles className="w-3.5 h-3.5" />
            ) : (
              <User className="w-3.5 h-3.5" />
            )}
            <span>{item.sourceName}</span>
          </div>

          {/* Related work */}
          {item.relatedWorkOrderTitle && (
            <div className="flex items-center gap-1.5">
              <span className="text-slate-400">→</span>
              <span className="font-medium text-slate-700 dark:text-slate-300">
                {item.relatedWorkOrderTitle}
              </span>
            </div>
          )}

          {/* AI Confidence */}
          {item.aiConfidence && (
            <div className={`flex items-center gap-1 font-medium ${confidenceColors[item.aiConfidence]}`}>
              <span className="uppercase tracking-wide">{item.aiConfidence}</span>
              <span className="text-slate-400">conf</span>
            </div>
          )}

          {/* Waiting time */}
          <div className="flex items-center gap-1.5">
            <Clock className="w-3.5 h-3.5" />
            <span>{formatWaitingTime(item.waitingHours)}</span>
          </div>
        </div>
      </div>

      {/* Urgency badge (visible on hover or if urgent) */}
      {item.urgency !== 'normal' && (
        <div className={`flex-shrink-0 flex items-center gap-1 px-2 py-1 rounded text-xs font-medium ${
          item.urgency === 'urgent'
            ? 'bg-red-100 dark:bg-red-950/30 text-red-700 dark:text-red-300'
            : 'bg-orange-100 dark:bg-orange-950/30 text-orange-700 dark:text-orange-300'
        }`}>
          <AlertTriangle className="w-3 h-3" />
          <span className="uppercase tracking-wide">{item.urgency}</span>
        </div>
      )}
    </div>
  )
}
