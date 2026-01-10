import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { SidebarProvider, SidebarInset } from '@/components/ui/sidebar';
import { SettingsSidebar } from './components/settings-sidebar';
import { WorkspaceSection } from './components/workspace-section';
import { TeamSection } from './components/team-section';
import { AIAgentsSection } from './components/ai-agents-section';
import { ComingSoonSection } from './components/coming-soon-section';
import type { BreadcrumbItem } from '@/types';
import type { SettingsPageProps } from '@/types/settings';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Settings', href: '/settings' },
];

const tabTitles: Record<string, string> = {
    'workspace': 'Workspace',
    'team': 'Team & Permissions',
    'ai-agents': 'AI Agents',
    'integrations': 'Integrations',
    'billing': 'Billing',
    'notifications': 'Notifications',
    'audit-log': 'Audit Log',
};

export default function Settings({
    workspaceSettings,
    teamMembers,
    aiAgents,
    globalAISettings,
    agentActivityLogs,
}: SettingsPageProps) {
    const searchParams = new URLSearchParams(window.location.search);
    const activeTab = searchParams.get('tab') || 'workspace';
    const pageTitle = tabTitles[activeTab] || 'Settings';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={pageTitle} />

            <SidebarProvider defaultOpen={true}>
                <div className="flex h-full">
                    <SettingsSidebar />

                    <SidebarInset>
                        <div className="flex h-full flex-col gap-4 p-4">
                            <div className="flex items-center justify-between">
                                <h1 className="text-2xl font-semibold">{pageTitle}</h1>
                            </div>

                            <div className="flex-1">
                                {activeTab === 'workspace' && (
                                    <WorkspaceSection settings={workspaceSettings} />
                                )}
                                {activeTab === 'team' && (
                                    <TeamSection members={teamMembers} />
                                )}
                                {activeTab === 'ai-agents' && (
                                    <AIAgentsSection
                                        agents={aiAgents}
                                        globalSettings={globalAISettings}
                                        activityLogs={agentActivityLogs}
                                    />
                                )}
                                {activeTab === 'integrations' && (
                                    <ComingSoonSection
                                        title="Integrations"
                                        description="Connect third-party services"
                                    />
                                )}
                                {activeTab === 'billing' && (
                                    <ComingSoonSection
                                        title="Billing & Usage"
                                        description="Manage subscription plan"
                                    />
                                )}
                                {activeTab === 'notifications' && (
                                    <ComingSoonSection
                                        title="Notification Preferences"
                                        description="Configure notification preferences"
                                    />
                                )}
                                {activeTab === 'audit-log' && (
                                    <ComingSoonSection
                                        title="Audit Log"
                                        description="Review activity history"
                                    />
                                )}
                            </div>
                        </div>
                    </SidebarInset>
                </div>
            </SidebarProvider>
        </AppLayout>
    );
}
