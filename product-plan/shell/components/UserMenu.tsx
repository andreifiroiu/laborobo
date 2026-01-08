import {
  LogOut,
  Building2,
  ChevronDown,
  User as UserIcon,
  HelpCircle,
  MessageSquare,
  FileText,
  Plus,
  Check,
  ExternalLink,
  Settings,
  Copy,
} from 'lucide-react'
import { useState } from 'react'

interface User {
  name: string
  email?: string
  avatarUrl?: string
}

interface Organization {
  id: string
  name: string
  plan?: string
  role?: string
  avatarUrl?: string
  lastActive?: string
  memberCount?: number
}

interface UserMenuProps {
  user: User
  organizations?: Organization[]
  currentOrganization?: Organization
  onSwitchOrganization?: (organizationId: string) => void
  onOpenProfile?: () => void
  onCreateOrganization?: () => void
  onOpenHelp?: () => void
  onOpenFeedback?: () => void
  onOpenTerms?: () => void
  onOpenOrgInNewTab?: (organizationId: string) => void
  onOpenOrgSettings?: (organizationId: string) => void
  onCopyOrgLink?: (organizationId: string) => void
  onLogout?: () => void
}

function getInitials(name: string): string {
  return name
    .split(' ')
    .map((part) => part[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
}

function formatRelativeTime(dateString?: string): string {
  if (!dateString) return 'Just now'
  const date = new Date(dateString)
  const now = new Date()
  const diffMs = now.getTime() - date.getTime()
  const diffMins = Math.floor(diffMs / 60000)
  const diffHours = Math.floor(diffMs / 3600000)
  const diffDays = Math.floor(diffMs / 86400000)

  if (diffMins < 60) return `${diffMins}m ago`
  if (diffHours < 24) return `${diffHours}h ago`
  if (diffDays < 7) return `${diffDays}d ago`
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
}

export function UserMenu({
  user,
  organizations = [],
  currentOrganization,
  onSwitchOrganization,
  onOpenProfile,
  onCreateOrganization,
  onOpenHelp,
  onOpenFeedback,
  onOpenTerms,
  onOpenOrgInNewTab,
  onOpenOrgSettings,
  onCopyOrgLink,
  onLogout,
}: UserMenuProps) {
  const [isMenuOpen, setIsMenuOpen] = useState(false)
  const [hoveredOrgId, setHoveredOrgId] = useState<string | null>(null)

  const handleOrgSwitch = (orgId: string) => {
    onSwitchOrganization?.(orgId)
    setIsMenuOpen(false)
  }

  return (
    <div className="relative">
      {/* Trigger Button */}
      <button
        onClick={() => setIsMenuOpen(!isMenuOpen)}
        className="w-full p-4 flex items-center gap-3 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
      >
        {/* Avatar */}
        <div className="w-10 h-10 rounded-full bg-indigo-600 dark:bg-indigo-500 flex items-center justify-center text-white font-medium text-sm flex-shrink-0">
          {user.avatarUrl ? (
            <img
              src={user.avatarUrl}
              alt={user.name}
              className="w-full h-full rounded-full object-cover"
            />
          ) : (
            getInitials(user.name)
          )}
        </div>

        {/* User info */}
        <div className="flex-1 min-w-0 text-left">
          <p className="text-sm font-medium text-slate-900 dark:text-slate-100 truncate">
            {user.name}
          </p>
          {user.email && (
            <p className="text-xs text-slate-600 dark:text-slate-400 truncate">{user.email}</p>
          )}
        </div>

        <ChevronDown
          size={16}
          className={`text-slate-500 dark:text-slate-400 transition-transform flex-shrink-0 ${
            isMenuOpen ? 'rotate-180' : ''
          }`}
        />
      </button>

      {/* Dropdown Menu */}
      {isMenuOpen && (
        <>
          {/* Backdrop */}
          <div
            className="fixed inset-0 z-40"
            onClick={() => setIsMenuOpen(false)}
          />

          {/* Menu */}
          <div className="absolute bottom-full left-0 right-0 mb-2 mx-4 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-2xl overflow-hidden z-50 max-h-[80vh] overflow-y-auto">
            {/* User Header */}
            <div className="p-4 border-b border-slate-200 dark:border-slate-700">
              <div className="flex items-start gap-3">
                <div className="w-12 h-12 rounded-full bg-indigo-600 dark:bg-indigo-500 flex items-center justify-center text-white font-medium flex-shrink-0">
                  {user.avatarUrl ? (
                    <img
                      src={user.avatarUrl}
                      alt={user.name}
                      className="w-full h-full rounded-full object-cover"
                    />
                  ) : (
                    getInitials(user.name)
                  )}
                </div>
                <div className="flex-1 min-w-0">
                  <p className="font-semibold text-slate-900 dark:text-slate-50 truncate">
                    {user.name}
                  </p>
                  {user.email && (
                    <p className="text-sm text-slate-600 dark:text-slate-400 truncate">
                      {user.email}
                    </p>
                  )}
                </div>
              </div>
            </div>

            {/* Organizations */}
            {organizations.length > 0 && (
              <div className="py-2 border-b border-slate-200 dark:border-slate-700">
                <div className="px-4 py-2">
                  <p className="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                    Organizations
                  </p>
                </div>
                <div className="space-y-1 px-2">
                  {organizations.map((org) => {
                    const isCurrent = org.id === currentOrganization?.id
                    const isHovered = hoveredOrgId === org.id

                    return (
                      <div
                        key={org.id}
                        className="relative"
                        onMouseEnter={() => setHoveredOrgId(org.id)}
                        onMouseLeave={() => setHoveredOrgId(null)}
                      >
                        <button
                          onClick={() => handleOrgSwitch(org.id)}
                          className={`w-full px-3 py-2.5 rounded-lg text-left transition-colors ${
                            isCurrent
                              ? 'bg-indigo-50 dark:bg-indigo-950/50'
                              : 'hover:bg-slate-50 dark:hover:bg-slate-700/50'
                          }`}
                        >
                          <div className="flex items-start gap-3">
                            {/* Org Avatar */}
                            <div className="w-10 h-10 rounded-lg bg-slate-100 dark:bg-slate-700 flex items-center justify-center flex-shrink-0">
                              {org.avatarUrl ? (
                                <img
                                  src={org.avatarUrl}
                                  alt={org.name}
                                  className="w-full h-full rounded-lg object-cover"
                                />
                              ) : (
                                <Building2 className="w-5 h-5 text-slate-600 dark:text-slate-400" />
                              )}
                            </div>

                            {/* Org Info */}
                            <div className="flex-1 min-w-0">
                              <div className="flex items-center gap-2 mb-0.5">
                                <p
                                  className={`text-sm font-medium truncate ${
                                    isCurrent
                                      ? 'text-indigo-700 dark:text-indigo-300'
                                      : 'text-slate-900 dark:text-slate-50'
                                  }`}
                                >
                                  {org.name}
                                </p>
                                {isCurrent && (
                                  <Check className="w-4 h-4 text-indigo-600 dark:text-indigo-400 flex-shrink-0" />
                                )}
                              </div>
                              <div className="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400">
                                {org.plan && (
                                  <span className="px-1.5 py-0.5 bg-slate-200 dark:bg-slate-700 rounded">
                                    {org.plan}
                                  </span>
                                )}
                                {org.role && <span>•</span>}
                                {org.role && <span>{org.role}</span>}
                              </div>
                              {org.lastActive && (
                                <p className="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                  Active {formatRelativeTime(org.lastActive)}
                                </p>
                              )}
                            </div>
                          </div>
                        </button>

                        {/* Quick Actions (on hover) */}
                        {isHovered && !isCurrent && (
                          <div className="absolute right-2 top-1/2 -translate-y-1/2 flex gap-1">
                            <button
                              onClick={(e) => {
                                e.stopPropagation()
                                onOpenOrgInNewTab?.(org.id)
                              }}
                              className="p-1.5 rounded bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
                              title="Open in new tab"
                            >
                              <ExternalLink className="w-3.5 h-3.5 text-slate-600 dark:text-slate-400" />
                            </button>
                            <button
                              onClick={(e) => {
                                e.stopPropagation()
                                onOpenOrgSettings?.(org.id)
                              }}
                              className="p-1.5 rounded bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
                              title="Org settings"
                            >
                              <Settings className="w-3.5 h-3.5 text-slate-600 dark:text-slate-400" />
                            </button>
                            <button
                              onClick={(e) => {
                                e.stopPropagation()
                                onCopyOrgLink?.(org.id)
                              }}
                              className="p-1.5 rounded bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
                              title="Copy link"
                            >
                              <Copy className="w-3.5 h-3.5 text-slate-600 dark:text-slate-400" />
                            </button>
                          </div>
                        )}
                      </div>
                    )
                  })}
                </div>

                {/* Create Organization Button */}
                {onCreateOrganization && (
                  <div className="px-2 mt-2">
                    <button
                      onClick={() => {
                        onCreateOrganization()
                        setIsMenuOpen(false)
                      }}
                      className="w-full flex items-center gap-2 px-3 py-2.5 rounded-lg text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/50 transition-colors"
                    >
                      <Plus className="w-4 h-4" />
                      Create Organization
                    </button>
                  </div>
                )}
              </div>
            )}

            {/* Quick Links */}
            <div className="py-2 border-b border-slate-200 dark:border-slate-700">
              {onOpenProfile && (
                <button
                  onClick={() => {
                    onOpenProfile()
                    setIsMenuOpen(false)
                  }}
                  className="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors"
                >
                  <UserIcon className="w-4 h-4" />
                  My Profile
                </button>
              )}
              {onOpenHelp && (
                <button
                  onClick={() => {
                    onOpenHelp()
                    setIsMenuOpen(false)
                  }}
                  className="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors"
                >
                  <HelpCircle className="w-4 h-4" />
                  Help & Support
                </button>
              )}
              {onOpenFeedback && (
                <button
                  onClick={() => {
                    onOpenFeedback()
                    setIsMenuOpen(false)
                  }}
                  className="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors"
                >
                  <MessageSquare className="w-4 h-4" />
                  Feedback
                </button>
              )}
              {onOpenTerms && (
                <button
                  onClick={() => {
                    onOpenTerms()
                    setIsMenuOpen(false)
                  }}
                  className="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors"
                >
                  <FileText className="w-4 h-4" />
                  Terms & Privacy
                </button>
              )}
            </div>

            {/* Sign Out */}
            {onLogout && (
              <div className="py-2">
                <button
                  onClick={() => {
                    onLogout()
                    setIsMenuOpen(false)
                  }}
                  className="w-full flex items-center gap-3 px-4 py-2.5 text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/30 transition-colors"
                >
                  <LogOut className="w-4 h-4" />
                  Sign Out
                </button>
              </div>
            )}
          </div>
        </>
      )}
    </div>
  )
}
