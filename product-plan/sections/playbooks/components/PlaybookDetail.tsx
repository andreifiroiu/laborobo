import { ArrowLeft, FileText, CheckSquare, FileStack, CheckCircle2, Edit, Trash2, Copy, Share2, Clock, Calendar, User, Sparkles, TrendingUp, History, ExternalLink } from 'lucide-react'
import type { Playbook, WorkOrder, SOP, Checklist, Template, AcceptanceCriteria } from '@/../product/sections/playbooks/types'

// Design tokens: Primary (indigo), Secondary (emerald), Neutral (slate)
// Typography: Inter for headings and body, IBM Plex Mono for code

interface PlaybookDetailProps {
  playbook: Playbook
  relatedWorkOrders: WorkOrder[]
  onBack?: () => void
  onEdit?: () => void
  onDelete?: () => void
  onDuplicate?: () => void
  onApply?: () => void
  onViewWorkOrder?: (id: string) => void
  onViewVersionHistory?: () => void
}

export function PlaybookDetail({
  playbook,
  relatedWorkOrders,
  onBack,
  onEdit,
  onDelete,
  onDuplicate,
  onApply,
  onViewWorkOrder,
  onViewVersionHistory,
}: PlaybookDetailProps) {
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
      month: 'long',
      day: 'numeric',
      year: 'numeric',
    })
  }

  const formatDateTime = (dateStr: string) => {
    const date = new Date(dateStr)
    return date.toLocaleString('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric',
      hour: 'numeric',
      minute: '2-digit',
    })
  }

  // Render SOP-specific content
  const renderSOPContent = (sop: SOP) => (
    <div className="space-y-8">
      {/* Trigger Conditions */}
      <div>
        <h3 className="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wide mb-3">
          When to use this SOP
        </h3>
        <p className="text-slate-700 dark:text-slate-300 bg-indigo-50 dark:bg-indigo-950/20 border border-indigo-200 dark:border-indigo-900 rounded-lg p-4">
          {sop.triggerConditions}
        </p>
      </div>

      {/* Steps */}
      <div>
        <h3 className="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wide mb-4">
          Procedure Steps
        </h3>
        <div className="space-y-4">
          {sop.steps.map((step, index) => (
            <div key={step.id} className="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5">
              <div className="flex gap-4">
                <div className="flex-shrink-0 w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 font-bold flex items-center justify-center text-sm">
                  {index + 1}
                </div>
                <div className="flex-1 min-w-0">
                  <h4 className="font-bold text-slate-900 dark:text-white mb-2">{step.title}</h4>
                  <p className="text-sm text-slate-600 dark:text-slate-400 mb-3">{step.description}</p>
                  <div className="flex flex-wrap gap-3 text-xs">
                    <div className="flex items-center gap-1.5 text-slate-600 dark:text-slate-400">
                      <User className="w-3.5 h-3.5" />
                      <span className="font-medium">{step.assignedRole}</span>
                    </div>
                    <div className="flex items-center gap-1.5 text-slate-600 dark:text-slate-400">
                      <FileText className="w-3.5 h-3.5" />
                      <span>Evidence: {step.evidenceDescription}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* Metadata */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div className="bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-4">
          <div className="text-xs text-slate-600 dark:text-slate-400 mb-1">Estimated Time</div>
          <div className="text-lg font-bold text-slate-900 dark:text-white">
            {Math.floor(sop.estimatedTimeMinutes / 60)}h {sop.estimatedTimeMinutes % 60}m
          </div>
        </div>
        <div className="bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-4">
          <div className="text-xs text-slate-600 dark:text-slate-400 mb-1">Roles Involved</div>
          <div className="text-sm font-medium text-slate-900 dark:text-white">
            {sop.rolesInvolved.join(', ')}
          </div>
        </div>
        <div className="bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-4">
          <div className="text-xs text-slate-600 dark:text-slate-400 mb-1">Steps</div>
          <div className="text-lg font-bold text-slate-900 dark:text-white">{sop.steps.length}</div>
        </div>
      </div>

      {/* Definition of Done */}
      <div>
        <h3 className="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wide mb-3">
          Definition of Done
        </h3>
        <p className="text-slate-700 dark:text-slate-300 bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-900 rounded-lg p-4">
          {sop.definitionOfDone}
        </p>
      </div>
    </div>
  )

  // Render Checklist-specific content
  const renderChecklistContent = (checklist: Checklist) => (
    <div className="space-y-6">
      <div>
        <h3 className="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wide mb-4">
          Checklist Items
        </h3>
        <div className="space-y-2">
          {checklist.items.map((item) => (
            <div key={item.id} className="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-4 flex items-start gap-3">
              <div className="flex-shrink-0 w-5 h-5 rounded border-2 border-slate-300 dark:border-slate-700 mt-0.5" />
              <div className="flex-1 min-w-0">
                <div className="font-medium text-slate-900 dark:text-white mb-1">{item.label}</div>
                {item.assignedRole && (
                  <div className="flex items-center gap-4 text-xs text-slate-600 dark:text-slate-400">
                    <div className="flex items-center gap-1.5">
                      <User className="w-3.5 h-3.5" />
                      <span>{item.assignedRole}</span>
                    </div>
                    {item.evidenceRequired && (
                      <div className="flex items-center gap-1.5">
                        <FileText className="w-3.5 h-3.5" />
                        <span>Evidence: {item.evidenceDescription}</span>
                      </div>
                    )}
                  </div>
                )}
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  )

  // Render Template-specific content
  const renderTemplateContent = (template: Template) => {
    if (template.templateType === 'project' && 'milestones' in template.structure) {
      return (
        <div className="space-y-6">
          <div className="bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-4">
            <div className="text-xs text-slate-600 dark:text-slate-400 mb-1">Template Type</div>
            <div className="text-lg font-bold text-slate-900 dark:text-white">Project Template</div>
            <div className="text-sm text-slate-600 dark:text-slate-400 mt-1">
              {template.structure.estimatedTotalDays} days • {template.structure.milestones.length} milestones
            </div>
          </div>

          <div>
            <h3 className="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wide mb-4">
              Project Milestones
            </h3>
            <div className="space-y-4">
              {template.structure.milestones.map((milestone, index) => (
                <div key={index} className="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5">
                  <div className="flex items-start gap-4 mb-3">
                    <div className="flex-shrink-0 w-8 h-8 rounded-full bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300 font-bold flex items-center justify-center text-sm">
                      {index + 1}
                    </div>
                    <div className="flex-1">
                      <h4 className="font-bold text-slate-900 dark:text-white">{milestone.name}</h4>
                      <div className="text-xs text-slate-600 dark:text-slate-400 mt-1">
                        {milestone.durationDays} days
                      </div>
                    </div>
                  </div>
                  <div className="ml-12 space-y-1">
                    {milestone.workOrders.map((wo, idx) => (
                      <div key={idx} className="text-sm text-slate-600 dark:text-slate-400 flex items-center gap-2">
                        <div className="w-1.5 h-1.5 rounded-full bg-slate-400 dark:bg-slate-600" />
                        {wo}
                      </div>
                    ))}
                  </div>
                </div>
              ))}
            </div>
          </div>

          <div>
            <h3 className="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wide mb-3">
              Default Team Roles
            </h3>
            <div className="flex flex-wrap gap-2">
              {template.structure.defaultTeamRoles.map((role, idx) => (
                <span key={idx} className="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-sm font-medium rounded-lg">
                  {role}
                </span>
              ))}
            </div>
          </div>
        </div>
      )
    }

    if (template.templateType === 'work-order' && 'prefilledScope' in template.structure) {
      return (
        <div className="space-y-6">
          <div className="bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-4">
            <div className="text-xs text-slate-600 dark:text-slate-400 mb-1">Template Type</div>
            <div className="text-lg font-bold text-slate-900 dark:text-white">Work Order Template</div>
            <div className="text-sm text-slate-600 dark:text-slate-400 mt-1">
              ~{template.structure.estimatedHours} hours
            </div>
          </div>

          <div>
            <h3 className="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wide mb-3">
              Scope
            </h3>
            <p className="text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-4">
              {template.structure.prefilledScope}
            </p>
          </div>

          <div>
            <h3 className="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wide mb-3">
              Default Tasks
            </h3>
            <div className="space-y-2">
              {template.structure.defaultTasks.map((task, idx) => (
                <div key={idx} className="flex items-center gap-3 text-sm text-slate-700 dark:text-slate-300">
                  <div className="w-1.5 h-1.5 rounded-full bg-slate-400 dark:bg-slate-600" />
                  {task}
                </div>
              ))}
            </div>
          </div>
        </div>
      )
    }

    if (template.templateType === 'document' && 'sections' in template.structure) {
      return (
        <div className="space-y-6">
          <div className="bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-4">
            <div className="text-xs text-slate-600 dark:text-slate-400 mb-1">Template Type</div>
            <div className="text-lg font-bold text-slate-900 dark:text-white">Document Template</div>
            <div className="text-sm text-slate-600 dark:text-slate-400 mt-1">
              Output: {template.structure.outputFormat}
            </div>
          </div>

          <div>
            <h3 className="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wide mb-4">
              Document Sections
            </h3>
            <div className="space-y-3">
              {template.structure.sections.map((section, idx) => (
                <div key={idx} className="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-4">
                  <h4 className="font-bold text-slate-900 dark:text-white mb-1">{section.heading}</h4>
                  <p className="text-sm text-slate-600 dark:text-slate-400">{section.description}</p>
                </div>
              ))}
            </div>
          </div>
        </div>
      )
    }

    return null
  }

  // Render Acceptance Criteria-specific content
  const renderAcceptanceCriteriaContent = (criteria: AcceptanceCriteria) => (
    <div className="space-y-6">
      <div>
        <h3 className="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wide mb-4">
          Validation Rules
        </h3>
        <div className="space-y-3">
          {criteria.criteria.map((rule) => (
            <div key={rule.id} className="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-4">
              <div className="flex items-start gap-3 mb-2">
                <CheckCircle2 className="w-4 h-4 text-emerald-600 dark:text-emerald-400 mt-0.5 flex-shrink-0" />
                <p className="flex-1 font-medium text-slate-900 dark:text-white">{rule.rule}</p>
              </div>
              <div className="ml-7 flex flex-wrap gap-3 text-xs">
                <span className={`px-2 py-1 rounded ${
                  rule.validationType === 'automated'
                    ? 'bg-indigo-50 dark:bg-indigo-950/20 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-900'
                    : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300'
                }`}>
                  {rule.validationType === 'automated' ? 'Automated' : 'Manual'}
                </span>
                {rule.validationTool && (
                  <span className="text-slate-600 dark:text-slate-400">
                    Tool: {rule.validationTool}
                  </span>
                )}
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  )

  return (
    <div className="min-h-screen bg-slate-50 dark:bg-slate-950">
      {/* Header */}
      <div className="bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 sticky top-0 z-10">
        <div className="max-w-5xl mx-auto px-6 py-4">
          <div className="flex items-center justify-between">
            <button
              onClick={onBack}
              className="flex items-center gap-2 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors"
            >
              <ArrowLeft className="w-4 h-4" strokeWidth={2} />
              <span className="text-sm font-medium">Back to Playbooks</span>
            </button>

            <div className="flex items-center gap-2">
              <button
                onClick={onEdit}
                className="px-4 py-2 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors flex items-center gap-2 text-sm font-medium"
              >
                <Edit className="w-4 h-4" />
                Edit
              </button>
              <button
                onClick={onDuplicate}
                className="px-4 py-2 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors flex items-center gap-2 text-sm font-medium"
              >
                <Copy className="w-4 h-4" />
                Duplicate
              </button>
              <button
                onClick={onApply}
                className="px-4 py-2 bg-indigo-600 dark:bg-indigo-500 text-white rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 transition-colors flex items-center gap-2 text-sm font-semibold shadow-lg shadow-indigo-600/20 dark:shadow-indigo-500/20"
              >
                <Share2 className="w-4 h-4" />
                Apply to Work Order
              </button>
              <button
                onClick={onDelete}
                className="px-4 py-2 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/20 rounded-lg transition-colors flex items-center gap-2 text-sm font-medium"
              >
                <Trash2 className="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>
      </div>

      {/* Content */}
      <div className="max-w-5xl mx-auto px-6 py-8">
        {/* Title and metadata */}
        <div className="mb-8">
          <div className="flex items-start gap-4 mb-4">
            <div className={`flex-shrink-0 p-4 rounded-xl border ${config.color}`}>
              <TypeIcon className="w-6 h-6" strokeWidth={2} />
            </div>
            <div className="flex-1 min-w-0">
              <div className="flex items-center gap-2 mb-3 flex-wrap">
                <span className={`px-3 py-1.5 text-xs font-bold uppercase tracking-wide rounded-md ${config.badge}`}>
                  {config.label}
                </span>
                {playbook.aiGenerated && (
                  <span className="flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 dark:bg-indigo-950/20 text-indigo-600 dark:text-indigo-400 text-xs font-medium rounded-md border border-indigo-200 dark:border-indigo-900">
                    <Sparkles className="w-3.5 h-3.5" />
                    AI Generated
                  </span>
                )}
              </div>
              <h1 className="text-3xl font-bold text-slate-900 dark:text-white mb-3">{playbook.name}</h1>
              <p className="text-lg text-slate-600 dark:text-slate-400 leading-relaxed">{playbook.description}</p>
            </div>
          </div>

          {/* Tags */}
          {playbook.tags.length > 0 && (
            <div className="flex flex-wrap gap-2 mb-6">
              {playbook.tags.map((tag) => (
                <span key={tag} className="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-sm rounded-lg font-medium">
                  #{tag}
                </span>
              ))}
            </div>
          )}

          {/* Metadata grid */}
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div className="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-4">
              <div className="flex items-center gap-2 text-slate-600 dark:text-slate-400 mb-1">
                <TrendingUp className="w-4 h-4" />
                <span className="text-xs font-medium">Times Applied</span>
              </div>
              <div className="text-2xl font-bold text-slate-900 dark:text-white">{playbook.timesApplied}</div>
            </div>
            <div className="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-4">
              <div className="flex items-center gap-2 text-slate-600 dark:text-slate-400 mb-1">
                <User className="w-4 h-4" />
                <span className="text-xs font-medium">Created By</span>
              </div>
              <div className="text-sm font-bold text-slate-900 dark:text-white truncate">{playbook.createdByName}</div>
            </div>
            <div className="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-4">
              <div className="flex items-center gap-2 text-slate-600 dark:text-slate-400 mb-1">
                <Calendar className="w-4 h-4" />
                <span className="text-xs font-medium">Last Modified</span>
              </div>
              <div className="text-xs font-bold text-slate-900 dark:text-white">{formatDate(playbook.lastModified)}</div>
            </div>
            <div className="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-4">
              <div className="flex items-center gap-2 text-slate-600 dark:text-slate-400 mb-1">
                <Clock className="w-4 h-4" />
                <span className="text-xs font-medium">Last Used</span>
              </div>
              <div className="text-xs font-bold text-slate-900 dark:text-white">{formatDate(playbook.lastUsed)}</div>
            </div>
          </div>
        </div>

        {/* Type-specific content */}
        <div className="mb-8">
          {playbook.type === 'sop' && renderSOPContent(playbook as SOP)}
          {playbook.type === 'checklist' && renderChecklistContent(playbook as Checklist)}
          {playbook.type === 'template' && renderTemplateContent(playbook as Template)}
          {playbook.type === 'acceptance_criteria' && renderAcceptanceCriteriaContent(playbook as AcceptanceCriteria)}
        </div>

        {/* Related Work Orders */}
        {relatedWorkOrders.length > 0 && (
          <div className="mb-8">
            <h3 className="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wide mb-4">
              Used By ({relatedWorkOrders.length} Work Orders)
            </h3>
            <div className="space-y-2">
              {relatedWorkOrders.map((wo) => (
                <button
                  key={wo.id}
                  onClick={() => onViewWorkOrder?.(wo.id)}
                  className="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-4 hover:border-indigo-300 dark:hover:border-indigo-700 hover:bg-indigo-50 dark:hover:bg-indigo-950/20 transition-all text-left group"
                >
                  <div className="flex items-center justify-between">
                    <div>
                      <div className="font-medium text-slate-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                        {wo.title}
                      </div>
                      <div className="text-sm text-slate-600 dark:text-slate-400">{wo.projectName}</div>
                    </div>
                    <ExternalLink className="w-4 h-4 text-slate-400 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors" />
                  </div>
                </button>
              ))}
            </div>
          </div>
        )}

        {/* Version History */}
        {playbook.versionHistory && playbook.versionHistory.length > 0 && (
          <div>
            <div className="flex items-center justify-between mb-4">
              <h3 className="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wide">
                Version History
              </h3>
              <button
                onClick={onViewVersionHistory}
                className="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 font-medium flex items-center gap-1"
              >
                <History className="w-4 h-4" />
                View Full History
              </button>
            </div>
            <div className="space-y-3">
              {playbook.versionHistory.slice(0, 3).map((version) => (
                <div key={version.version} className="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-4">
                  <div className="flex items-start justify-between mb-2">
                    <div className="font-medium text-slate-900 dark:text-white">Version {version.version}</div>
                    <div className="text-xs text-slate-600 dark:text-slate-400">{formatDateTime(version.modifiedAt)}</div>
                  </div>
                  <p className="text-sm text-slate-600 dark:text-slate-400 mb-1">{version.changeDescription}</p>
                  <div className="text-xs text-slate-500 dark:text-slate-500">By {version.modifiedBy}</div>
                </div>
              ))}
            </div>
          </div>
        )}
      </div>
    </div>
  )
}
