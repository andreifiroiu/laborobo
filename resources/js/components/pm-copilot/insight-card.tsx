import { Badge } from '@/components/ui/badge';
import { AlertTriangle, Clock, Users, TrendingUp, Lightbulb, ChevronRight } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { InsightCardProps, InsightType, InsightSeverity } from '@/types/pm-copilot.d';

const insightIcons: Record<InsightType, typeof AlertTriangle> = {
    overdue: Clock,
    bottleneck: AlertTriangle,
    resource: Users,
    scope_creep: TrendingUp,
};

const severityStyles: Record<InsightSeverity, { card: string; icon: string; badge: string }> = {
    low: {
        card: 'border-blue-200 bg-blue-50/50 dark:border-blue-900 dark:bg-blue-950/20',
        icon: 'bg-blue-100 text-blue-700 dark:bg-blue-950/50 dark:text-blue-300',
        badge: 'bg-blue-100 text-blue-700 border-blue-200 dark:bg-blue-950/30 dark:text-blue-400 dark:border-blue-900',
    },
    medium: {
        card: 'border-amber-200 bg-amber-50/50 dark:border-amber-900 dark:bg-amber-950/20',
        icon: 'bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300',
        badge: 'bg-amber-100 text-amber-700 border-amber-200 dark:bg-amber-950/30 dark:text-amber-400 dark:border-amber-900',
    },
    high: {
        card: 'border-orange-200 bg-orange-50/50 dark:border-orange-900 dark:bg-orange-950/20',
        icon: 'bg-orange-100 text-orange-700 dark:bg-orange-950/50 dark:text-orange-300',
        badge: 'bg-orange-100 text-orange-700 border-orange-200 dark:bg-orange-950/30 dark:text-orange-400 dark:border-orange-900',
    },
    critical: {
        card: 'border-red-200 bg-red-50/50 dark:border-red-900 dark:bg-red-950/20',
        icon: 'bg-red-100 text-red-700 dark:bg-red-950/50 dark:text-red-300',
        badge: 'bg-red-100 text-red-700 border-red-200 dark:bg-red-950/30 dark:text-red-400 dark:border-red-900',
    },
};

/**
 * Card displaying a single project insight from PM Copilot.
 * Shows insight type icon, severity indicator, title, description,
 * affected items count, and actionable suggestion.
 */
export function InsightCard({ insight, onClick }: InsightCardProps) {
    const Icon = insightIcons[insight.type];
    const styles = severityStyles[insight.severity];
    const isClickable = !!onClick;

    return (
        <div
            data-testid="insight-card"
            className={cn(
                'rounded-lg border p-4 transition-colors',
                styles.card,
                isClickable && 'cursor-pointer hover:border-primary/50'
            )}
            onClick={onClick}
            role={isClickable ? 'button' : undefined}
            tabIndex={isClickable ? 0 : undefined}
            onKeyDown={
                isClickable
                    ? (e) => {
                          if (e.key === 'Enter' || e.key === ' ') {
                              e.preventDefault();
                              onClick();
                          }
                      }
                    : undefined
            }
        >
            {/* Header */}
            <div className="flex items-start gap-3">
                <div className={cn('flex h-9 w-9 shrink-0 items-center justify-center rounded-full', styles.icon)}>
                    <Icon className="h-5 w-5" />
                </div>

                <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-2 mb-1">
                        <h4 className="font-medium text-sm">{insight.title}</h4>
                        <Badge variant="outline" className={cn('text-xs capitalize', styles.badge)}>
                            {insight.severity}
                        </Badge>
                    </div>

                    <p className="text-sm text-muted-foreground">{insight.description}</p>

                    {/* Affected Items */}
                    {insight.affectedItems.length > 0 && (
                        <p className="text-xs text-muted-foreground mt-2">
                            {insight.affectedItems.length} {insight.affectedItems.length === 1 ? 'item' : 'items'} affected
                        </p>
                    )}

                    {/* Suggestion */}
                    <div className="mt-3 flex items-start gap-2 rounded-md bg-background/50 p-2">
                        <Lightbulb className="h-4 w-4 shrink-0 text-muted-foreground mt-0.5" />
                        <p className="text-sm">{insight.suggestion}</p>
                    </div>
                </div>

                {isClickable && (
                    <ChevronRight className="h-5 w-5 shrink-0 text-muted-foreground" />
                )}
            </div>
        </div>
    );
}
