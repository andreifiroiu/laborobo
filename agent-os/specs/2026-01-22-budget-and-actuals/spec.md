# Specification: Budget and Actuals

## Goal
Extend the existing hour-based tracking system to support cost-based budgeting, actuals tracking, and profitability analysis, enabling teams to measure financial performance across projects, work orders, team members, and clients.

## User Stories
- As a project manager, I want to set budgets in dollar amounts and track actual costs so that I can monitor project profitability
- As a team lead, I want to configure hourly rates for team members (both internal cost and billing rates) so that costs and revenue are calculated accurately
- As an executive, I want to view profitability reports by project, client, and team member so that I can make informed business decisions

## Specific Requirements

**User Rate Management**
- Create a `user_rates` table to store hourly rates per user, scoped to team
- Store both `internal_rate` (cost) and `billing_rate` (revenue) as decimal(10,2) fields
- Support project-specific rate overrides via a `project_user_rates` pivot table
- Rates should have an `effective_date` field to allow rate changes without affecting historical data
- When retrieving rates, always check for project-specific override first, then fall back to team default

**Budget Type Support**
- Add `budget_type` enum field to WorkOrder and Project models with values: `fixed_price`, `time_and_materials`, `monthly_subscription`
- Add `budget_cost` decimal(12,2) field alongside existing `budget_hours` on Project and WorkOrder
- Add `actual_cost` decimal(12,2) field alongside existing `actual_hours` on Project and WorkOrder
- For time-and-materials projects, budget_cost can be calculated from budget_hours x billing_rate
- For fixed-price projects, budget_cost is entered directly as a dollar amount

**Cost Calculation on Time Entries**
- Add `cost_rate`, `billing_rate`, `calculated_cost`, and `calculated_revenue` fields to TimeEntry model
- Use a Laravel model observer to snapshot rates when TimeEntry is created
- Fetch user's applicable rate at entry creation time (check project override, then team default)
- Rate changes do not retroactively update existing time entries (snapshot approach)
- Non-billable entries (`is_billable = false`) still calculate cost but set revenue to zero

**Cost Aggregation**
- Mirror existing hour aggregation pattern: TimeEntry -> Task -> WorkOrder -> Project
- Add `recalculateActualCost()` method to Task model that sums TimeEntry calculated_cost
- Add `recalculateActualCost()` method to WorkOrder that sums Task actual_cost and bubbles up
- Add `recalculateActualCost()` method to Project that sums from WorkOrder actual_cost
- Trigger cost recalculation alongside existing `recalculateActualHours()` calls

**Profitability Calculations**
- Margin = Revenue (sum of calculated_revenue) - Cost (sum of calculated_cost)
- Margin Percentage = (Margin / Revenue) x 100 (handle division by zero)
- Budget Variance = Budget Cost - Actual Cost (positive means under budget)
- Utilization = Billable Hours / Total Hours Logged (per user, per period)

**Profitability Reports Controller**
- Create `ProfitabilityReportsController` following existing `TimeReportsController` patterns
- Implement endpoints: `byProject`, `byWorkOrder`, `byTeamMember`, `byClient`
- Each endpoint returns: budget_cost, actual_cost, revenue, margin, margin_percent, utilization
- Support date range filtering consistent with existing time reports
- Include breakdown of billable vs non-billable costs in response data

**Profitability Reports UI**
- Create new page at `/reports/profitability` using existing Time Reports page as template
- Implement tabs: By Project, By Work Order, By Team Member, By Client
- Display table with columns: Name, Budget, Actual Cost, Revenue, Margin, Margin %, Utilization
- Use color coding for margin (green for positive, red for negative, yellow for low margin)
- Include summary cards at top showing totals across all items in current view

**Rate Configuration UI**
- Add "Rates" section to team settings page at `/account/settings/rates`
- Display table of team members with editable internal_rate and billing_rate fields
- Include effective_date picker for rate changes
- Add project-level rate override configuration on project settings panel
- Validate rates are positive numbers with max 2 decimal places

**Budget Entry UI**
- Add budget fields to WorkOrder and Project create/edit forms
- Include budget_type dropdown (Fixed Price, Time & Materials, Monthly Subscription)
- For Fixed Price: show budget_cost input field
- For T&M: show budget_hours field with calculated budget_cost display
- For Subscription: show monthly_budget field and billing_period selector

## Visual Design
No visual assets provided.

## Existing Code to Leverage

**TimeEntry model (`app/Models/TimeEntry.php`)**
- Contains existing `is_billable` flag, `hours` tracking, and timer functionality
- Has `stopTimer()` method that triggers `recalculateActualHours()` on parent Task
- Use this pattern for triggering cost recalculation alongside hours
- Add new cost/rate fields to existing `$fillable` and `$casts` arrays

**Hour aggregation pattern (Task, WorkOrder, Project models)**
- Task has `recalculateActualHours()` that sums from TimeEntry and calls parent
- WorkOrder has `recalculateActualHours()` that sums from Tasks and calls parent Project
- Project has `recalculateActualHours()` that sums from Tasks via WorkOrders
- Replicate this exact pattern for `recalculateActualCost()` methods

**TimeReportsController (`app/Http/Controllers/Reports/TimeReportsController.php`)**
- Provides existing report structure with date filtering and team scoping
- Has methods for by-user, by-project, and actual-vs-estimated views
- Use same patterns for ProfitabilityReportsController endpoints
- Reuse date range filtering approach and JSON response structure

**Time Reports UI (`resources/js/pages/reports/time/index.tsx`)**
- Tab-based layout with By User, By Project, Actual vs Estimated views
- Date range filter component with Apply Filters button
- Tree view component for hierarchical project data display
- Variance color coding functions (`getVarianceColor`, `getVarianceBgColor`)
- Empty state components when no data available

**Party model (`app/Models/Party.php`)**
- Links to Projects via `hasMany` relationship
- Has `forTeam` scope for team-based filtering
- Use this relationship for client-level profitability aggregation

## Out of Scope
- Invoice generation or billing workflows
- Expense tracking beyond labor costs (materials, travel, etc.)
- Multi-currency support or currency conversion
- Integration with external accounting software (QuickBooks, Xero)
- Budget approval workflows or authorization gates
- Forecasting or predictive analytics
- Resource allocation optimization
- Historical rate changes affecting past time entries (rates are snapshotted)
- Automated budget alerts or notifications
- PDF/Excel export of profitability reports (can be added later)
