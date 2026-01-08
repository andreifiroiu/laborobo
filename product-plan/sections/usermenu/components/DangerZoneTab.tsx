import { AlertTriangle, Download, Trash2, X } from 'lucide-react'
import { useState } from 'react'

interface DangerZoneTabProps {
  onExportData?: () => void
  onDeleteAccount?: () => void
}

export function DangerZoneTab({ onExportData, onDeleteAccount }: DangerZoneTabProps) {
  const [showDeleteConfirm, setShowDeleteConfirm] = useState(false)
  const [deleteConfirmText, setDeleteConfirmText] = useState('')

  const handleDeleteAccount = () => {
    if (deleteConfirmText === 'DELETE') {
      onDeleteAccount?.()
      setShowDeleteConfirm(false)
      setDeleteConfirmText('')
    }
  }

  return (
    <div className="p-6 lg:p-8">
      <div className="mb-8">
        <div className="flex items-center gap-2 mb-2">
          <AlertTriangle className="w-6 h-6 text-red-600 dark:text-red-400" />
          <h2 className="text-2xl font-bold text-slate-900 dark:text-slate-50">Danger Zone</h2>
        </div>
        <p className="text-slate-600 dark:text-slate-400">
          Irreversible and destructive actions for your account
        </p>
      </div>

      <div className="space-y-6">
        {/* Export Data */}
        <div className="p-6 bg-slate-50 dark:bg-slate-800/50 rounded-xl border-2 border-slate-200 dark:border-slate-700">
          <div className="flex items-start justify-between gap-4">
            <div className="flex-1">
              <div className="flex items-center gap-2 mb-1">
                <Download className="w-5 h-5 text-slate-600 dark:text-slate-400" />
                <h3 className="font-semibold text-slate-900 dark:text-slate-50">
                  Export Your Data
                </h3>
              </div>
              <p className="text-sm text-slate-600 dark:text-slate-400 mb-3">
                Download a copy of all your personal data, settings, and preferences. This does
                not include organization data.
              </p>
              <ul className="text-sm text-slate-600 dark:text-slate-400 space-y-1 mb-4">
                <li>• Profile information and settings</li>
                <li>• Notification and appearance preferences</li>
                <li>• Connected apps and API keys</li>
                <li>• Account activity history</li>
              </ul>
            </div>
            <button
              onClick={onExportData}
              className="px-4 py-2 bg-slate-600 hover:bg-slate-700 dark:bg-slate-700 dark:hover:bg-slate-600 text-white font-medium rounded-lg transition-colors flex items-center gap-2 flex-shrink-0"
            >
              <Download className="w-4 h-4" />
              Export Data
            </button>
          </div>
        </div>

        {/* Delete Account */}
        <div className="p-6 bg-red-50 dark:bg-red-950/20 rounded-xl border-2 border-red-200 dark:border-red-900">
          <div className="flex items-start justify-between gap-4 mb-4">
            <div className="flex-1">
              <div className="flex items-center gap-2 mb-1">
                <Trash2 className="w-5 h-5 text-red-600 dark:text-red-400" />
                <h3 className="font-semibold text-red-900 dark:text-red-200">
                  Delete Your Account
                </h3>
              </div>
              <p className="text-sm text-red-800 dark:text-red-300 mb-3">
                Permanently delete your account and remove your personal data. This action cannot
                be undone.
              </p>
              <div className="text-sm text-red-800 dark:text-red-300 space-y-1 mb-4">
                <p className="font-medium">⚠️ What will be deleted:</p>
                <ul className="space-y-1 ml-4">
                  <li>• Your user profile and settings</li>
                  <li>• Your access to all organizations</li>
                  <li>• Your notification and appearance preferences</li>
                  <li>• Your connected apps and API keys</li>
                </ul>
                <p className="font-medium mt-3">
                  💡 Organizations you own will remain active but you'll lose access
                </p>
              </div>
            </div>
            {!showDeleteConfirm && (
              <button
                onClick={() => setShowDeleteConfirm(true)}
                className="px-4 py-2 bg-red-600 hover:bg-red-700 dark:bg-red-600 dark:hover:bg-red-700 text-white font-medium rounded-lg transition-colors flex items-center gap-2 flex-shrink-0"
              >
                <Trash2 className="w-4 h-4" />
                Delete Account
              </button>
            )}
          </div>

          {/* Delete Confirmation */}
          {showDeleteConfirm && (
            <div className="pt-4 border-t border-red-200 dark:border-red-900">
              <div className="p-4 bg-red-100 dark:bg-red-950/50 rounded-lg mb-4">
                <p className="text-sm font-medium text-red-900 dark:text-red-200 mb-3">
                  ⚠️ This action is permanent and cannot be undone!
                </p>
                <p className="text-sm text-red-800 dark:text-red-300 mb-3">
                  Type <span className="font-mono font-bold">DELETE</span> to confirm:
                </p>
                <input
                  type="text"
                  value={deleteConfirmText}
                  onChange={(e) => setDeleteConfirmText(e.target.value)}
                  placeholder="Type DELETE to confirm"
                  className="w-full px-4 py-2 rounded-lg border-2 border-red-300 dark:border-red-800 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-50 focus:outline-none focus:ring-2 focus:ring-red-500 font-mono"
                  autoFocus
                />
              </div>

              <div className="flex gap-3 justify-end">
                <button
                  onClick={() => {
                    setShowDeleteConfirm(false)
                    setDeleteConfirmText('')
                  }}
                  className="px-4 py-2 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors font-medium flex items-center gap-2"
                >
                  <X className="w-4 h-4" />
                  Cancel
                </button>
                <button
                  onClick={handleDeleteAccount}
                  disabled={deleteConfirmText !== 'DELETE'}
                  className={`px-4 py-2 rounded-lg font-medium transition-colors flex items-center gap-2 ${
                    deleteConfirmText === 'DELETE'
                      ? 'bg-red-600 hover:bg-red-700 text-white'
                      : 'bg-slate-300 dark:bg-slate-700 text-slate-500 dark:text-slate-500 cursor-not-allowed'
                  }`}
                >
                  <Trash2 className="w-4 h-4" />
                  Delete Account Permanently
                </button>
              </div>
            </div>
          )}
        </div>

        {/* Warning Notice */}
        <div className="p-4 bg-amber-50 dark:bg-amber-950/20 rounded-lg border border-amber-200 dark:border-amber-900">
          <p className="text-sm text-amber-900 dark:text-amber-200">
            <strong>Note:</strong> Before deleting your account, make sure you've transferred
            ownership of any organizations you own, or designated a new owner. If you're the sole
            owner of an organization, that organization will continue to exist but you won't be
            able to access it.
          </p>
        </div>
      </div>
    </div>
  )
}
