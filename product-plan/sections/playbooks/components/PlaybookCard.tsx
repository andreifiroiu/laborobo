import { FileText, CheckSquare, FileStack, CheckCircle2, TrendingUp, Calendar, Sparkles, User, Clock } from 'lucide-react'
import type { Playbook } from '@/../product/sections/playbooks/types'

// Design tokens: Primary (indigo), Secondary (emerald), Neutral (slate)
// Typography: Inter for headings and body, IBM Plex Mono for code

interface PlaybookCardProps {
  playbook: Playbook
  onView?: () => void
}

export function PlaybookCard({ playbook, onView }: PlaybookCardProps) {
  // Type-specific configuration
  const typeConfig = {
    sop: {
      icon: FileText,
      label: 'SOP',
      color: 'bg-indigo-50 dark:bg-indigo-950/20 text-indigo-700 dark:text-indigo-300 border-indigo-200 dark:border-indigo-900',
      badge: 'bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300',
    },
    checklist: {
      icon: CheckSquare,
      label: 'Checklist',
      color: 'bg-emerald-50 dark:bg-emerald-950/20 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-900',
      badge: 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300',
    },
    template: {
      icon: FileStack,
      label: 'Template',
      color: 'bg-purple-50 dark:bg-purple-950/20 text-purple-700 dark:text-purple-300 border-purple-200 dark:border-purple-900',
      badge: 'bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300',
    },
    acceptance_criteria: {
      icon: CheckCircle2,
      label: 'Acceptance Criteria',
      color: 'bg-amber-50 dark:bg-amber-950/20 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-900',
      badge: 'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300',
    },
  }

  const config = typeConfig[playbook.type]
  const TypeIcon = config.icon

  // Format date
  const formatDate = (dateStr: string) => {
    const date = new Date(dateStr)
    return date.toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric',
    })
  }

  // Type-specific metadata
  const renderTypeMetadata = () => {
    if (playbook.type === 'sop' && 'estimatedTimeMinutes' in playbook) {
      const hours = Math.floor(playbook.estimatedTimeMinutes / 60)
      const minutes = playbook.estimatedTimeMinutes % 60
      return (
        <div className="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400">
          <Clock className="w-3.5 h-3.5" />
          <span>{hours > 0 ? `${hours}h ` : ''}{minutes}m</span>
        </div>
      )
    }

    if (playbook.type === 'checklist' && 'items' in playbook) {
      return (
        <div className="text-xs text-slate-500 dark:text-slate-400">
          {playbook.items.length} items
        </div>
      )
    }

    if (playbook.type === 'template' && 'templateType' in playbook) {
      const typeLabels = {
        project: 'Project',
        'work-order': 'Work Order',
        document: 'Document',
      }
      return (
        <div className="text-xs text-slate-500 dark:text-slate-400">
          {typeLabels[playbook.templateType]}
        </div>
      )
    }

    if (playbook.type === 'acceptance_criteria' && 'criteria' in playbook) {
      return (
        <div className="text-xs text-slate-500 dark:text-slate-400">
          {playbook.criteria.length} criteria
        </div>
      )
    }

    return null
  }

  return (
    <div
      onClick={onView}
      className="group bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-6 hover:border-slate-300 dark:hover:border-slate-700 hover:shadow-xl hover:shadow-slate-200/50 dark:hover:shadow-slate-950/50 transition-all cursor-pointer"
    >
      {/* Header with type icon and badges */}
      <div className="flex items-start gap-4 mb-4">
        {/* Type Icon */}
        <div className={`flex-shrink-0 p-3 rounded-lg border ${config.color} transition-transform group-hover:scale-110`}>
          <TypeIcon className="w-5 h-5" strokeWidth={2} />
        </div>

        {/* Title and badges */}
        <div className="flex-1 min-w-0">
          <div className="flex items-center gap-2 mb-2 flex-wrap">
            <span className={`px-2.5 py-1 text-xs font-bold uppercase tracking-wide rounded-md ${config.badge}`}>
              {config.label}
            </span>
            {playbook.aiGenerated && (
              <span className="flex items-center gap-1 px-2.5 py-1 bg-indigo-50 dark:bg-indigo-950/20 text-indigo-600 dark:text-indigo-400 text-xs font-medium rounded-md border border-indigo-200 dark:border-indigo-900">
                <Sparkles className="w-3 h-3" />
                AI
              </span>
            )}
            {renderTypeMetadata()}
          </div>
          <h3 className="font-bold text-lg text-slate-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors line-clamp-2 leading-snug">
            {playbook.name}
          </h3>
        </div>
      </div>

      {/* Description */}
      <p className="text-sm text-slate-600 dark:text-slate-400 line-clamp-2 mb-4 leading-relaxed">
        {playbook.description}
      </p>

      {/* Tags */}
      {playbook.tags.length > 0 && (
        <div className="flex flex-wrap gap-2 mb-4">
          {playbook.tags.slice(0, 3).map((tag) => (
            <span
              key={tag}
              className="px-2.5 py-1 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs rounded-md font-medium"
            >
              #{tag}
            </span>
          ))}
          {playbook.tags.length > 3 && (
            <span className="px-2.5 py-1 text-xs text-slate-500 dark:text-slate-400">
              +{playbook.tags.length - 3} more
            </span>
          )}
        </div>
      )}

      {/* Metadata footer */}
      <div className="flex items-center justify-between pt-4 border-t border-slate-100 dark:border-slate-800 text-xs">
        <div className="flex items-center gap-4">
          {/* Usage count */}
          <div className="flex items-center gap-1.5 text-slate-500 dark:text-slate-400">
            <TrendingUp className="w-3.5 h-3.5" />
            <span className="font-bold text-slate-900 dark:text-white">{playbook.timesApplied}</span>
            <span>uses</span>
          </div>

          {/* Created by */}
          <div className="flex items-center gap-1.5 text-slate-500 dark:text-slate-400">
            {playbook.aiGenerated ? (
              <Sparkles className="w-3.5 h-3.5" />
            ) : (
              <User className="w-3.5 h-3.5" />
            )}
            <span className="truncate max-w-[100px]">{playbook.createdByName}</span>
          </div>
        </div>

        {/* Last modified */}
        <div className="flex items-center gap-1.5 text-slate-500 dark:text-slate-400">
          <Calendar className="w-3.5 h-3.5" />
          <span>{formatDate(playbook.lastModified)}</span>
        </div>
      </div>
    </div>
  )
}
