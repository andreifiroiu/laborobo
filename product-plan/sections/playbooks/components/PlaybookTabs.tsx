import type { PlaybookTab } from '@/../product/sections/playbooks/types'

interface PlaybookTabsProps {
  currentTab: PlaybookTab
  counts: {
    all: number
    sop: number
    checklist: number
    template: number
    acceptance_criteria: number
  }
  onTabChange?: (tab: PlaybookTab) => void
}

export function PlaybookTabs({ currentTab, counts, onTabChange }: PlaybookTabsProps) {
  const tabs: Array<{ id: PlaybookTab; label: string; count: number }> = [
    { id: 'all', label: 'All Playbooks', count: counts.all },
    { id: 'sop', label: 'SOPs', count: counts.sop },
    { id: 'checklist', label: 'Checklists', count: counts.checklist },
    { id: 'template', label: 'Templates', count: counts.template },
    { id: 'acceptance_criteria', label: 'Acceptance Criteria', count: counts.acceptance_criteria },
  ]

  return (
    <div className="border-b border-slate-200 dark:border-slate-800">
      <nav className="flex -mb-px overflow-x-auto scrollbar-hide" aria-label="Playbook tabs">
        {tabs.map((tab) => {
          const isActive = currentTab === tab.id
          return (
            <button
              key={tab.id}
              onClick={() => onTabChange?.(tab.id)}
              className={`flex items-center gap-2.5 px-6 py-4 border-b-2 font-semibold text-sm whitespace-nowrap transition-all ${
                isActive
                  ? 'border-indigo-600 dark:border-indigo-400 text-indigo-600 dark:text-indigo-400'
                  : 'border-transparent text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:border-slate-300 dark:hover:border-slate-700'
              }`}
            >
              <span>{tab.label}</span>
              <span
                className={`px-2.5 py-0.5 rounded-full text-xs font-bold min-w-[28px] text-center ${
                  isActive
                    ? 'bg-indigo-100 dark:bg-indigo-950/30 text-indigo-700 dark:text-indigo-300'
                    : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300'
                }`}
              >
                {tab.count}
              </span>
            </button>
          )
        })}
      </nav>
    </div>
  )
}
