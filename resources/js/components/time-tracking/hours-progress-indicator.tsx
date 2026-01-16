import { cn } from '@/lib/utils';

interface HoursProgressIndicatorProps {
    actualHours: number;
    estimatedHours: number;
    className?: string;
}

/**
 * Converts decimal hours to H:MM format.
 * Example: 1.5 -> "1:30"
 */
function formatHoursMinutes(decimalHours: number): string {
    const hours = Math.floor(decimalHours);
    const minutes = Math.round((decimalHours - hours) * 60);
    return `${hours}:${minutes.toString().padStart(2, '0')}`;
}

/**
 * Returns the appropriate color class based on percentage.
 * Green: under 80%
 * Yellow: 80-100%
 * Red: over 100%
 */
function getProgressColor(percentage: number): string {
    if (percentage >= 100) {
        return 'bg-red-500';
    }
    if (percentage >= 80) {
        return 'bg-amber-500';
    }
    return 'bg-emerald-500';
}

/**
 * Returns the appropriate text color class based on percentage.
 */
function getTextColor(percentage: number): string {
    if (percentage >= 100) {
        return 'text-red-600 dark:text-red-400';
    }
    if (percentage >= 80) {
        return 'text-amber-600 dark:text-amber-400';
    }
    return 'text-emerald-600 dark:text-emerald-400';
}

/**
 * Displays actual vs estimated hours with a visual progress bar.
 * Shows both decimal and H:MM time formats with color-coded progress.
 */
export function HoursProgressIndicator({
    actualHours,
    estimatedHours,
    className,
}: HoursProgressIndicatorProps) {
    // Calculate percentage, avoiding division by zero
    const percentage = estimatedHours > 0 ? Math.round((actualHours / estimatedHours) * 100) : 0;

    // Cap the visual progress bar at 100% for display, but show actual percentage in text
    const visualPercentage = Math.min(percentage, 100);

    const progressColor = getProgressColor(percentage);
    const textColor = getTextColor(percentage);

    return (
        <div className={cn('space-y-1.5', className)}>
            {/* Primary format: "X.X / Y.Y hours (ZZ%)" */}
            <div className="flex items-center justify-between text-sm">
                <span className="text-muted-foreground">
                    {actualHours.toFixed(1)} / {estimatedHours.toFixed(1)} hours
                </span>
                <span className={cn('font-medium', textColor)}>({percentage}%)</span>
            </div>

            {/* Progress bar */}
            <div
                className="h-2 w-full overflow-hidden rounded-full bg-muted"
                role="progressbar"
                aria-valuenow={percentage}
                aria-valuemin={0}
                aria-valuemax={100}
                aria-label={`Progress: ${percentage}% of estimated hours used`}
            >
                <div
                    data-testid="hours-progress-bar"
                    className={cn('h-full rounded-full transition-all duration-300', progressColor)}
                    style={{ width: `${visualPercentage}%` }}
                />
            </div>

            {/* Secondary format: "(H:MM / H:MM)" */}
            <div className="text-xs text-muted-foreground">
                ({formatHoursMinutes(actualHours)} / {formatHoursMinutes(estimatedHours)})
            </div>
        </div>
    );
}
