import { X, Check, XCircle, Edit3, Clock, Sparkles, User, ExternalLink } from 'lucide-react'
import type { InboxItem } from '@/../product/sections/inbox/types'

interface InboxSidePanelProps {
  item: InboxItem | null
  onClose?: () => void
  onApprove?: () => void
  onReject?: () => void
  onEdit?: () => void
  onDefer?: () => void
}

export function InboxSidePanel({
  item,
  onClose,
  onApprove,
  onReject,
  onEdit,
  onDefer,
}: InboxSidePanelProps) {
  if (!item) return null

  // Type labels
  const typeLabels = {
    agent_draft: 'Agent Draft',
    approval: 'Approval Request',
    flag: 'Flagged Item',
    mention: 'Mention',
  }

  // Urgency colors
  const urgencyColors = {
    urgent: 'text-red-600 dark:text-red-400',
    high: 'text-orange-600 dark:text-orange-400',
    normal: 'text-slate-600 dark:text-slate-400',
  }

  // Format date
  const formatDate = (dateStr: string) => {
    const date = new Date(dateStr)
    return date.toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric',
      hour: 'numeric',
      minute: '2-digit',
    })
  }

  return (
    <div className="fixed inset-y-0 right-0 w-full sm:w-[600px] bg-white dark:bg-slate-900 border-l border-slate-200 dark:border-slate-800 shadow-2xl flex flex-col z-50">
      {/* Header */}
      <div className="flex items-start justify-between p-6 border-b border-slate-200 dark:border-slate-800">
        <div className="flex-1 pr-4">
          <div className="flex items-center gap-2 mb-2">
            <span className="px-2 py-1 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-medium rounded">
              {typeLabels[item.type]}
            </span>
            {item.urgency !== 'normal' && (
              <span className={`px-2 py-1 text-xs font-bold uppercase tracking-wide ${urgencyColors[item.urgency]}`}>
                {item.urgency}
              </span>
            )}
            {item.qaValidation === 'passed' && (
              <span className="px-2 py-1 bg-emerald-100 dark:bg-emerald-950/30 text-emerald-700 dark:text-emerald-300 text-xs font-medium rounded flex items-center gap-1">
                <Check className="w-3 h-3" />
                QA Passed
              </span>
            )}
          </div>
          <h2 className="text-xl font-bold text-slate-900 dark:text-white">
            {item.title}
          </h2>
        </div>
        <button
          onClick={onClose}
          className="p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors"
        >
          <X className="w-5 h-5 text-slate-500" />
        </button>
      </div>

      {/* Metadata */}
      <div className="px-6 py-4 bg-slate-50 dark:bg-slate-950 border-b border-slate-200 dark:border-slate-800">
        <div className="grid grid-cols-2 gap-4 text-sm">
          <div>
            <div className="text-xs text-slate-500 dark:text-slate-500 mb-1">Source</div>
            <div className="flex items-center gap-2 font-medium text-slate-900 dark:text-white">
              {item.sourceType === 'ai_agent' ? (
                <Sparkles className="w-4 h-4 text-indigo-600 dark:text-indigo-400" />
              ) : (
                <User className="w-4 h-4 text-slate-600 dark:text-slate-400" />
              )}
              {item.sourceName}
            </div>
          </div>

          <div>
            <div className="text-xs text-slate-500 dark:text-slate-500 mb-1">Created</div>
            <div className="font-medium text-slate-900 dark:text-white">
              {formatDate(item.createdAt)}
            </div>
          </div>

          {item.relatedProjectName && (
            <div>
              <div className="text-xs text-slate-500 dark:text-slate-500 mb-1">Project</div>
              <div className="font-medium text-slate-900 dark:text-white">
                {item.relatedProjectName}
              </div>
            </div>
          )}

          {item.relatedWorkOrderTitle && (
            <div>
              <div className="text-xs text-slate-500 dark:text-slate-500 mb-1">Work Order</div>
              <button className="font-medium text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1">
                {item.relatedWorkOrderTitle}
                <ExternalLink className="w-3 h-3" />
              </button>
            </div>
          )}

          {item.aiConfidence && (
            <div>
              <div className="text-xs text-slate-500 dark:text-slate-500 mb-1">AI Confidence</div>
              <div className={`font-bold uppercase tracking-wide text-sm ${
                item.aiConfidence === 'high'
                  ? 'text-emerald-600 dark:text-emerald-400'
                  : item.aiConfidence === 'medium'
                  ? 'text-amber-600 dark:text-amber-400'
                  : 'text-red-600 dark:text-red-400'
              }`}>
                {item.aiConfidence}
              </div>
            </div>
          )}

          <div>
            <div className="text-xs text-slate-500 dark:text-slate-500 mb-1">Waiting Time</div>
            <div className="font-medium text-slate-900 dark:text-white">
              {item.waitingHours < 24
                ? `${item.waitingHours}h`
                : `${Math.floor(item.waitingHours / 24)}d ${item.waitingHours % 24}h`}
            </div>
          </div>
        </div>
      </div>

      {/* Content - Scrollable */}
      <div className="flex-1 overflow-y-auto p-6">
        <div className="prose prose-sm dark:prose-invert max-w-none">
          <div className="whitespace-pre-wrap text-slate-900 dark:text-white font-mono text-sm leading-relaxed">
            {item.fullContent}
          </div>
        </div>
      </div>

      {/* Actions */}
      <div className="p-6 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950">
        <div className="flex flex-wrap gap-3">
          {(item.type === 'agent_draft' || item.type === 'approval') && (
            <button
              onClick={onApprove}
              className="flex-1 sm:flex-none px-6 py-2.5 bg-emerald-600 dark:bg-emerald-500 text-white font-medium rounded-lg hover:bg-emerald-700 dark:hover:bg-emerald-600 transition-colors flex items-center justify-center gap-2"
            >
              <Check className="w-4 h-4" />
              Approve
            </button>
          )}

          {(item.type === 'agent_draft' || item.type === 'approval') && (
            <button
              onClick={onReject}
              className="flex-1 sm:flex-none px-6 py-2.5 bg-red-100 dark:bg-red-950/30 text-red-700 dark:text-red-300 font-medium rounded-lg hover:bg-red-200 dark:hover:bg-red-950/50 transition-colors flex items-center justify-center gap-2"
            >
              <XCircle className="w-4 h-4" />
              Reject
            </button>
          )}

          <button
            onClick={onEdit}
            className="flex-1 sm:flex-none px-6 py-2.5 bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white font-medium rounded-lg hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors flex items-center justify-center gap-2"
          >
            <Edit3 className="w-4 h-4" />
            Edit
          </button>

          <button
            onClick={onDefer}
            className="flex-1 sm:flex-none px-6 py-2.5 bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white font-medium rounded-lg hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors flex items-center justify-center gap-2"
          >
            <Clock className="w-4 h-4" />
            Defer
          </button>
        </div>

        {(item.type === 'agent_draft' || item.type === 'approval') && (
          <p className="text-xs text-slate-500 dark:text-slate-500 mt-3 text-center">
            Tip: Review carefully before approving. Rejected items will return to the agent with your feedback.
          </p>
        )}
      </div>
    </div>
  )
}
