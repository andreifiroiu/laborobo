import { useState } from 'react'
import type { SettingsProps, SettingsSection } from '@/../product/sections/settings/types'
import { WorkspaceSection } from './WorkspaceSection'
import { TeamSection } from './TeamSection'
import { AIAgentsSection } from './AIAgentsSection'
import { IntegrationsSection } from './IntegrationsSection'
import { BillingSection } from './BillingSection'
import { NotificationsSection } from './NotificationsSection'
import { AuditLogSection } from './AuditLogSection'

export function Settings({
  workspaceSettings,
  teamMembers,
  aiAgents,
  agentConfigurations,
  globalAISettings,
  agentActivityLogs,
  integrations,
  billingInfo,
  invoices,
  notificationPreferences,
  auditLogEntries,
  activeSection = 'workspace',
  filters,
  onSectionChange,
  onUpdateWorkspaceSettings,
  onInviteTeamMember,
  onUpdateTeamMemberRole,
  onRemoveTeamMember,
  onViewTeamMember,
  onToggleAgent,
  onUpdateAgentConfig,
  onUpdateGlobalAISettings,
  onViewAgentActivity,
  onApproveAgentOutput,
  onRejectAgentOutput,
  onConnectIntegration,
  onDisconnectIntegration,
  onConfigureIntegration,
  onDownloadInvoice,
  onUpdatePaymentMethod,
  onChangePlan,
  onUpdateNotificationPreferences,
  onFilterAuditLog,
  onExportAuditLog,
  onViewAuditLogEntry,
}: SettingsProps) {
  const [currentSection, setCurrentSection] = useState<SettingsSection>(activeSection)

  const handleSectionChange = (section: SettingsSection) => {
    setCurrentSection(section)
    onSectionChange?.(section)
  }

  const sections = [
    { id: 'workspace' as SettingsSection, label: 'Workspace', icon: '🏢' },
    { id: 'team' as SettingsSection, label: 'Team & Permissions', icon: '👥' },
    { id: 'ai-agents' as SettingsSection, label: 'AI Agents', icon: '🤖' },
    { id: 'integrations' as SettingsSection, label: 'Integrations', icon: '🔗' },
    { id: 'billing' as SettingsSection, label: 'Billing', icon: '💳' },
    { id: 'notifications' as SettingsSection, label: 'Notifications', icon: '🔔' },
    { id: 'audit-log' as SettingsSection, label: 'Audit Log', icon: '📋' },
  ]

  return (
    <div className="min-h-screen bg-slate-50 dark:bg-slate-950 flex">
      {/* Sidebar Navigation */}
      <div className="w-64 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 flex-shrink-0">
        <div className="p-6">
          <h1 className="text-xl font-semibold text-slate-900 dark:text-slate-50 mb-1">
            Settings
          </h1>
          <p className="text-sm text-slate-600 dark:text-slate-400">
            Manage your workspace
          </p>
        </div>
        <nav className="px-3 pb-6">
          {sections.map((section) => (
            <button
              key={section.id}
              onClick={() => handleSectionChange(section.id)}
              className={`w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-left transition-colors mb-1 ${
                currentSection === section.id
                  ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300'
                  : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'
              }`}
            >
              <span className="text-lg">{section.icon}</span>
              <span className="text-sm font-medium">{section.label}</span>
            </button>
          ))}
        </nav>
      </div>

      {/* Main Content */}
      <div className="flex-1 overflow-y-auto">
        {currentSection === 'workspace' && (
          <WorkspaceSection
            settings={workspaceSettings}
            onUpdate={onUpdateWorkspaceSettings}
          />
        )}
        {currentSection === 'team' && (
          <TeamSection
            teamMembers={teamMembers}
            onInvite={onInviteTeamMember}
            onUpdateRole={onUpdateTeamMemberRole}
            onRemove={onRemoveTeamMember}
            onView={onViewTeamMember}
          />
        )}
        {currentSection === 'ai-agents' && (
          <AIAgentsSection
            agents={aiAgents}
            configurations={agentConfigurations}
            globalSettings={globalAISettings}
            activityLogs={agentActivityLogs}
            onToggle={onToggleAgent}
            onUpdateConfig={onUpdateAgentConfig}
            onUpdateGlobalSettings={onUpdateGlobalAISettings}
            onViewActivity={onViewAgentActivity}
            onApprove={onApproveAgentOutput}
            onReject={onRejectAgentOutput}
          />
        )}
        {currentSection === 'integrations' && (
          <IntegrationsSection
            integrations={integrations}
            onConnect={onConnectIntegration}
            onDisconnect={onDisconnectIntegration}
            onConfigure={onConfigureIntegration}
          />
        )}
        {currentSection === 'billing' && (
          <BillingSection
            billingInfo={billingInfo}
            invoices={invoices}
            onDownloadInvoice={onDownloadInvoice}
            onUpdatePaymentMethod={onUpdatePaymentMethod}
            onChangePlan={onChangePlan}
          />
        )}
        {currentSection === 'notifications' && (
          <NotificationsSection
            preferences={notificationPreferences}
            onUpdate={onUpdateNotificationPreferences}
          />
        )}
        {currentSection === 'audit-log' && (
          <AuditLogSection
            entries={auditLogEntries}
            filters={filters}
            onFilter={onFilterAuditLog}
            onExport={onExportAuditLog}
            onViewEntry={onViewAuditLogEntry}
          />
        )}
      </div>
    </div>
  )
}
