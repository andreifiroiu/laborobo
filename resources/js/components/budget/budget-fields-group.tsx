import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/input-error';
import { BudgetTypeSelect, type BudgetType } from './budget-type-select';

interface BudgetFieldsGroupProps {
    /** Currently selected budget type */
    budgetType: BudgetType | undefined;
    /** Budget cost value (for Fixed Price) */
    budgetCost: string;
    /** Budget hours value (for T&M and Subscription) */
    budgetHours: string;
    /** Callback when budget type changes */
    onBudgetTypeChange: (value: BudgetType | undefined) => void;
    /** Callback when budget cost changes */
    onBudgetCostChange: (value: string) => void;
    /** Callback when budget hours changes */
    onBudgetHoursChange: (value: string) => void;
    /** Average billing rate for calculating estimated cost (for T&M) */
    averageBillingRate?: number;
    /** Error messages for validation */
    errors?: {
        budget_type?: string;
        budget_cost?: string;
        budget_hours?: string;
    };
    /** Whether the fields are disabled */
    disabled?: boolean;
}

/**
 * Formats a number as USD currency.
 */
function formatCurrency(value: number): string {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(value);
}

/**
 * Budget fields group component for Project and WorkOrder forms.
 * Conditionally renders budget_cost or budget_hours inputs based on budget_type selection.
 *
 * - Fixed Price: Shows budget_cost input
 * - Time & Materials: Shows budget_hours input with calculated cost display
 * - Monthly Subscription: Shows budget_cost input (monthly amount)
 */
export function BudgetFieldsGroup({
    budgetType,
    budgetCost,
    budgetHours,
    onBudgetTypeChange,
    onBudgetCostChange,
    onBudgetHoursChange,
    averageBillingRate = 0,
    errors = {},
    disabled = false,
}: BudgetFieldsGroupProps) {
    // Calculate estimated budget cost for T&M projects
    const estimatedBudgetCost =
        budgetType === 'time_and_materials' && budgetHours && averageBillingRate
            ? parseFloat(budgetHours) * averageBillingRate
            : null;

    // Determine which fields to show based on budget type
    const showBudgetCost = budgetType === 'fixed_price' || budgetType === 'monthly_subscription';
    const showBudgetHours = budgetType === 'time_and_materials';

    return (
        <div className="grid gap-4">
            {/* Budget Type Select */}
            <div className="grid gap-2">
                <Label htmlFor="budget_type">Budget Type</Label>
                <BudgetTypeSelect
                    id="budget_type"
                    value={budgetType}
                    onChange={onBudgetTypeChange}
                    disabled={disabled}
                    hasError={!!errors.budget_type}
                />
                <InputError message={errors.budget_type} />
                <p className="text-xs text-muted-foreground">
                    Choose how this project or work order is budgeted.
                </p>
            </div>

            {/* Budget Cost Field (Fixed Price or Monthly Subscription) */}
            {showBudgetCost && (
                <div className="grid gap-2">
                    <Label htmlFor="budget_cost">
                        {budgetType === 'monthly_subscription' ? 'Monthly Budget' : 'Budget Cost'}
                    </Label>
                    <div className="relative">
                        <span className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground">
                            $
                        </span>
                        <Input
                            id="budget_cost"
                            type="number"
                            step="0.01"
                            min="0"
                            value={budgetCost}
                            onChange={(e) => onBudgetCostChange(e.target.value)}
                            placeholder="0.00"
                            className="pl-7"
                            disabled={disabled}
                            aria-describedby={errors.budget_cost ? 'budget_cost_error' : undefined}
                            aria-invalid={!!errors.budget_cost}
                        />
                    </div>
                    <InputError message={errors.budget_cost} id="budget_cost_error" />
                    <p className="text-xs text-muted-foreground">
                        {budgetType === 'monthly_subscription'
                            ? 'The recurring monthly budget amount.'
                            : 'The total budget for this fixed-price scope.'}
                    </p>
                </div>
            )}

            {/* Budget Hours Field (Time & Materials) */}
            {showBudgetHours && (
                <div className="grid gap-2">
                    <Label htmlFor="budget_hours">Budget Hours</Label>
                    <div className="relative">
                        <Input
                            id="budget_hours"
                            type="number"
                            step="0.5"
                            min="0"
                            value={budgetHours}
                            onChange={(e) => onBudgetHoursChange(e.target.value)}
                            placeholder="0"
                            disabled={disabled}
                            aria-describedby={errors.budget_hours ? 'budget_hours_error' : undefined}
                            aria-invalid={!!errors.budget_hours}
                        />
                        <span className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground text-sm">
                            hours
                        </span>
                    </div>
                    <InputError message={errors.budget_hours} id="budget_hours_error" />
                    <p className="text-xs text-muted-foreground">
                        The estimated hours for this time-and-materials scope.
                    </p>

                    {/* Calculated Budget Display for T&M */}
                    {estimatedBudgetCost !== null && estimatedBudgetCost > 0 && (
                        <div className="mt-2 p-3 bg-muted rounded-md">
                            <div className="flex items-center justify-between text-sm">
                                <span className="text-muted-foreground">Estimated Budget Cost</span>
                                <span className="font-medium">{formatCurrency(estimatedBudgetCost)}</span>
                            </div>
                            <p className="text-xs text-muted-foreground mt-1">
                                {budgetHours} hours x {formatCurrency(averageBillingRate)}/hr
                            </p>
                        </div>
                    )}

                    {/* Info message when no billing rate is set */}
                    {budgetType === 'time_and_materials' && averageBillingRate === 0 && (
                        <div className="mt-2 p-3 bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-900/50 rounded-md">
                            <p className="text-xs text-amber-700 dark:text-amber-400">
                                Set up team member billing rates to see estimated budget costs.
                            </p>
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}
