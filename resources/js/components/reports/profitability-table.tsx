import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    formatCurrency,
    formatPercent,
    getMarginColor,
    getMarginBgColor,
} from '@/lib/profitability-utils';
import { cn } from '@/lib/utils';

export interface ProfitabilityRow {
    id: number | string;
    name: string;
    budget: number;
    actualCost: number;
    revenue: number;
    margin: number;
    marginPercent: number;
    utilization?: number;
}

interface ProfitabilityTableProps {
    data: ProfitabilityRow[];
    showUtilization?: boolean;
    onRowClick?: (row: ProfitabilityRow) => void;
    emptyMessage?: string;
}

export function ProfitabilityTable({
    data,
    showUtilization = false,
    onRowClick,
    emptyMessage = 'No data available',
}: ProfitabilityTableProps) {
    if (data.length === 0) {
        return (
            <div className="flex items-center justify-center py-12">
                <p className="text-muted-foreground">{emptyMessage}</p>
            </div>
        );
    }

    return (
        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead>Name</TableHead>
                    <TableHead className="text-right">Budget</TableHead>
                    <TableHead className="text-right">Actual Cost</TableHead>
                    <TableHead className="text-right">Revenue</TableHead>
                    <TableHead className="text-right">Margin</TableHead>
                    <TableHead className="text-right">Margin %</TableHead>
                    {showUtilization && (
                        <TableHead className="text-right">Utilization</TableHead>
                    )}
                </TableRow>
            </TableHeader>
            <TableBody>
                {data.map((row) => (
                    <TableRow
                        key={row.id}
                        onClick={() => onRowClick?.(row)}
                        className={cn(onRowClick && 'cursor-pointer')}
                    >
                        <TableCell className="font-medium">{row.name}</TableCell>
                        <TableCell className="text-right font-mono">
                            {formatCurrency(row.budget)}
                        </TableCell>
                        <TableCell className="text-right font-mono">
                            {formatCurrency(row.actualCost)}
                        </TableCell>
                        <TableCell className="text-right font-mono">
                            {formatCurrency(row.revenue)}
                        </TableCell>
                        <TableCell
                            className={cn(
                                'text-right font-mono',
                                getMarginColor(row.marginPercent)
                            )}
                        >
                            {formatCurrency(row.margin)}
                        </TableCell>
                        <TableCell className="text-right">
                            <span
                                className={cn(
                                    'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                                    getMarginBgColor(row.marginPercent),
                                    getMarginColor(row.marginPercent)
                                )}
                            >
                                {formatPercent(row.marginPercent)}
                            </span>
                        </TableCell>
                        {showUtilization && (
                            <TableCell className="text-right font-mono">
                                {row.utilization !== undefined
                                    ? formatPercent(row.utilization)
                                    : '-'}
                            </TableCell>
                        )}
                    </TableRow>
                ))}
            </TableBody>
        </Table>
    );
}
