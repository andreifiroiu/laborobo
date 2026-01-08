import { useState } from 'react'
import type {
  AIAgent,
  AgentConfiguration,
  GlobalAISettings,
  AgentActivityLog,
} from '@/../product/sections/settings/types'

interface AIAgentsSectionProps {
  agents: AIAgent[]
  configurations: AgentConfiguration[]
  globalSettings: GlobalAISettings
  activityLogs: AgentActivityLog[]
  onToggle?: (agentId: string, enabled: boolean) => void
  onUpdateConfig?: (agentId: string, config: Partial<AgentConfiguration>) => void
  onUpdateGlobalSettings?: (settings: Partial<GlobalAISettings>) => void
  onViewActivity?: (logId: string) => void
  onApprove?: (logId: string) => void
  onReject?: (logId: string) => void
}

export function AIAgentsSection({
  agents,
  configurations,
  globalSettings,
  activityLogs,
  onToggle,
  onUpdateConfig,
}: AIAgentsSectionProps) {
  const [expandedAgent, setExpandedAgent] = useState<string | null>(null)
  const [activeTab, setActiveTab] = useState<'config' | 'activity' | 'budget'>('config')

  const getConfig = (agentId: string) =>
    configurations.find((c) => c.agentId === agentId)

  const getAgentLogs = (agentId: string) =>
    activityLogs.filter((log) => log.agentId === agentId).slice(0, 10)

  return (
    <div className="max-w-6xl mx-auto p-8">
      <div className="mb-8">
        <h2 className="text-2xl font-semibold text-slate-900 dark:text-slate-50 mb-2">
          AI Agents
        </h2>
        <p className="text-slate-600 dark:text-slate-400">
          Configure AI agents, budgets, and permissions
        </p>
      </div>

      {/* Global Budget */}
      <div className="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-6 mb-6">
        <h3 className="text-lg font-semibold text-slate-900 dark:text-slate-50 mb-4">
          Global AI Budget
        </h3>
        <div className="grid grid-cols-3 gap-6">
          <div>
            <p className="text-sm text-slate-600 dark:text-slate-400 mb-1">Monthly Budget</p>
            <p className="text-2xl font-semibold text-slate-900 dark:text-slate-50">
              ${globalSettings.totalMonthlyBudget}
            </p>
          </div>
          <div>
            <p className="text-sm text-slate-600 dark:text-slate-400 mb-1">Current Spend</p>
            <p className="text-2xl font-semibold text-emerald-600 dark:text-emerald-400">
              ${globalSettings.currentMonthSpend.toFixed(2)}
            </p>
          </div>
          <div>
            <p className="text-sm text-slate-600 dark:text-slate-400 mb-1">Remaining</p>
            <p className="text-2xl font-semibold text-slate-900 dark:text-slate-50">
              ${(globalSettings.totalMonthlyBudget - globalSettings.currentMonthSpend).toFixed(2)}
            </p>
          </div>
        </div>
      </div>

      {/* Agent List */}
      <div className="space-y-3">
        {agents.map((agent) => {
          const config = getConfig(agent.id)
          const isExpanded = expandedAgent === agent.id
          const logs = getAgentLogs(agent.id)

          return (
            <div
              key={agent.id}
              className="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg overflow-hidden"
            >
              {/* Agent Header */}
              <button
                onClick={() => setExpandedAgent(isExpanded ? null : agent.id)}
                className="w-full flex items-center justify-between p-6 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors"
              >
                <div className="flex items-center gap-4">
                  <div className="text-3xl">🤖</div>
                  <div className="text-left">
                    <h4 className="text-lg font-semibold text-slate-900 dark:text-slate-50">
                      {agent.name}
                    </h4>
                    <p className="text-sm text-slate-600 dark:text-slate-400">
                      {agent.description}
                    </p>
                  </div>
                </div>
                <div className="flex items-center gap-4">
                  <div className="text-right mr-4">
                    <p className="text-sm text-slate-600 dark:text-slate-400">This Month</p>
                    <p className="text-lg font-semibold text-slate-900 dark:text-slate-50">
                      ${config?.currentMonthSpend.toFixed(2) || '0.00'}
                    </p>
                  </div>
                  <span
                    className={`inline-flex items-center px-3 py-1 rounded-full text-xs font-medium ${
                      agent.status === 'enabled'
                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
                        : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'
                    }`}
                  >
                    {agent.status}
                  </span>
                  <svg
                    className={`w-5 h-5 text-slate-400 transition-transform ${isExpanded ? 'rotate-180' : ''}`}
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      strokeWidth={2}
                      d="M19 9l-7 7-7-7"
                    />
                  </svg>
                </div>
              </button>

              {/* Expanded Content */}
              {isExpanded && config && (
                <div className="border-t border-slate-200 dark:border-slate-800">
                  {/* Tabs */}
                  <div className="flex gap-6 px-6 pt-4 border-b border-slate-200 dark:border-slate-800">
                    <button
                      onClick={() => setActiveTab('config')}
                      className={`pb-3 text-sm font-medium border-b-2 transition-colors ${
                        activeTab === 'config'
                          ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400'
                          : 'border-transparent text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-50'
                      }`}
                    >
                      Config
                    </button>
                    <button
                      onClick={() => setActiveTab('activity')}
                      className={`pb-3 text-sm font-medium border-b-2 transition-colors ${
                        activeTab === 'activity'
                          ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400'
                          : 'border-transparent text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-50'
                      }`}
                    >
                      Activity
                    </button>
                    <button
                      onClick={() => setActiveTab('budget')}
                      className={`pb-3 text-sm font-medium border-b-2 transition-colors ${
                        activeTab === 'budget'
                          ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400'
                          : 'border-transparent text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-50'
                      }`}
                    >
                      Budget
                    </button>
                  </div>

                  {/* Tab Content */}
                  <div className="p-6">
                    {activeTab === 'config' && (
                      <div className="space-y-4">
                        <div className="flex items-center justify-between">
                          <div>
                            <p className="font-medium text-slate-900 dark:text-slate-50">
                              Enable Agent
                            </p>
                            <p className="text-sm text-slate-600 dark:text-slate-400">
                              Allow this agent to run automatically
                            </p>
                          </div>
                          <button
                            onClick={() => onToggle?.(agent.id, !config.enabled)}
                            className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${
                              config.enabled ? 'bg-indigo-600' : 'bg-slate-300 dark:bg-slate-600'
                            }`}
                          >
                            <span
                              className={`inline-block h-4 w-4 transform rounded-full bg-white transition-transform ${
                                config.enabled ? 'translate-x-6' : 'translate-x-1'
                              }`}
                            />
                          </button>
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                          <div>
                            <label className="block text-sm font-medium text-slate-900 dark:text-slate-50 mb-2">
                              Daily Run Limit
                            </label>
                            <input
                              type="number"
                              value={config.dailyRunLimit}
                              onChange={(e) =>
                                onUpdateConfig?.(agent.id, {
                                  dailyRunLimit: parseInt(e.target.value),
                                })
                              }
                              className="w-full px-3 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded text-slate-900 dark:text-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            />
                          </div>
                          <div>
                            <label className="block text-sm font-medium text-slate-900 dark:text-slate-50 mb-2">
                              Monthly Budget Cap
                            </label>
                            <input
                              type="number"
                              value={config.monthlyBudgetCap}
                              onChange={(e) =>
                                onUpdateConfig?.(agent.id, {
                                  monthlyBudgetCap: parseFloat(e.target.value),
                                })
                              }
                              className="w-full px-3 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded text-slate-900 dark:text-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            />
                          </div>
                        </div>
                      </div>
                    )}

                    {activeTab === 'activity' && (
                      <div className="space-y-2">
                        {logs.map((log) => (
                          <div
                            key={log.id}
                            className="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-lg"
                          >
                            <div className="flex items-start justify-between mb-2">
                              <p className="text-sm font-medium text-slate-900 dark:text-slate-50">
                                {log.runType.replace(/_/g, ' ')}
                              </p>
                              <span
                                className={`text-xs px-2 py-0.5 rounded ${
                                  log.approvalStatus === 'approved'
                                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
                                    : log.approvalStatus === 'pending'
                                      ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300'
                                      : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'
                                }`}
                              >
                                {log.approvalStatus}
                              </span>
                            </div>
                            <p className="text-xs text-slate-600 dark:text-slate-400 mb-1">
                              {new Date(log.timestamp).toLocaleString()} • ${log.cost.toFixed(4)}
                            </p>
                            <p className="text-sm text-slate-700 dark:text-slate-300">
                              {log.input.substring(0, 100)}...
                            </p>
                          </div>
                        ))}
                      </div>
                    )}

                    {activeTab === 'budget' && (
                      <div className="space-y-4">
                        <div className="grid grid-cols-3 gap-4">
                          <div>
                            <p className="text-sm text-slate-600 dark:text-slate-400 mb-1">
                              Budget Cap
                            </p>
                            <p className="text-xl font-semibold text-slate-900 dark:text-slate-50">
                              ${config.monthlyBudgetCap}
                            </p>
                          </div>
                          <div>
                            <p className="text-sm text-slate-600 dark:text-slate-400 mb-1">Spent</p>
                            <p className="text-xl font-semibold text-emerald-600 dark:text-emerald-400">
                              ${config.currentMonthSpend.toFixed(2)}
                            </p>
                          </div>
                          <div>
                            <p className="text-sm text-slate-600 dark:text-slate-400 mb-1">
                              Remaining
                            </p>
                            <p className="text-xl font-semibold text-slate-900 dark:text-slate-50">
                              ${(config.monthlyBudgetCap - config.currentMonthSpend).toFixed(2)}
                            </p>
                          </div>
                        </div>
                        <div>
                          <p className="text-sm text-slate-600 dark:text-slate-400 mb-2">
                            Budget Usage
                          </p>
                          <div className="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-3">
                            <div
                              className={`h-3 rounded-full transition-all ${
                                (config.currentMonthSpend / config.monthlyBudgetCap) * 100 >= 90
                                  ? 'bg-red-600'
                                  : (config.currentMonthSpend / config.monthlyBudgetCap) * 100 >= 75
                                    ? 'bg-amber-600'
                                    : 'bg-emerald-600'
                              }`}
                              style={{
                                width: `${Math.min((config.currentMonthSpend / config.monthlyBudgetCap) * 100, 100)}%`,
                              }}
                            />
                          </div>
                        </div>
                      </div>
                    )}
                  </div>
                </div>
              )}
            </div>
          )
        })}
      </div>
    </div>
  )
}
