import type { InboxTabsProps } from '@/types/inbox';
import { FileText, CheckCircle, Flag, MessageSquare, Inbox } from 'lucide-react';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Badge } from '@/components/ui/badge';

export function InboxTabs({ currentTab, counts, onTabChange }: InboxTabsProps) {
    const tabs = [
        { value: 'all' as const, label: 'All', icon: Inbox },
        { value: 'agent_drafts' as const, label: 'Agent Drafts', icon: FileText },
        { value: 'approvals' as const, label: 'Approvals', icon: CheckCircle },
        { value: 'flagged' as const, label: 'Flagged', icon: Flag },
        { value: 'mentions' as const, label: 'Mentions', icon: MessageSquare },
    ];

    return (
        <div className="border-b border-sidebar-border/70 dark:border-sidebar-border px-6">
            <Tabs value={currentTab} onValueChange={onTabChange}>
                <TabsList className="bg-transparent border-0 p-0 h-auto">
                    {tabs.map((tab) => {
                        const Icon = tab.icon;
                        return (
                            <TabsTrigger
                                key={tab.value}
                                value={tab.value}
                                className="flex items-center gap-2 rounded-none border-b-2 border-transparent data-[state=active]:border-primary data-[state=active]:bg-transparent shadow-none"
                            >
                                <Icon className="w-4 h-4" />
                                <span>{tab.label}</span>
                                {counts[tab.value] > 0 && (
                                    <Badge variant="secondary" className="ml-1 text-xs">
                                        {counts[tab.value]}
                                    </Badge>
                                )}
                            </TabsTrigger>
                        );
                    })}
                </TabsList>
            </Tabs>
        </div>
    );
}
