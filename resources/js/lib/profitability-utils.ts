/**
 * Profitability utility functions for color coding and formatting.
 *
 * Margin thresholds:
 * - Green (positive): > 20% margin
 * - Yellow (low): 0-20% margin
 * - Red (negative): < 0% margin
 */

/**
 * Returns the text color class for a given margin percentage.
 * @param marginPercent - The margin percentage value
 * @returns Tailwind text color class
 */
export function getMarginColor(marginPercent: number): string {
    if (marginPercent > 20) {
        return 'text-green-600 dark:text-green-400';
    }
    if (marginPercent >= 0) {
        return 'text-yellow-600 dark:text-yellow-400';
    }
    return 'text-red-600 dark:text-red-400';
}

/**
 * Returns the background color class for a given margin percentage.
 * @param marginPercent - The margin percentage value
 * @returns Tailwind background color class
 */
export function getMarginBgColor(marginPercent: number): string {
    if (marginPercent > 20) {
        return 'bg-green-100 dark:bg-green-900/20';
    }
    if (marginPercent >= 0) {
        return 'bg-yellow-100 dark:bg-yellow-900/20';
    }
    return 'bg-red-100 dark:bg-red-900/20';
}

/**
 * Formats a currency value for display.
 * @param value - The numeric value to format
 * @param locale - The locale to use for formatting (default: 'en-US')
 * @returns Formatted currency string
 */
export function formatCurrency(value: number, locale = 'en-US'): string {
    return new Intl.NumberFormat(locale, {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(value);
}

/**
 * Formats a percentage value for display.
 * @param value - The percentage value
 * @param decimals - Number of decimal places (default: 1)
 * @returns Formatted percentage string
 */
export function formatPercent(value: number, decimals = 1): string {
    return `${value.toFixed(decimals)}%`;
}

/**
 * Calculates margin from revenue and cost.
 * @param revenue - Total revenue
 * @param cost - Total cost
 * @returns The margin (revenue - cost)
 */
export function calculateMargin(revenue: number, cost: number): number {
    return revenue - cost;
}

/**
 * Calculates margin percentage from revenue and cost.
 * Handles division by zero by returning 0.
 * @param revenue - Total revenue
 * @param cost - Total cost
 * @returns The margin percentage
 */
export function calculateMarginPercent(revenue: number, cost: number): number {
    if (revenue === 0) {
        return 0;
    }
    const margin = revenue - cost;
    return (margin / revenue) * 100;
}

/**
 * Calculates utilization percentage from billable and total hours.
 * Handles division by zero by returning 0.
 * @param billableHours - Total billable hours
 * @param totalHours - Total hours
 * @returns The utilization percentage
 */
export function calculateUtilization(billableHours: number, totalHours: number): number {
    if (totalHours === 0) {
        return 0;
    }
    return (billableHours / totalHours) * 100;
}
