import { useState } from 'react'
import { User as UserIcon, Mail, Phone, Globe, Clock } from 'lucide-react'
import type { User } from '@/../product/sections/usermenu/types'

interface ProfileTabProps {
  user: User
  onUpdateProfile?: (updates: Partial<User>) => void
}

export function ProfileTab({ user, onUpdateProfile }: ProfileTabProps) {
  const [displayName, setDisplayName] = useState(user.displayName)
  const [email, setEmail] = useState(user.email)
  const [phone, setPhone] = useState(user.phone || '')
  const [timezone, setTimezone] = useState(user.timezone)
  const [language, setLanguage] = useState(user.language)

  const handleSave = () => {
    onUpdateProfile?.({
      displayName,
      email,
      phone: phone || undefined,
      timezone,
      language,
    })
  }

  const hasChanges =
    displayName !== user.displayName ||
    email !== user.email ||
    phone !== (user.phone || '') ||
    timezone !== user.timezone ||
    language !== user.language

  return (
    <div className="p-6 lg:p-8">
      <div className="mb-8">
        <h2 className="text-2xl font-bold text-slate-900 dark:text-slate-50 mb-1">
          Profile
        </h2>
        <p className="text-slate-600 dark:text-slate-400">
          Your personal information that applies across all organizations
        </p>
      </div>

      <div className="space-y-8">
        {/* Avatar Section */}
        <div className="flex items-start gap-6">
          <div className="relative group">
            {user.avatarUrl ? (
              <img
                src={user.avatarUrl}
                alt={user.displayName}
                className="w-24 h-24 rounded-full border-2 border-slate-200 dark:border-slate-700"
              />
            ) : (
              <div className="w-24 h-24 rounded-full bg-indigo-100 dark:bg-indigo-950 flex items-center justify-center border-2 border-slate-200 dark:border-slate-700">
                <UserIcon className="w-12 h-12 text-indigo-600 dark:text-indigo-400" />
              </div>
            )}
            <button className="absolute inset-0 rounded-full bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-sm font-medium">
              Change
            </button>
          </div>
          <div className="flex-1">
            <h3 className="font-semibold text-slate-900 dark:text-slate-50 mb-1">
              Profile Photo
            </h3>
            <p className="text-sm text-slate-600 dark:text-slate-400 mb-3">
              This image will be visible across all your organizations
            </p>
            <button className="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300">
              Upload new photo
            </button>
          </div>
        </div>

        {/* Form Fields */}
        <div className="grid gap-6">
          <div className="grid sm:grid-cols-2 gap-6">
            <div>
              <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                <UserIcon className="w-4 h-4 inline mr-1.5 -mt-0.5" />
                Display Name
              </label>
              <input
                type="text"
                value={displayName}
                onChange={(e) => setDisplayName(e.target.value)}
                className="w-full px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400"
                placeholder="Your name"
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                <Mail className="w-4 h-4 inline mr-1.5 -mt-0.5" />
                Email
              </label>
              <input
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                className="w-full px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400"
                placeholder="your@email.com"
              />
            </div>
          </div>

          <div>
            <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
              <Phone className="w-4 h-4 inline mr-1.5 -mt-0.5" />
              Phone Number
            </label>
            <input
              type="tel"
              value={phone}
              onChange={(e) => setPhone(e.target.value)}
              className="w-full px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400"
              placeholder="+1 (555) 123-4567"
            />
            <p className="mt-1.5 text-sm text-slate-500 dark:text-slate-400">
              Optional - used for SMS notifications
            </p>
          </div>

          <div className="grid sm:grid-cols-2 gap-6">
            <div>
              <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                <Clock className="w-4 h-4 inline mr-1.5 -mt-0.5" />
                Timezone
              </label>
              <select
                value={timezone}
                onChange={(e) => setTimezone(e.target.value)}
                className="w-full px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400"
              >
                <option value="America/New_York">Eastern Time (ET)</option>
                <option value="America/Chicago">Central Time (CT)</option>
                <option value="America/Denver">Mountain Time (MT)</option>
                <option value="America/Los_Angeles">Pacific Time (PT)</option>
                <option value="Europe/London">London (GMT)</option>
                <option value="Europe/Paris">Paris (CET)</option>
                <option value="Asia/Tokyo">Tokyo (JST)</option>
              </select>
            </div>

            <div>
              <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                <Globe className="w-4 h-4 inline mr-1.5 -mt-0.5" />
                Language
              </label>
              <select
                value={language}
                onChange={(e) => setLanguage(e.target.value)}
                className="w-full px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400"
              >
                <option value="en-US">English (US)</option>
                <option value="en-GB">English (UK)</option>
                <option value="es-ES">Español</option>
                <option value="fr-FR">Français</option>
                <option value="de-DE">Deutsch</option>
                <option value="ja-JP">日本語</option>
              </select>
            </div>
          </div>
        </div>

        {/* Save Button */}
        {hasChanges && (
          <div className="flex justify-end pt-6 border-t border-slate-200 dark:border-slate-800">
            <button
              onClick={handleSave}
              className="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white font-medium rounded-lg transition-colors"
            >
              Save Changes
            </button>
          </div>
        )}
      </div>
    </div>
  )
}
