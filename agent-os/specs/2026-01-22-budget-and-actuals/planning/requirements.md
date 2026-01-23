# Spec Requirements: Budget and Actuals

## Initial Description

Extend the existing hour-based tracking system to support cost-based budgeting, actuals tracking, and profitability analysis.

**Current State (Already Implemented):**
- Hour tracking: budget_hours/actual_hours on Projects, WorkOrders, Tasks
- TimeEntry model: Timer functionality, is_billable flag, date tracking
- Automatic hour aggregation: Hours roll up the hierarchy (Task > WorkOrder > Project)
- Time Reports: By User, By Project, Actual vs Estimated (hours only)

**What Needs to be Built:**
1. Hourly rates on users/teams: Store and manage hourly rates
2. Cost fields on WorkOrders/Projects: Budget and actual cost tracking
3. Cost calculation from time entries: Convert hours to costs using rates
4. Budget vs Actuals views: Cost-based comparison views
5. Margin calculations: Calculate and display profitability margins
6. Profitability reports: Reports showing profitability by project, client, or team member

## Requirements Discussion

### First Round Questions

**Q1:** Rate Structures - How should hourly rates be structured?
**Answer:** Rates per user with project-specific rate overrides option.

**Q2:** Internal vs Billing Rates - Do we need both internal cost rates and billing rates?
**Answer:** Both internal cost rates AND billing rates (for margin calculations).

**Q3:** Budget Types - What types of budgets need to be supported?
**Answer:** Support THREE budget types: fixed-price, time-and-materials, AND monthly subscriptions.

**Q4:** Budget Entry Method - How should budgets be entered?
**Answer:** Both options for budget entry - enter budgets as dollar amounts directly OR calculate from estimated hours x rate.

**Q5:** Cost Calculation Timing - When should costs be calculated and how should rate changes be handled?
**Answer:** Calculate costs at time entry creation using user's rate at that moment. Rate changes don't retroactively change historical costs (snapshot approach).

**Q6:** Non-billable Time Handling - How should non-billable time be treated in cost calculations?
**Answer:** Non-billable time still counts toward actual costs but flagged separately in reports.

**Q7:** Profitability Report Scope - What dimensions of profitability reporting are needed?
**Answer:** All profitability views needed: Project, Work Order, Team Member, AND Client/Party profitability.

**Q8:** Utilization Metrics - Should utilization metrics be included?
**Answer:** Include utilization metrics (billable hours vs total capacity) in the same reports.

### Existing Code to Reference

**Similar Features Identified:**
- Feature: TimeEntry model - Path: `app/Models/TimeEntry.php`
- Feature: Time Reports - Path: `resources/js/pages/reports/` (existing time reports to extend)
- Feature: Hour aggregation logic - Path: existing budget_hours/actual_hours fields on Project, WorkOrder, Task models
- Components to potentially reuse: Existing report layouts and data visualization patterns

### Follow-up Questions

No follow-up questions needed - the user's answers are comprehensive and address all key decision points.

## Visual Assets

### Files Provided:
No visual assets provided.

### Visual Insights:
N/A

## Requirements Summary

### Functional Requirements

**Rate Management:**
- Store hourly rates per user (team-scoped)
- Support both internal cost rate and billing rate per user
- Allow project-specific rate overrides for users
- Rates captured at time of entry creation (snapshot approach)

**Budget Management:**
- Support three budget types on WorkOrders/Projects:
  - Fixed-price: Set total budget amount
  - Time-and-materials: Budget based on estimated hours x rates
  - Monthly subscription: Recurring budget amounts
- Allow direct dollar amount entry for budgets
- Allow calculated budgets (estimated hours x rate)
- Store budget_cost alongside existing budget_hours

**Cost Tracking:**
- Calculate cost when TimeEntry is created using user's rate at that moment
- Store cost_rate and calculated_cost on TimeEntry records
- Non-billable time contributes to actual costs (flagged separately)
- Costs aggregate up hierarchy: Task > WorkOrder > Project

**Profitability Analysis:**
- Margin calculations: Revenue (billing rate x hours) - Cost (internal rate x hours)
- Profitability percentage calculations
- Budget vs Actuals comparison (both hours and costs)

**Reporting:**
- Project profitability view
- Work Order profitability view
- Team Member profitability view
- Client/Party profitability view
- Utilization metrics: billable hours vs total capacity
- Separate tracking of billable vs non-billable costs in reports

### Reusability Opportunities

- Extend existing TimeEntry model with rate/cost fields
- Extend existing Project, WorkOrder models with budget_cost, actual_cost fields
- Build on existing Time Reports page structure for profitability reports
- Reuse existing hour aggregation patterns for cost aggregation
- Leverage existing report layouts and UI components

### Scope Boundaries

**In Scope:**
- User rate management (internal and billing rates)
- Project-specific rate overrides
- Three budget types (fixed-price, T&M, subscription)
- Cost calculation and storage on time entries
- Cost aggregation up work hierarchy
- Budget vs Actuals views (cost-based)
- Profitability reports by Project, Work Order, Team Member, and Client
- Utilization metrics in reports
- Non-billable cost tracking (flagged separately)

**Out of Scope:**
- Invoice generation
- Expense tracking beyond labor costs
- Currency conversion
- Integration with accounting software
- Multi-currency support
- Approval workflows for budgets

### Technical Considerations

- Database migrations needed for new rate and cost fields
- Rate snapshot approach means storing rate values on TimeEntry at creation
- Consider using observers or events for cost calculation on TimeEntry creation
- Aggregation logic should mirror existing hour aggregation patterns
- Reports should extend existing report infrastructure
- Budget type field (enum) on WorkOrder/Project models
- Consider caching for profitability calculations on larger datasets
