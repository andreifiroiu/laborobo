import { useForm } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import type { NotificationPreferences } from '@/types/settings';

interface NotificationsSectionProps {
    preferences: NotificationPreferences;
}

export function NotificationsSection({ preferences }: NotificationsSectionProps) {
    const form = useForm({
        email_project_updates: preferences.projectUpdates.email,
        push_project_updates: preferences.projectUpdates.push,
        slack_project_updates: preferences.projectUpdates.slack,
        email_task_assignments: preferences.taskAssignments.email,
        push_task_assignments: preferences.taskAssignments.push,
        slack_task_assignments: preferences.taskAssignments.slack,
        email_approval_requests: preferences.approvalRequests.email,
        push_approval_requests: preferences.approvalRequests.push,
        slack_approval_requests: preferences.approvalRequests.slack,
        email_blockers: preferences.blockers.email,
        push_blockers: preferences.blockers.push,
        slack_blockers: preferences.blockers.slack,
        email_deadlines: preferences.deadlines.email,
        push_deadlines: preferences.deadlines.push,
        slack_deadlines: preferences.deadlines.slack,
        email_weekly_digest: preferences.weeklyDigest.email,
        push_weekly_digest: preferences.weeklyDigest.push,
        slack_weekly_digest: preferences.weeklyDigest.slack,
        email_agent_activity: preferences.agentActivity.email,
        push_agent_activity: preferences.agentActivity.push,
        slack_agent_activity: preferences.agentActivity.slack,
    });

    const handleToggle = (field: string) => {
        form.setData(field as keyof typeof form.data, !form.data[field as keyof typeof form.data]);
        form.patch('/settings/notifications', { preserveScroll: true });
    };

    const categories = [
        { key: 'projectUpdates', label: 'Project Updates', description: 'Updates about project status and progress' },
        { key: 'taskAssignments', label: 'Task Assignments', description: 'When tasks are assigned to you' },
        { key: 'approvalRequests', label: 'Approval Requests', description: 'When your approval is needed' },
        { key: 'blockers', label: 'Blockers', description: 'When work is blocked' },
        { key: 'deadlines', label: 'Deadlines', description: 'Upcoming and overdue deadlines' },
        { key: 'weeklyDigest', label: 'Weekly Digest', description: 'Summary of the week' },
        { key: 'agentActivity', label: 'AI Agent Activity', description: 'Agent runs and outputs' },
    ];

    return (
        <Card>
            <CardHeader>
                <CardTitle>Notification Preferences</CardTitle>
                <CardDescription>
                    Choose how you want to be notified about activity in your workspace
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div className="space-y-6">
                    {categories.map((category) => (
                        <div key={category.key} className="border-b pb-6 last:border-0">
                            <div className="mb-4">
                                <h3 className="font-medium">{category.label}</h3>
                                <p className="text-sm text-muted-foreground">{category.description}</p>
                            </div>
                            <div className="grid grid-cols-3 gap-4">
                                {['email', 'push', 'slack'].map((channel) => {
                                    const fieldName = `${channel}_${category.key.replace(/([A-Z])/g, '_$1').toLowerCase()}`;
                                    return (
                                        <div key={channel} className="flex items-center justify-between space-x-2">
                                            <Label htmlFor={fieldName} className="capitalize">{channel}</Label>
                                            <Switch
                                                id={fieldName}
                                                checked={form.data[fieldName as keyof typeof form.data] as boolean}
                                                onCheckedChange={() => handleToggle(fieldName)}
                                                disabled={form.processing}
                                            />
                                        </div>
                                    );
                                })}
                            </div>
                        </div>
                    ))}
                </div>

                {form.recentlySuccessful && (
                    <p className="mt-4 text-sm text-muted-foreground">Preferences saved.</p>
                )}
            </CardContent>
        </Card>
    );
}
