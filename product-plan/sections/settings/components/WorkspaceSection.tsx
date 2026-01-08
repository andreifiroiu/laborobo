import { useState } from 'react'
import type { WorkspaceSettings } from '@/../product/sections/settings/types'

interface WorkspaceSectionProps {
  settings: WorkspaceSettings
  onUpdate?: (settings: Partial<WorkspaceSettings>) => void
}

export function WorkspaceSection({ settings, onUpdate }: WorkspaceSectionProps) {
  const [formData, setFormData] = useState(settings)
  const [hasChanges, setHasChanges] = useState(false)

  const handleChange = (field: keyof WorkspaceSettings, value: string) => {
    setFormData({ ...formData, [field]: value })
    setHasChanges(true)
  }

  const handleSave = () => {
    onUpdate?.(formData)
    setHasChanges(false)
  }

  return (
    <div className="max-w-4xl mx-auto p-8">
      <div className="mb-8">
        <h2 className="text-2xl font-semibold text-slate-900 dark:text-slate-50 mb-2">
          Workspace Settings
        </h2>
        <p className="text-slate-600 dark:text-slate-400">
          Configure your workspace name, timezone, and preferences
        </p>
      </div>

      <div className="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-6 space-y-6">
        <div>
          <label className="block text-sm font-medium text-slate-900 dark:text-slate-50 mb-2">
            Workspace Name
          </label>
          <input
            type="text"
            value={formData.name}
            onChange={(e) => handleChange('name', e.target.value)}
            className="w-full px-4 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500"
          />
        </div>

        <div className="grid grid-cols-2 gap-6">
          <div>
            <label className="block text-sm font-medium text-slate-900 dark:text-slate-50 mb-2">
              Timezone
            </label>
            <select
              value={formData.timezone}
              onChange={(e) => handleChange('timezone', e.target.value)}
              className="w-full px-4 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            >
              <option>America/Los_Angeles</option>
              <option>America/New_York</option>
              <option>Europe/London</option>
              <option>UTC</option>
            </select>
          </div>

          <div>
            <label className="block text-sm font-medium text-slate-900 dark:text-slate-50 mb-2">
              Currency
            </label>
            <select
              value={formData.currency}
              onChange={(e) => handleChange('currency', e.target.value)}
              className="w-full px-4 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            >
              <option>USD</option>
              <option>EUR</option>
              <option>GBP</option>
            </select>
          </div>
        </div>

        <div>
          <label className="block text-sm font-medium text-slate-900 dark:text-slate-50 mb-2">
            Brand Color
          </label>
          <input
            type="color"
            value={formData.brandColor}
            onChange={(e) => handleChange('brandColor', e.target.value)}
            className="h-10 w-20 rounded border border-slate-300 dark:border-slate-700"
          />
        </div>

        {hasChanges && (
          <div className="flex gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
            <button
              onClick={handleSave}
              className="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors"
            >
              Save Changes
            </button>
            <button
              onClick={() => {
                setFormData(settings)
                setHasChanges(false)
              }}
              className="px-4 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-900 dark:text-slate-50 text-sm font-medium rounded-lg transition-colors"
            >
              Cancel
            </button>
          </div>
        )}
      </div>
    </div>
  )
}
