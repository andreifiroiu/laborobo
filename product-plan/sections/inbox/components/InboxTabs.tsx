import type { InboxTab } from '@/../product/sections/inbox/types'

interface InboxTabsProps {
  currentTab: InboxTab
  counts: {
    all: number
    agent_drafts: number
    approvals: number
    flagged: number
    mentions: number
  }
  onTabChange?: (tab: InboxTab) => void
}

export function InboxTabs({ currentTab, counts, onTabChange }: InboxTabsProps) {
  const tabs: Array<{ id: InboxTab; label: string; count: number }> = [
    { id: 'all', label: 'All', count: counts.all },
    { id: 'agent_drafts', label: 'Agent Drafts', count: counts.agent_drafts },
    { id: 'approvals', label: 'Approvals', count: counts.approvals },
    { id: 'flagged', label: 'Flagged', count: counts.flagged },
    { id: 'mentions', label: 'Mentions', count: counts.mentions },
  ]

  return (
    <div className="border-b border-slate-200 dark:border-slate-800">
      <nav className="flex -mb-px overflow-x-auto" aria-label="Inbox tabs">
        {tabs.map((tab) => {
          const isActive = currentTab === tab.id
          return (
            <button
              key={tab.id}
              onClick={() => onTabChange?.(tab.id)}
              className={`flex items-center gap-2 px-6 py-4 border-b-2 font-medium text-sm whitespace-nowrap transition-colors ${
                isActive
                  ? 'border-indigo-600 dark:border-indigo-400 text-indigo-600 dark:text-indigo-400'
                  : 'border-transparent text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:border-slate-300 dark:hover:border-slate-700'
              }`}
            >
              {tab.label}
              {tab.count > 0 && (
                <span
                  className={`px-2 py-0.5 rounded-full text-xs font-bold ${
                    isActive
                      ? 'bg-indigo-100 dark:bg-indigo-950/30 text-indigo-700 dark:text-indigo-300'
                      : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300'
                  }`}
                >
                  {tab.count}
                </span>
              )}
            </button>
          )
        })}
      </nav>
    </div>
  )
}
