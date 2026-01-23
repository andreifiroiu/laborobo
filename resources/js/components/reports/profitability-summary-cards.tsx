import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    formatCurrency,
    formatPercent,
    getMarginColor,
    getMarginBgColor,
} from '@/lib/profitability-utils';
import { cn } from '@/lib/utils';
import { DollarSign, TrendingUp, TrendingDown, Percent, Target } from 'lucide-react';

export interface ProfitabilitySummary {
    totalBudget: number;
    totalActualCost: number;
    totalRevenue: number;
    totalMargin: number;
    avgMarginPercent: number;
}

interface ProfitabilitySummaryCardsProps {
    summary: ProfitabilitySummary;
}

export function ProfitabilitySummaryCards({ summary }: ProfitabilitySummaryCardsProps) {
    const {
        totalBudget,
        totalActualCost,
        totalRevenue,
        totalMargin,
        avgMarginPercent,
    } = summary;

    const cards = [
        {
            title: 'Total Budget',
            value: formatCurrency(totalBudget),
            icon: Target,
            description: 'Budgeted amount',
        },
        {
            title: 'Total Actual Cost',
            value: formatCurrency(totalActualCost),
            icon: DollarSign,
            description: 'Total costs incurred',
        },
        {
            title: 'Total Revenue',
            value: formatCurrency(totalRevenue),
            icon: TrendingUp,
            description: 'Total billable revenue',
        },
        {
            title: 'Total Margin',
            value: formatCurrency(totalMargin),
            icon: totalMargin >= 0 ? TrendingUp : TrendingDown,
            colorClass: getMarginColor(avgMarginPercent),
            bgClass: getMarginBgColor(avgMarginPercent),
            description: 'Revenue minus cost',
        },
        {
            title: 'Avg Margin %',
            value: formatPercent(avgMarginPercent),
            icon: Percent,
            colorClass: getMarginColor(avgMarginPercent),
            bgClass: getMarginBgColor(avgMarginPercent),
            description: 'Average margin percentage',
        },
    ];

    return (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            {cards.map((card) => (
                <Card key={card.title} className="py-4">
                    <CardHeader className="flex flex-row items-center justify-between pb-2">
                        <CardTitle className="text-sm font-medium text-muted-foreground">
                            {card.title}
                        </CardTitle>
                        <card.icon
                            className={cn(
                                'size-4',
                                card.colorClass || 'text-muted-foreground'
                            )}
                            aria-hidden="true"
                        />
                    </CardHeader>
                    <CardContent className="pt-0">
                        <div
                            className={cn(
                                'text-2xl font-bold',
                                card.colorClass
                            )}
                        >
                            {card.value}
                        </div>
                        <p className="text-xs text-muted-foreground">
                            {card.description}
                        </p>
                    </CardContent>
                </Card>
            ))}
        </div>
    );
}
