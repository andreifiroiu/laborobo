import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

/**
 * Budget type values matching the BudgetType enum on the backend.
 */
export type BudgetType = 'fixed_price' | 'time_and_materials' | 'monthly_subscription';

/**
 * Budget type option with label for display.
 */
export interface BudgetTypeOption {
    value: BudgetType;
    label: string;
    description: string;
}

/**
 * Available budget type options.
 */
export const BUDGET_TYPE_OPTIONS: BudgetTypeOption[] = [
    {
        value: 'fixed_price',
        label: 'Fixed Price',
        description: 'A set dollar amount for the entire scope',
    },
    {
        value: 'time_and_materials',
        label: 'Time & Materials',
        description: 'Billed based on hours worked at hourly rates',
    },
    {
        value: 'monthly_subscription',
        label: 'Monthly Subscription',
        description: 'Recurring monthly budget allocation',
    },
];

interface BudgetTypeSelectProps {
    /** Currently selected budget type */
    value: BudgetType | undefined;
    /** Callback when budget type changes */
    onChange: (value: BudgetType | undefined) => void;
    /** Optional placeholder text */
    placeholder?: string;
    /** Whether the select is disabled */
    disabled?: boolean;
    /** Additional className for the trigger */
    className?: string;
    /** Optional id for the select trigger */
    id?: string;
    /** Whether the field has an error */
    hasError?: boolean;
}

/**
 * Select component for choosing budget type.
 * Options: Fixed Price, Time & Materials, Monthly Subscription.
 * Uses Radix UI Select primitive.
 */
export function BudgetTypeSelect({
    value,
    onChange,
    placeholder = 'Select budget type',
    disabled = false,
    className,
    id,
    hasError = false,
}: BudgetTypeSelectProps) {
    const handleValueChange = (newValue: string) => {
        // Handle clearing the value
        if (newValue === '__clear__') {
            onChange(undefined);
        } else {
            onChange(newValue as BudgetType);
        }
    };

    return (
        <Select
            value={value ?? ''}
            onValueChange={handleValueChange}
            disabled={disabled}
        >
            <SelectTrigger
                id={id}
                className={className}
                aria-invalid={hasError}
            >
                <SelectValue placeholder={placeholder} />
            </SelectTrigger>
            <SelectContent>
                {BUDGET_TYPE_OPTIONS.map((option) => (
                    <SelectItem
                        key={option.value}
                        value={option.value}
                    >
                        <div className="flex flex-col">
                            <span>{option.label}</span>
                        </div>
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}
