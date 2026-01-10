import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { PlaybookTab, PlaybookTabsProps } from '@/types/playbooks';

export function PlaybookTabs({ activeTab, onTabChange, counts }: PlaybookTabsProps) {
    const tabs: Array<{ key: PlaybookTab; label: string }> = [
        { key: 'all', label: 'All Playbooks' },
        { key: 'sop', label: 'SOPs' },
        { key: 'checklist', label: 'Checklists' },
        { key: 'template', label: 'Templates' },
        { key: 'acceptance_criteria', label: 'Acceptance Criteria' },
    ];

    return (
        <div className="flex flex-wrap gap-2">
            {tabs.map((tab) => (
                <Button
                    key={tab.key}
                    variant={activeTab === tab.key ? 'default' : 'outline'}
                    size="sm"
                    onClick={() => onTabChange(tab.key)}
                    className="gap-2"
                >
                    {tab.label}
                    <Badge
                        variant={activeTab === tab.key ? 'secondary' : 'outline'}
                        className="ml-1"
                    >
                        {counts[tab.key]}
                    </Badge>
                </Button>
            ))}
        </div>
    );
}
