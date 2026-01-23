# Change Order Flow

## Raw Idea

The user wants to implement a Change Order Flow feature that includes:

- Change request creation from existing work orders
- Approval workflow for scope changes
- Automatic budget adjustment
- Change history tracking

## Context

This feature is part of the Financial Tracking section in the product roadmap (item #18), following the completed Budget and Actuals feature. The change order flow is essential for:

1. Making scope additions visible and billable
2. Protecting margins by requiring approval for scope changes
3. Keeping budgets and expectations aligned between team and clients
4. Supporting the product mission of turning "profitability blindness" into "data-driven" decisions

## Related Existing Features

- Work Orders with budget_type, budget_cost, actual_cost fields
- InboxItem approval workflow system
- StatusTransition audit trail
- WorkflowTransitionService for state machine transitions
- CostCalculationService for rate-based cost calculations
- RACI roles (accountable, responsible, reviewer) on work items
