import { Zap, Briefcase, Inbox, BookOpen, Users, BarChart3, Settings } from 'lucide-react'
import type { NavigationItem } from './AppShell'

interface MainNavProps {
  items: NavigationItem[]
  onNavigate?: (href: string) => void
}

const iconMap: Record<string, React.ComponentType<{ size?: number; className?: string }>> = {
  'Today': Zap,
  'Work': Briefcase,
  'Inbox': Inbox,
  'Playbooks': BookOpen,
  'Directory': Users,
  'Reports': BarChart3,
  'Settings': Settings,
}

export function MainNav({ items, onNavigate }: MainNavProps) {
  const handleClick = (href: string) => {
    if (onNavigate) {
      onNavigate(href)
    }
  }

  return (
    <nav className="px-3">
      <ul className="space-y-1">
        {items.map((item) => {
          const Icon = iconMap[item.label] || Zap
          const isActive = item.isActive

          return (
            <li key={item.href}>
              <button
                onClick={() => handleClick(item.href)}
                className={`
                  w-full flex items-center gap-3 px-3 py-2.5 rounded-lg
                  text-sm font-medium transition-colors
                  ${
                    isActive
                      ? 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300'
                      : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'
                  }
                `}
              >
                <Icon
                  size={20}
                  className={isActive ? 'text-indigo-600 dark:text-indigo-400' : ''}
                />
                <span>{item.label}</span>
              </button>
            </li>
          )
        })}
      </ul>
    </nav>
  )
}
