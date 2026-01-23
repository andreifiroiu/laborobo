import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi, beforeEach } from 'vitest';
import { BudgetFieldsGroup } from '../budget-fields-group';
import { BudgetTypeSelect, BUDGET_TYPE_OPTIONS } from '../budget-type-select';

describe('BudgetTypeSelect', () => {
    it('renders the select trigger with placeholder', () => {
        const onChange = vi.fn();

        render(
            <BudgetTypeSelect
                value={undefined}
                onChange={onChange}
            />
        );

        // Check that the trigger is rendered
        const trigger = screen.getByRole('combobox');
        expect(trigger).toBeInTheDocument();
        expect(screen.getByText('Select budget type')).toBeInTheDocument();
    });

    it('displays the selected value when provided', () => {
        const onChange = vi.fn();

        render(
            <BudgetTypeSelect
                value="fixed_price"
                onChange={onChange}
            />
        );

        // Check that the selected value is displayed
        expect(screen.getByText('Fixed Price')).toBeInTheDocument();
    });

    it('exports all budget type options', () => {
        // Verify that all budget type options are defined correctly
        expect(BUDGET_TYPE_OPTIONS).toHaveLength(3);

        const values = BUDGET_TYPE_OPTIONS.map(opt => opt.value);
        expect(values).toContain('fixed_price');
        expect(values).toContain('time_and_materials');
        expect(values).toContain('monthly_subscription');

        const labels = BUDGET_TYPE_OPTIONS.map(opt => opt.label);
        expect(labels).toContain('Fixed Price');
        expect(labels).toContain('Time & Materials');
        expect(labels).toContain('Monthly Subscription');
    });
});

describe('BudgetFieldsGroup', () => {
    const defaultProps = {
        budgetType: undefined as 'fixed_price' | 'time_and_materials' | 'monthly_subscription' | undefined,
        budgetCost: '',
        budgetHours: '',
        onBudgetTypeChange: vi.fn(),
        onBudgetCostChange: vi.fn(),
        onBudgetHoursChange: vi.fn(),
    };

    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('shows budget_cost input when Fixed Price is selected', () => {
        render(
            <BudgetFieldsGroup
                {...defaultProps}
                budgetType="fixed_price"
            />
        );

        // Budget cost field should be visible
        expect(screen.getByLabelText(/budget cost/i)).toBeInTheDocument();

        // Budget hours field should NOT be visible for fixed price
        expect(screen.queryByLabelText(/budget hours/i)).not.toBeInTheDocument();
    });

    it('shows budget_hours with calculated cost display for T&M projects', () => {
        const averageBillingRate = 125;

        render(
            <BudgetFieldsGroup
                {...defaultProps}
                budgetType="time_and_materials"
                budgetHours="40"
                averageBillingRate={averageBillingRate}
            />
        );

        // Budget hours field should be visible
        expect(screen.getByLabelText(/budget hours/i)).toBeInTheDocument();

        // Budget cost field should NOT be visible for T&M
        expect(screen.queryByLabelText(/budget cost/i)).not.toBeInTheDocument();

        // Estimated cost display should show calculated value
        // 40 hours x $125/hr = $5,000
        expect(screen.getByText('Estimated Budget Cost')).toBeInTheDocument();
        expect(screen.getByText('$5,000.00')).toBeInTheDocument();
    });

    it('shows budget_cost input for Monthly Subscription', () => {
        render(
            <BudgetFieldsGroup
                {...defaultProps}
                budgetType="monthly_subscription"
            />
        );

        // Monthly Budget field should be visible
        expect(screen.getByLabelText(/monthly budget/i)).toBeInTheDocument();

        // Budget hours field should NOT be visible for subscription
        expect(screen.queryByLabelText(/budget hours/i)).not.toBeInTheDocument();
    });

    it('conditionally renders fields based on budget_type changes', () => {
        const onBudgetTypeChange = vi.fn();

        const { rerender } = render(
            <BudgetFieldsGroup
                {...defaultProps}
                onBudgetTypeChange={onBudgetTypeChange}
            />
        );

        // Initially no conditional fields should be shown (only the select)
        expect(screen.queryByLabelText(/budget cost/i)).not.toBeInTheDocument();
        expect(screen.queryByLabelText(/budget hours/i)).not.toBeInTheDocument();
        expect(screen.queryByLabelText(/monthly budget/i)).not.toBeInTheDocument();

        // Re-render with fixed_price to simulate budget type change
        rerender(
            <BudgetFieldsGroup
                {...defaultProps}
                budgetType="fixed_price"
                onBudgetTypeChange={onBudgetTypeChange}
            />
        );

        // Now budget cost field should be visible
        expect(screen.getByLabelText(/budget cost/i)).toBeInTheDocument();

        // Re-render with time_and_materials
        rerender(
            <BudgetFieldsGroup
                {...defaultProps}
                budgetType="time_and_materials"
                onBudgetTypeChange={onBudgetTypeChange}
            />
        );

        // Now budget hours field should be visible, not budget cost
        expect(screen.getByLabelText(/budget hours/i)).toBeInTheDocument();
        expect(screen.queryByLabelText(/budget cost/i)).not.toBeInTheDocument();
    });

    it('handles budget cost input changes', async () => {
        const user = userEvent.setup();
        const onBudgetCostChange = vi.fn();

        render(
            <BudgetFieldsGroup
                {...defaultProps}
                budgetType="fixed_price"
                onBudgetCostChange={onBudgetCostChange}
            />
        );

        const input = screen.getByLabelText(/budget cost/i);
        await user.type(input, '5000');

        expect(onBudgetCostChange).toHaveBeenCalled();
    });

    it('handles budget hours input changes', async () => {
        const user = userEvent.setup();
        const onBudgetHoursChange = vi.fn();

        render(
            <BudgetFieldsGroup
                {...defaultProps}
                budgetType="time_and_materials"
                onBudgetHoursChange={onBudgetHoursChange}
            />
        );

        const input = screen.getByLabelText(/budget hours/i);
        await user.type(input, '40');

        expect(onBudgetHoursChange).toHaveBeenCalled();
    });

    it('shows warning when T&M has no billing rate configured', () => {
        render(
            <BudgetFieldsGroup
                {...defaultProps}
                budgetType="time_and_materials"
                budgetHours="40"
                averageBillingRate={0}
            />
        );

        // Should show a warning about setting up billing rates
        expect(screen.getByText(/set up team member billing rates/i)).toBeInTheDocument();
    });

    it('does not show calculated cost value when billing rate is zero', () => {
        render(
            <BudgetFieldsGroup
                {...defaultProps}
                budgetType="time_and_materials"
                budgetHours="40"
                averageBillingRate={0}
            />
        );

        // The calculated cost value (like "$5,000.00") should not be shown
        // The "Estimated Budget Cost" label in the calculation box should not appear
        expect(screen.queryByText('Estimated Budget Cost')).not.toBeInTheDocument();
        // But the warning text should appear (which contains different wording)
        expect(screen.getByText(/set up team member billing rates/i)).toBeInTheDocument();
    });
});
