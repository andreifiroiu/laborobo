import { useState } from 'react'
import {
  Lock,
  Shield,
  Smartphone,
  Monitor,
  ExternalLink,
  Trash2,
  Plus,
  Key,
  LogOut,
  MapPin,
} from 'lucide-react'
import type { Session, ConnectedApp, ApiKey } from '@/../product/sections/usermenu/types'

interface SecurityTabProps {
  sessions: Session[]
  connectedApps: ConnectedApp[]
  apiKeys: ApiKey[]
  onChangePassword?: () => void
  onToggle2FA?: (enabled: boolean) => void
  onSignOutSession?: (sessionId: string) => void
  onDisconnectApp?: (appId: string) => void
  onRevokeApiKey?: (keyId: string) => void
  onCreateApiKey?: (name: string) => void
}

export function SecurityTab({
  sessions,
  connectedApps,
  apiKeys,
  onChangePassword,
  onToggle2FA,
  onSignOutSession,
  onDisconnectApp,
  onRevokeApiKey,
  onCreateApiKey,
}: SecurityTabProps) {
  const [twoFactorEnabled, setTwoFactorEnabled] = useState(false)
  const [showCreateApiKey, setShowCreateApiKey] = useState(false)
  const [apiKeyName, setApiKeyName] = useState('')

  const handleToggle2FA = () => {
    const newValue = !twoFactorEnabled
    setTwoFactorEnabled(newValue)
    onToggle2FA?.(newValue)
  }

  const handleCreateApiKey = () => {
    if (apiKeyName.trim()) {
      onCreateApiKey?.(apiKeyName)
      setApiKeyName('')
      setShowCreateApiKey(false)
    }
  }

  const formatDate = (dateString: string | null) => {
    if (!dateString) return 'Never'
    const date = new Date(dateString)
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
  }

  const formatDateTime = (dateString: string) => {
    const date = new Date(dateString)
    return date.toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      hour: 'numeric',
      minute: '2-digit',
    })
  }

  return (
    <div className="p-6 lg:p-8">
      <div className="mb-8">
        <h2 className="text-2xl font-bold text-slate-900 dark:text-slate-50 mb-1">Security</h2>
        <p className="text-slate-600 dark:text-slate-400">
          Manage your account security and access
        </p>
      </div>

      <div className="space-y-8">
        {/* Password */}
        <div className="p-6 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700">
          <div className="flex items-start justify-between">
            <div className="flex-1">
              <div className="flex items-center gap-2 mb-1">
                <Lock className="w-5 h-5 text-slate-600 dark:text-slate-400" />
                <h3 className="font-semibold text-slate-900 dark:text-slate-50">Password</h3>
              </div>
              <p className="text-sm text-slate-600 dark:text-slate-400">
                Last changed 3 months ago
              </p>
            </div>
            <button
              onClick={onChangePassword}
              className="px-4 py-2 text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/50 rounded-lg transition-colors"
            >
              Change Password
            </button>
          </div>
        </div>

        {/* Two-Factor Authentication */}
        <div className="p-6 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700">
          <div className="flex items-start justify-between mb-4">
            <div className="flex-1">
              <div className="flex items-center gap-2 mb-1">
                <Shield className="w-5 h-5 text-slate-600 dark:text-slate-400" />
                <h3 className="font-semibold text-slate-900 dark:text-slate-50">
                  Two-Factor Authentication
                </h3>
              </div>
              <p className="text-sm text-slate-600 dark:text-slate-400">
                Add an extra layer of security to your account
              </p>
            </div>
            <button
              onClick={handleToggle2FA}
              className={`
                w-12 h-7 rounded-full transition-colors relative flex-shrink-0
                ${
                  twoFactorEnabled
                    ? 'bg-indigo-600 dark:bg-indigo-500'
                    : 'bg-slate-300 dark:bg-slate-700'
                }
              `}
            >
              <span
                className={`
                  absolute top-0.5 left-0.5 w-6 h-6 bg-white rounded-full transition-transform
                  ${twoFactorEnabled ? 'translate-x-5' : 'translate-x-0'}
                `}
              />
            </button>
          </div>
          {twoFactorEnabled && (
            <div className="pt-4 border-t border-slate-200 dark:border-slate-700">
              <p className="text-sm text-emerald-600 dark:text-emerald-400 mb-3">
                Two-factor authentication is enabled via authenticator app
              </p>
              <button className="text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200">
                Manage 2FA methods
              </button>
            </div>
          )}
        </div>

        {/* Active Sessions */}
        <div>
          <h3 className="font-semibold text-slate-900 dark:text-slate-50 mb-4">
            Active Sessions
          </h3>
          <div className="space-y-3">
            {sessions.map((session) => (
              <div
                key={session.id}
                className="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-lg border border-slate-200 dark:border-slate-700"
              >
                <div className="flex items-start justify-between">
                  <div className="flex items-start gap-3 flex-1">
                    <div className="w-10 h-10 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center flex-shrink-0">
                      {session.device.includes('iPhone') || session.device.includes('iPad') ? (
                        <Smartphone className="w-5 h-5 text-slate-600 dark:text-slate-400" />
                      ) : (
                        <Monitor className="w-5 h-5 text-slate-600 dark:text-slate-400" />
                      )}
                    </div>
                    <div className="flex-1 min-w-0">
                      <div className="flex items-center gap-2 mb-1">
                        <p className="font-medium text-slate-900 dark:text-slate-50">
                          {session.device}
                        </p>
                        {session.isCurrent && (
                          <span className="px-2 py-0.5 text-xs font-medium bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 rounded">
                            Current
                          </span>
                        )}
                      </div>
                      <div className="text-sm text-slate-600 dark:text-slate-400 space-y-0.5">
                        <p>{session.browser}</p>
                        <div className="flex items-center gap-1">
                          <MapPin className="w-3 h-3" />
                          <span>
                            {session.location} • {session.ipAddress}
                          </span>
                        </div>
                        <p>Last active {formatDateTime(session.lastActive)}</p>
                      </div>
                    </div>
                  </div>
                  {!session.isCurrent && (
                    <button
                      onClick={() => onSignOutSession?.(session.id)}
                      className="p-2 text-slate-600 dark:text-slate-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/30 rounded-lg transition-colors"
                      title="Sign out this session"
                    >
                      <LogOut className="w-4 h-4" />
                    </button>
                  )}
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Connected Apps */}
        <div>
          <h3 className="font-semibold text-slate-900 dark:text-slate-50 mb-4">
            Connected Apps
          </h3>
          <div className="space-y-3">
            {connectedApps.map((app) => (
              <div
                key={app.id}
                className="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-lg border border-slate-200 dark:border-slate-700"
              >
                <div className="flex items-start justify-between">
                  <div className="flex items-start gap-3 flex-1">
                    <div className="w-10 h-10 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center flex-shrink-0">
                      {app.avatarUrl ? (
                        <img src={app.avatarUrl} alt={app.name} className="w-5 h-5" />
                      ) : (
                        <ExternalLink className="w-5 h-5 text-slate-600 dark:text-slate-400" />
                      )}
                    </div>
                    <div className="flex-1">
                      <p className="font-medium text-slate-900 dark:text-slate-50 mb-1">
                        {app.name}
                      </p>
                      <p className="text-sm text-slate-600 dark:text-slate-400 mb-2">
                        Connected {formatDate(app.connectedAt)}
                      </p>
                      <div className="flex flex-wrap gap-1">
                        {app.permissions.map((permission) => (
                          <span
                            key={permission}
                            className="px-2 py-0.5 text-xs bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded"
                          >
                            {permission}
                          </span>
                        ))}
                      </div>
                    </div>
                  </div>
                  <button
                    onClick={() => onDisconnectApp?.(app.id)}
                    className="p-2 text-slate-600 dark:text-slate-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/30 rounded-lg transition-colors"
                    title="Disconnect"
                  >
                    <Trash2 className="w-4 h-4" />
                  </button>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* API Keys */}
        <div>
          <div className="flex items-center justify-between mb-4">
            <h3 className="font-semibold text-slate-900 dark:text-slate-50">API Keys</h3>
            <button
              onClick={() => setShowCreateApiKey(true)}
              className="flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/50 rounded-lg transition-colors"
            >
              <Plus className="w-4 h-4" />
              Create Key
            </button>
          </div>

          {showCreateApiKey && (
            <div className="mb-4 p-4 bg-indigo-50 dark:bg-indigo-950/30 rounded-lg border border-indigo-200 dark:border-indigo-800">
              <div className="flex gap-2">
                <input
                  type="text"
                  value={apiKeyName}
                  onChange={(e) => setApiKeyName(e.target.value)}
                  placeholder="Key name (e.g., Production API)"
                  className="flex-1 px-3 py-2 rounded-lg border border-indigo-300 dark:border-indigo-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                  autoFocus
                />
                <button
                  onClick={handleCreateApiKey}
                  className="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white font-medium rounded-lg transition-colors"
                >
                  Create
                </button>
                <button
                  onClick={() => {
                    setShowCreateApiKey(false)
                    setApiKeyName('')
                  }}
                  className="px-4 py-2 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-800 rounded-lg transition-colors"
                >
                  Cancel
                </button>
              </div>
            </div>
          )}

          <div className="space-y-3">
            {apiKeys.map((key) => (
              <div
                key={key.id}
                className="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-lg border border-slate-200 dark:border-slate-700"
              >
                <div className="flex items-start justify-between">
                  <div className="flex items-start gap-3 flex-1">
                    <div className="w-10 h-10 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center flex-shrink-0">
                      <Key className="w-5 h-5 text-slate-600 dark:text-slate-400" />
                    </div>
                    <div className="flex-1 min-w-0">
                      <p className="font-medium text-slate-900 dark:text-slate-50 mb-1">
                        {key.name}
                      </p>
                      <p className="text-sm font-mono text-slate-600 dark:text-slate-400 mb-2">
                        {key.keyPreview}
                      </p>
                      <div className="text-sm text-slate-600 dark:text-slate-400 space-y-0.5">
                        <p>Created {formatDate(key.createdAt)}</p>
                        <p>Last used {formatDate(key.lastUsed)}</p>
                        {key.expiresAt && (
                          <p className="text-amber-600 dark:text-amber-400">
                            Expires {formatDate(key.expiresAt)}
                          </p>
                        )}
                      </div>
                    </div>
                  </div>
                  <button
                    onClick={() => onRevokeApiKey?.(key.id)}
                    className="p-2 text-slate-600 dark:text-slate-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/30 rounded-lg transition-colors"
                    title="Revoke key"
                  >
                    <Trash2 className="w-4 h-4" />
                  </button>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  )
}
