import { Badge } from '@/components/ui/badge';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { cn } from '@/lib/utils';
import type { RaciRole } from '@/types/work';

interface RaciBadgeProps {
    role: RaciRole;
    showTooltip?: boolean;
    className?: string;
}

const raciConfig: Record<RaciRole, { label: string; fullName: string; colorClasses: string }> = {
    accountable: {
        label: 'A',
        fullName: 'Accountable',
        colorClasses: 'bg-violet-100 text-violet-700 border-violet-200 dark:bg-violet-950/30 dark:text-violet-400 dark:border-violet-900',
    },
    responsible: {
        label: 'R',
        fullName: 'Responsible',
        colorClasses: 'bg-indigo-100 text-indigo-700 border-indigo-200 dark:bg-indigo-950/30 dark:text-indigo-400 dark:border-indigo-900',
    },
    consulted: {
        label: 'C',
        fullName: 'Consulted',
        colorClasses: 'bg-amber-100 text-amber-700 border-amber-200 dark:bg-amber-950/30 dark:text-amber-400 dark:border-amber-900',
    },
    informed: {
        label: 'I',
        fullName: 'Informed',
        colorClasses: 'bg-slate-100 text-slate-500 border-slate-200 dark:bg-slate-800 dark:text-slate-500 dark:border-slate-700',
    },
};

// Define prominence order for sorting (higher = more prominent)
const raciProminenceOrder: Record<RaciRole, number> = {
    accountable: 4,
    responsible: 3,
    consulted: 2,
    informed: 1,
};

export function RaciBadge({ role, showTooltip = true, className }: RaciBadgeProps) {
    const config = raciConfig[role];

    const badge = (
        <Badge
            variant="outline"
            data-testid="raci-badge"
            className={cn(
                'text-xs font-semibold border px-1.5 py-0',
                config.colorClasses,
                className
            )}
        >
            {config.label}
        </Badge>
    );

    if (!showTooltip) {
        return badge;
    }

    return (
        <Tooltip>
            <TooltipTrigger asChild>
                {badge}
            </TooltipTrigger>
            <TooltipContent>
                <span>{config.fullName}</span>
            </TooltipContent>
        </Tooltip>
    );
}

interface RaciBadgeGroupProps {
    roles: RaciRole[];
    showTooltip?: boolean;
    className?: string;
}

export function RaciBadgeGroup({ roles, showTooltip = true, className }: RaciBadgeGroupProps) {
    if (!roles || roles.length === 0) {
        return null;
    }

    // Sort roles by prominence (highest first)
    const sortedRoles = [...roles].sort((a, b) => raciProminenceOrder[b] - raciProminenceOrder[a]);

    return (
        <div className={cn('flex items-center gap-1', className)}>
            {sortedRoles.map((role) => (
                <RaciBadge key={role} role={role} showTooltip={showTooltip} />
            ))}
        </div>
    );
}

// Utility function to sort roles by prominence
export function sortRolesByProminence(roles: RaciRole[]): RaciRole[] {
    return [...roles].sort((a, b) => raciProminenceOrder[b] - raciProminenceOrder[a]);
}

// Get visual prominence class based on highest RACI role
export function getProminenceClass(roles: RaciRole[]): string {
    if (!roles || roles.length === 0) {
        return '';
    }

    const highestRole = sortRolesByProminence(roles)[0];

    switch (highestRole) {
        case 'accountable':
            return 'border-l-violet-500 dark:border-l-violet-600';
        case 'responsible':
            return 'border-l-indigo-500 dark:border-l-indigo-600';
        case 'consulted':
            return 'border-l-amber-500 dark:border-l-amber-600';
        case 'informed':
            return 'border-l-slate-300 dark:border-l-slate-600 opacity-75';
        default:
            return '';
    }
}
