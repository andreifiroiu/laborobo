import { cn } from '@/lib/utils';
import type { MyWorkMetrics as MyWorkMetricsType, RaciRole } from '@/types/work';

interface MyWorkMetricsProps {
    metrics: MyWorkMetricsType;
    onMetricClick?: (filterType: 'accountable' | 'responsible' | 'awaiting_review' | 'assigned_tasks') => void;
    className?: string;
}

export function MyWorkMetrics({ metrics, onMetricClick, className }: MyWorkMetricsProps) {
    return (
        <div className={cn('grid grid-cols-2 lg:grid-cols-4 gap-4', className)}>
            <MetricCard
                label="Accountable"
                value={metrics.accountableCount}
                description="items where you're accountable"
                color="violet"
                onClick={() => onMetricClick?.('accountable')}
            />
            <MetricCard
                label="Responsible"
                value={metrics.responsibleCount}
                description="items where you're responsible"
                color="indigo"
                onClick={() => onMetricClick?.('responsible')}
            />
            <MetricCard
                label="Awaiting Review"
                value={metrics.awaitingReviewCount}
                description="items awaiting your review"
                color="amber"
                onClick={() => onMetricClick?.('awaiting_review')}
            />
            <MetricCard
                label="Assigned Tasks"
                value={metrics.assignedTasksCount}
                description="tasks assigned to you"
                color="emerald"
                onClick={() => onMetricClick?.('assigned_tasks')}
            />
        </div>
    );
}

interface MetricCardProps {
    label: string;
    value: number;
    description: string;
    color: 'violet' | 'indigo' | 'amber' | 'emerald';
    onClick?: () => void;
}

function MetricCard({ label, value, description, color, onClick }: MetricCardProps) {
    const colorClasses = {
        violet: 'bg-violet-50 dark:bg-violet-950/20 text-violet-700 dark:text-violet-400 hover:bg-violet-100 dark:hover:bg-violet-950/30',
        indigo: 'bg-indigo-50 dark:bg-indigo-950/20 text-indigo-700 dark:text-indigo-400 hover:bg-indigo-100 dark:hover:bg-indigo-950/30',
        amber: 'bg-amber-50 dark:bg-amber-950/20 text-amber-700 dark:text-amber-400 hover:bg-amber-100 dark:hover:bg-amber-950/30',
        emerald: 'bg-emerald-50 dark:bg-emerald-950/20 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-100 dark:hover:bg-emerald-950/30',
    };

    const Component = onClick ? 'button' : 'div';

    return (
        <Component
            onClick={onClick}
            className={cn(
                'p-4 rounded-xl transition-colors',
                colorClasses[color],
                onClick && 'cursor-pointer focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2'
            )}
        >
            <div className="text-2xl font-bold mb-1">{value}</div>
            <div className="text-sm font-medium">{label}</div>
            <div className="text-xs opacity-70 mt-0.5">{description}</div>
        </Component>
    );
}

// Helper to convert metric filter to RACI role filter
export function metricFilterToRaciRole(
    filterType: 'accountable' | 'responsible' | 'awaiting_review' | 'assigned_tasks'
): RaciRole | null {
    switch (filterType) {
        case 'accountable':
            return 'accountable';
        case 'responsible':
            return 'responsible';
        default:
            return null;
    }
}
