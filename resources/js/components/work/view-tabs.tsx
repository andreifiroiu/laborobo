import type { WorkView } from '@/types/work';
import { Archive, Calendar, Kanban, LayoutGrid, User } from 'lucide-react';
import { useEffect, useRef } from 'react';

interface ViewTabsProps {
    currentView: WorkView;
    onViewChange: (view: WorkView) => void;
}

const views: Array<{
    value: WorkView;
    label: string;
    icon: React.ComponentType<{ className?: string }>;
}> = [
    { value: 'all_projects', label: 'All Projects', icon: LayoutGrid },
    { value: 'my_work', label: 'My Work', icon: User },
    { value: 'by_status', label: 'By Status', icon: Kanban },
    { value: 'calendar', label: 'Calendar', icon: Calendar },
    { value: 'archive', label: 'Archive', icon: Archive },
];

export function ViewTabs({ currentView, onViewChange }: ViewTabsProps) {
    const activeTabRef = useRef<HTMLButtonElement>(null);

    // The strip scrolls horizontally on phones and hides its scrollbar, and the
    // view is restored from the user's saved preference, so a later tab can be
    // active while sitting off-screen with nothing to hint at it.
    useEffect(() => {
        activeTabRef.current?.scrollIntoView({
            block: 'nearest',
            inline: 'nearest',
        });
    }, [currentView]);

    return (
        <div className="border-b border-sidebar-border/70 dark:border-sidebar-border">
            <div className="flex gap-1 overflow-x-auto px-4 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                {views.map((view) => {
                    const Icon = view.icon;
                    const isActive = currentView === view.value;

                    return (
                        <button
                            key={view.value}
                            ref={isActive ? activeTabRef : undefined}
                            onClick={() => onViewChange(view.value)}
                            className={`flex shrink-0 items-center gap-2 border-b-2 px-3 py-3 text-sm font-medium whitespace-nowrap transition-colors sm:px-4 ${
                                isActive
                                    ? 'border-primary text-primary'
                                    : 'border-transparent text-muted-foreground hover:text-foreground'
                            } `}
                        >
                            <Icon className="h-4 w-4" />
                            {view.label}
                        </button>
                    );
                })}
            </div>
        </div>
    );
}
