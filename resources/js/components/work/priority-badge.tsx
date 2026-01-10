interface PriorityBadgeProps {
    priority: 'low' | 'medium' | 'high' | 'urgent';
}

const priorityColors: Record<string, string> = {
    low: 'text-slate-500 dark:text-slate-400',
    medium: 'text-amber-600 dark:text-amber-500',
    high: 'text-orange-600 dark:text-orange-500',
    urgent: 'text-red-600 dark:text-red-500',
};

export function PriorityBadge({ priority }: PriorityBadgeProps) {
    return (
        <span className={`text-xs font-medium uppercase ${priorityColors[priority]}`}>
            {priority}
        </span>
    );
}
