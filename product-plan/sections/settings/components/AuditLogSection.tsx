import { useState } from 'react'
import type { AuditLogEntry } from '../types'

interface AuditLogSectionProps {
  entries: AuditLogEntry[]
  onExport?: () => void
}

export function AuditLogSection({ entries, onExport }: AuditLogSectionProps) {
  const auditLog = entries
  const [searchQuery, setSearchQuery] = useState('')
  const [actionFilter, setActionFilter] = useState<string>('all')

  const actionTypes = Array.from(new Set(auditLog.map((entry) => entry.action)))

  const filteredLog = auditLog.filter((entry) => {
    const matchesSearch =
      searchQuery === '' ||
      entry.action.toLowerCase().includes(searchQuery.toLowerCase()) ||
      entry.actorName.toLowerCase().includes(searchQuery.toLowerCase()) ||
      entry.details.toLowerCase().includes(searchQuery.toLowerCase())

    const matchesAction = actionFilter === 'all' || entry.action === actionFilter

    return matchesSearch && matchesAction
  })

  return (
    <div className="max-w-6xl mx-auto p-8">
      <div className="flex items-center justify-between mb-8">
        <div>
          <h2 className="text-2xl font-semibold text-slate-900 dark:text-slate-50 mb-2">
            Audit Log
          </h2>
          <p className="text-slate-600 dark:text-slate-400">
            View all activity and changes in your workspace
          </p>
        </div>
        <button
          onClick={onExport}
          className="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors"
        >
          Export Log
        </button>
      </div>

      {/* Filters */}
      <div className="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-4 mb-6">
        <div className="flex gap-4">
          <div className="flex-1">
            <input
              type="text"
              placeholder="Search by action, user, or details..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="w-full px-4 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-50 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            />
          </div>
          <div>
            <select
              value={actionFilter}
              onChange={(e) => setActionFilter(e.target.value)}
              className="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            >
              <option value="all">All Actions</option>
              {actionTypes.map((action) => (
                <option key={action} value={action}>
                  {action}
                </option>
              ))}
            </select>
          </div>
        </div>
      </div>

      {/* Audit Log Table */}
      <div className="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg overflow-hidden">
        <table className="min-w-full">
          <thead className="bg-slate-50 dark:bg-slate-800/50">
            <tr>
              <th className="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">
                Timestamp
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">
                User
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">
                Action
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">
                Details
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">
                IP Address
              </th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-200 dark:divide-slate-800">
            {filteredLog.length === 0 ? (
              <tr>
                <td colSpan={5} className="px-6 py-8 text-center">
                  <p className="text-sm text-slate-500 dark:text-slate-400">
                    No audit log entries found
                  </p>
                </td>
              </tr>
            ) : (
              filteredLog.map((entry) => (
                <tr key={entry.id}>
                  <td className="px-6 py-4 text-sm text-slate-600 dark:text-slate-400">
                    {new Date(entry.timestamp).toLocaleString()}
                  </td>
                  <td className="px-6 py-4 text-sm font-medium text-slate-900 dark:text-slate-50">
                    {entry.actorName}
                  </td>
                  <td className="px-6 py-4">
                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                      {entry.action}
                    </span>
                  </td>
                  <td className="px-6 py-4 text-sm text-slate-600 dark:text-slate-400">
                    {entry.details}
                  </td>
                  <td className="px-6 py-4 text-sm font-mono text-slate-500 dark:text-slate-400">
                    {entry.ipAddress}
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>

      {/* Results Count */}
      {filteredLog.length > 0 && (
        <div className="mt-4 text-sm text-slate-600 dark:text-slate-400">
          Showing {filteredLog.length} of {auditLog.length} entries
        </div>
      )}
    </div>
  )
}
