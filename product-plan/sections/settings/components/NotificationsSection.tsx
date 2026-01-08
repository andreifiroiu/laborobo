import type { NotificationPreferences } from '@/../product/sections/settings/types'

interface NotificationsSectionProps {
  preferences: NotificationPreferences
  onUpdate?: (preferences: Partial<NotificationPreferences>) => void
}

export function NotificationsSection({ preferences, onUpdate }: NotificationsSectionProps) {
  const handleToggle = (
    channel: 'email' | 'push' | 'slack',
    category: keyof NotificationPreferences['email']
  ) => {
    const currentValue = preferences[channel][category]
    onUpdate?.({
      [channel]: {
        ...preferences[channel],
        [category]: !currentValue,
      },
    } as Partial<NotificationPreferences>)
  }

  const categories = [
    { key: 'projectUpdates', label: 'Project Updates', description: 'Status changes and milestones' },
    {
      key: 'taskAssignments',
      label: 'Task Assignments',
      description: 'When you are assigned to a task',
    },
    {
      key: 'approvalRequests',
      label: 'Approval Requests',
      description: 'When your approval is needed',
    },
    { key: 'blockers', label: 'Blockers', description: 'When blockers are reported' },
    { key: 'deadlines', label: 'Deadlines', description: 'Upcoming and overdue deadlines' },
    { key: 'weeklyDigest', label: 'Weekly Digest', description: 'Summary of your week' },
    { key: 'agentActivity', label: 'Agent Activity', description: 'AI agent runs and approvals' },
  ] as const

  return (
    <div className="max-w-6xl mx-auto p-8">
      <div className="mb-8">
        <h2 className="text-2xl font-semibold text-slate-900 dark:text-slate-50 mb-2">
          Notifications
        </h2>
        <p className="text-slate-600 dark:text-slate-400">
          Configure how and when you want to receive notifications
        </p>
      </div>

      <div className="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg overflow-hidden">
        <table className="min-w-full">
          <thead className="bg-slate-50 dark:bg-slate-800/50">
            <tr>
              <th className="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">
                Notification Type
              </th>
              <th className="px-6 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">
                Email
              </th>
              <th className="px-6 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">
                Push
              </th>
              <th className="px-6 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">
                Slack
              </th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-200 dark:divide-slate-800">
            {categories.map((category) => (
              <tr key={category.key}>
                <td className="px-6 py-4">
                  <div>
                    <p className="text-sm font-medium text-slate-900 dark:text-slate-50">
                      {category.label}
                    </p>
                    <p className="text-sm text-slate-500 dark:text-slate-400">
                      {category.description}
                    </p>
                  </div>
                </td>
                <td className="px-6 py-4 text-center">
                  <button
                    onClick={() => handleToggle('email', category.key)}
                    className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${
                      preferences.email[category.key]
                        ? 'bg-indigo-600'
                        : 'bg-slate-300 dark:bg-slate-600'
                    }`}
                  >
                    <span
                      className={`inline-block h-4 w-4 transform rounded-full bg-white transition-transform ${
                        preferences.email[category.key] ? 'translate-x-6' : 'translate-x-1'
                      }`}
                    />
                  </button>
                </td>
                <td className="px-6 py-4 text-center">
                  <button
                    onClick={() => handleToggle('push', category.key)}
                    className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${
                      preferences.push[category.key]
                        ? 'bg-indigo-600'
                        : 'bg-slate-300 dark:bg-slate-600'
                    }`}
                  >
                    <span
                      className={`inline-block h-4 w-4 transform rounded-full bg-white transition-transform ${
                        preferences.push[category.key] ? 'translate-x-6' : 'translate-x-1'
                      }`}
                    />
                  </button>
                </td>
                <td className="px-6 py-4 text-center">
                  <button
                    onClick={() => handleToggle('slack', category.key)}
                    className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${
                      preferences.slack[category.key]
                        ? 'bg-indigo-600'
                        : 'bg-slate-300 dark:bg-slate-600'
                    }`}
                  >
                    <span
                      className={`inline-block h-4 w-4 transform rounded-full bg-white transition-transform ${
                        preferences.slack[category.key] ? 'translate-x-6' : 'translate-x-1'
                      }`}
                    />
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  )
}
