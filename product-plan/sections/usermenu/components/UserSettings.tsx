import { useState } from 'react'
import type { UserSettingsProps } from '@/../product/sections/usermenu/types'
import { ProfileTab } from './ProfileTab'
import { NotificationsTab } from './NotificationsTab'
import { AppearanceTab } from './AppearanceTab'
import { SecurityTab } from './SecurityTab'
import { DangerZoneTab } from './DangerZoneTab'

type TabId = 'profile' | 'notifications' | 'appearance' | 'security' | 'dangerzone'

export function UserSettings({
  user,
  notificationPreferences,
  appearanceSettings,
  sessions,
  connectedApps,
  apiKeys,
  onUpdateProfile,
  onUpdateNotifications,
  onUpdateAppearance,
  onChangePassword,
  onToggle2FA,
  onSignOutSession,
  onDisconnectApp,
  onRevokeApiKey,
  onCreateApiKey,
  onExportData,
  onDeleteAccount,
}: UserSettingsProps) {
  const [activeTab, setActiveTab] = useState<TabId>('profile')

  const tabs: { id: TabId; label: string }[] = [
    { id: 'profile', label: 'Profile' },
    { id: 'notifications', label: 'Notifications' },
    { id: 'appearance', label: 'Appearance' },
    { id: 'security', label: 'Security' },
    { id: 'dangerzone', label: 'Danger Zone' },
  ]

  return (
    <div className="min-h-screen bg-slate-50 dark:bg-slate-950">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-12">
        {/* Header */}
        <div className="mb-8">
          <h1 className="text-3xl lg:text-4xl font-bold text-slate-900 dark:text-slate-50 mb-2">
            Settings
          </h1>
          <p className="text-slate-600 dark:text-slate-400">
            Manage your account preferences and security settings
          </p>
        </div>

        <div className="lg:grid lg:grid-cols-12 lg:gap-8">
          {/* Sidebar Navigation */}
          <aside className="lg:col-span-3">
            <nav className="space-y-1 mb-8 lg:mb-0">
              {tabs.map((tab) => (
                <button
                  key={tab.id}
                  onClick={() => setActiveTab(tab.id)}
                  className={`
                    w-full text-left px-4 py-3 rounded-lg font-medium transition-all
                    ${
                      activeTab === tab.id
                        ? 'bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 shadow-sm'
                        : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-900'
                    }
                  `}
                >
                  {tab.label}
                </button>
              ))}
            </nav>
          </aside>

          {/* Content Area */}
          <div className="lg:col-span-9">
            <div className="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
              {activeTab === 'profile' && (
                <ProfileTab user={user} onUpdateProfile={onUpdateProfile} />
              )}
              {activeTab === 'notifications' && (
                <NotificationsTab
                  notificationPreferences={notificationPreferences}
                  onUpdateNotifications={onUpdateNotifications}
                />
              )}
              {activeTab === 'appearance' && (
                <AppearanceTab
                  appearanceSettings={appearanceSettings}
                  onUpdateAppearance={onUpdateAppearance}
                />
              )}
              {activeTab === 'security' && (
                <SecurityTab
                  sessions={sessions}
                  connectedApps={connectedApps}
                  apiKeys={apiKeys}
                  onChangePassword={onChangePassword}
                  onToggle2FA={onToggle2FA}
                  onSignOutSession={onSignOutSession}
                  onDisconnectApp={onDisconnectApp}
                  onRevokeApiKey={onRevokeApiKey}
                  onCreateApiKey={onCreateApiKey}
                />
              )}
              {activeTab === 'dangerzone' && (
                <DangerZoneTab onExportData={onExportData} onDeleteAccount={onDeleteAccount} />
              )}
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}
