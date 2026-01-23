import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Lightbulb, Loader2 } from 'lucide-react';
import { InsightCard } from './insight-card';
import type { ProjectInsightsPanelProps, InsightType } from '@/types/pm-copilot.d';

const insightTypeLabels: Record<InsightType, string> = {
    overdue: 'Overdue Items',
    bottleneck: 'Bottlenecks',
    resource: 'Resource Issues',
    scope_creep: 'Scope Creep Risks',
};

const insightTypePriority: Record<InsightType, number> = {
    bottleneck: 1,
    scope_creep: 2,
    overdue: 3,
    resource: 4,
};

/**
 * Panel displaying project insights from PM Copilot analysis.
 * Insights are grouped by type and show severity indicators.
 */
export function ProjectInsightsPanel({
    insights,
    onInsightClick,
    isLoading = false,
}: ProjectInsightsPanelProps) {
    // Group insights by type
    const groupedInsights = insights.reduce((acc, insight) => {
        if (!acc[insight.type]) {
            acc[insight.type] = [];
        }
        acc[insight.type].push(insight);
        return acc;
    }, {} as Record<InsightType, typeof insights>);

    // Sort groups by priority
    const sortedTypes = Object.keys(groupedInsights).sort(
        (a, b) => insightTypePriority[a as InsightType] - insightTypePriority[b as InsightType]
    ) as InsightType[];

    if (isLoading) {
        return (
            <Card>
                <CardContent className="py-8 text-center">
                    <Loader2 className="mx-auto mb-2 h-8 w-8 animate-spin text-muted-foreground" />
                    <p className="text-muted-foreground">Analyzing project...</p>
                </CardContent>
            </Card>
        );
    }

    if (insights.length === 0) {
        return (
            <Card>
                <CardContent className="py-8 text-center">
                    <Lightbulb className="mx-auto mb-2 h-8 w-8 text-muted-foreground" />
                    <p className="text-muted-foreground">No insights available</p>
                    <p className="text-xs text-muted-foreground mt-1">
                        Project analysis found no issues to report
                    </p>
                </CardContent>
            </Card>
        );
    }

    return (
        <Card>
            <CardHeader className="pb-3">
                <div className="flex items-center gap-2">
                    <div className="flex h-8 w-8 items-center justify-center rounded-full bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300">
                        <Lightbulb className="h-4 w-4" />
                    </div>
                    <div>
                        <CardTitle className="text-base">Project Insights</CardTitle>
                        <p className="text-xs text-muted-foreground">
                            {insights.length} {insights.length === 1 ? 'insight' : 'insights'} found
                        </p>
                    </div>
                </div>
            </CardHeader>
            <CardContent className="space-y-6">
                {sortedTypes.map((type) => (
                    <div key={type}>
                        <h4 className="text-sm font-medium mb-3 flex items-center gap-2">
                            {insightTypeLabels[type]}
                            <span className="text-xs text-muted-foreground font-normal">
                                ({groupedInsights[type].length})
                            </span>
                        </h4>
                        <div className="space-y-3">
                            {groupedInsights[type].map((insight) => (
                                <InsightCard
                                    key={insight.id}
                                    insight={insight}
                                    onClick={onInsightClick ? () => onInsightClick(insight.id) : undefined}
                                />
                            ))}
                        </div>
                    </div>
                ))}
            </CardContent>
        </Card>
    );
}
