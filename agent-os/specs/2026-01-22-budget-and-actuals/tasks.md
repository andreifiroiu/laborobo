# Task Breakdown: Budget and Actuals

## Overview
Total Tasks: 47

This feature extends the existing hour-based tracking system to support cost-based budgeting, actuals tracking, and profitability analysis. The implementation follows the existing patterns for hour aggregation and time reports.

## Task List

### Database Layer

#### Task Group 1: User Rates Data Models
**Dependencies:** None

- [x] 1.0 Complete user rates database layer
  - [x] 1.1 Write 4-6 focused tests for UserRate model functionality
    - Test rate retrieval with team scoping
    - Test effective_date filtering (get rate valid at a specific date)
    - Test project-specific rate override precedence
    - Test validation of rate fields (positive decimals)
  - [x] 1.2 Create `user_rates` migration
    - Fields: `id`, `team_id`, `user_id`, `internal_rate` (decimal 10,2), `billing_rate` (decimal 10,2), `effective_date` (date), timestamps
    - Add foreign keys: `team_id` -> teams, `user_id` -> users
    - Add indexes on: `team_id`, `user_id`, `effective_date`
    - Add unique constraint on: `team_id`, `user_id`, `effective_date`
  - [x] 1.3 Create `project_user_rates` migration for project-specific overrides
    - Fields: `id`, `project_id`, `user_id`, `internal_rate` (decimal 10,2), `billing_rate` (decimal 10,2), `effective_date` (date), timestamps
    - Add foreign keys: `project_id` -> projects, `user_id` -> users
    - Add indexes on: `project_id`, `user_id`, `effective_date`
    - Add unique constraint on: `project_id`, `user_id`, `effective_date`
  - [x] 1.4 Create UserRate model with validations
    - Fields in `$fillable`: `team_id`, `user_id`, `internal_rate`, `billing_rate`, `effective_date`
    - Casts: `internal_rate` -> decimal:2, `billing_rate` -> decimal:2, `effective_date` -> date
    - Relationships: belongsTo Team, belongsTo User
    - Scopes: `forTeam()`, `forUser()`, `effectiveAt()`
  - [x] 1.5 Create ProjectUserRate model with validations
    - Fields in `$fillable`: `project_id`, `user_id`, `internal_rate`, `billing_rate`, `effective_date`
    - Casts: `internal_rate` -> decimal:2, `billing_rate` -> decimal:2, `effective_date` -> date
    - Relationships: belongsTo Project, belongsTo User
    - Scopes: `forProject()`, `forUser()`, `effectiveAt()`
  - [x] 1.6 Add rate relationships to User model
    - `rates()`: hasMany UserRate
    - `projectRates()`: hasMany ProjectUserRate
  - [x] 1.7 Ensure user rates tests pass
    - Run ONLY the 4-6 tests written in 1.1
    - Verify migrations run successfully

**Acceptance Criteria:**
- The 4-6 tests written in 1.1 pass
- UserRate and ProjectUserRate models have proper validations
- Migrations run successfully with correct indexes and constraints
- Rate retrieval respects effective_date ordering

---

#### Task Group 2: Budget and Cost Fields on Entities
**Dependencies:** Task Group 1

- [x] 2.0 Complete budget and cost fields on existing models
  - [x] 2.1 Write 4-6 focused tests for budget/cost fields
    - Test budget_type enum values on Project and WorkOrder
    - Test budget_cost and actual_cost field storage
    - Test cost fields on TimeEntry (cost_rate, billing_rate, calculated_cost, calculated_revenue)
    - Test actual_cost field on Task model
  - [x] 2.2 Create BudgetType enum
    - Values: `fixed_price`, `time_and_materials`, `monthly_subscription`
    - Location: `app/Enums/BudgetType.php`
  - [x] 2.3 Create migration to add budget/cost fields to projects table
    - Add `budget_type` enum field (nullable, default null)
    - Add `budget_cost` decimal(12,2) nullable
    - Add `actual_cost` decimal(12,2) nullable default 0
    - Add `actual_revenue` decimal(12,2) nullable default 0
  - [x] 2.4 Create migration to add budget/cost fields to work_orders table
    - Add `budget_type` enum field (nullable, default null)
    - Add `budget_cost` decimal(12,2) nullable
    - Add `actual_cost` decimal(12,2) nullable default 0
    - Add `actual_revenue` decimal(12,2) nullable default 0
  - [x] 2.5 Create migration to add cost fields to tasks table
    - Add `actual_cost` decimal(12,2) nullable default 0
    - Add `actual_revenue` decimal(12,2) nullable default 0
  - [x] 2.6 Create migration to add rate/cost fields to time_entries table
    - Add `cost_rate` decimal(10,2) nullable
    - Add `billing_rate` decimal(10,2) nullable
    - Add `calculated_cost` decimal(12,2) nullable default 0
    - Add `calculated_revenue` decimal(12,2) nullable default 0
  - [x] 2.7 Update Project model
    - Add fields to `$fillable`: `budget_type`, `budget_cost`, `actual_cost`, `actual_revenue`
    - Add casts: `budget_type` -> BudgetType, `budget_cost` -> decimal:2, `actual_cost` -> decimal:2, `actual_revenue` -> decimal:2
  - [x] 2.8 Update WorkOrder model
    - Add fields to `$fillable`: `budget_type`, `budget_cost`, `actual_cost`, `actual_revenue`
    - Add casts: `budget_type` -> BudgetType, `budget_cost` -> decimal:2, `actual_cost` -> decimal:2, `actual_revenue` -> decimal:2
  - [x] 2.9 Update Task model
    - Add fields to `$fillable`: `actual_cost`, `actual_revenue`
    - Add casts: `actual_cost` -> decimal:2, `actual_revenue` -> decimal:2
  - [x] 2.10 Update TimeEntry model
    - Add fields to `$fillable`: `cost_rate`, `billing_rate`, `calculated_cost`, `calculated_revenue`
    - Add casts: `cost_rate` -> decimal:2, `billing_rate` -> decimal:2, `calculated_cost` -> decimal:2, `calculated_revenue` -> decimal:2
  - [x] 2.11 Ensure budget/cost field tests pass
    - Run ONLY the 4-6 tests written in 2.1
    - Verify migrations run successfully

**Acceptance Criteria:**
- The 4-6 tests written in 2.1 pass
- All new fields are properly typed and cast
- Migrations run successfully
- BudgetType enum works correctly

---

### Service Layer

#### Task Group 3: Cost Calculation Service
**Dependencies:** Task Groups 1, 2

- [x] 3.0 Complete cost calculation service
  - [x] 3.1 Write 4-6 focused tests for cost calculation
    - Test rate lookup priority (project override first, then team default)
    - Test cost calculation for billable time entry
    - Test cost calculation for non-billable time entry (cost calculated, revenue = 0)
    - Test rate snapshot on time entry creation
  - [x] 3.2 Create CostCalculationService
    - Location: `app/Services/CostCalculationService.php`
    - Method: `getRateForUser(User $user, ?Project $project, Carbon $date): array` returns `['internal_rate' => x, 'billing_rate' => y]`
    - Method: `calculateCost(TimeEntry $entry): void` calculates and sets cost/revenue fields
    - Rate lookup: check ProjectUserRate first, fall back to UserRate
    - For non-billable entries: set `calculated_revenue` to 0
  - [x] 3.3 Create TimeEntryObserver
    - Location: `app/Observers/TimeEntryObserver.php`
    - Hook into `creating` event to snapshot rates and calculate cost
    - Hook into `updating` event to recalculate if hours change
    - Inject CostCalculationService
  - [x] 3.4 Register TimeEntryObserver in AppServiceProvider
    - Add observer registration in boot method
  - [x] 3.5 Ensure cost calculation tests pass
    - Run ONLY the 4-6 tests written in 3.1
    - Verify rate lookup and cost calculation work correctly

**Acceptance Criteria:**
- The 4-6 tests written in 3.1 pass
- Rates are correctly looked up with project override priority
- Costs are calculated automatically on TimeEntry creation
- Non-billable entries have zero revenue

---

#### Task Group 4: Cost Aggregation Methods
**Dependencies:** Task Group 3

- [x] 4.0 Complete cost aggregation methods
  - [x] 4.1 Write 4-6 focused tests for cost aggregation
    - Test Task `recalculateActualCost()` sums from TimeEntry
    - Test WorkOrder `recalculateActualCost()` sums from Tasks and bubbles up
    - Test Project `recalculateActualCost()` sums from WorkOrders
    - Test aggregation triggered alongside `recalculateActualHours()`
  - [x] 4.2 Add `recalculateActualCost()` method to Task model
    - Sum `calculated_cost` and `calculated_revenue` from timeEntries
    - Update `actual_cost` and `actual_revenue` fields
    - Call `workOrder->recalculateActualCost()` to bubble up
  - [x] 4.3 Add `recalculateActualCost()` method to WorkOrder model
    - Sum `actual_cost` and `actual_revenue` from tasks
    - Update `actual_cost` and `actual_revenue` fields
    - Call `project->recalculateActualCost()` to bubble up
  - [x] 4.4 Add `recalculateActualCost()` method to Project model
    - Sum `actual_cost` and `actual_revenue` from tasks via workOrders
    - Update `actual_cost` and `actual_revenue` fields
  - [x] 4.5 Update existing `recalculateActualHours()` calls to also call `recalculateActualCost()`
    - Update Task model `recalculateActualHours()` method
    - Update WorkOrder model `recalculateActualHours()` method
    - Update Project model `recalculateActualHours()` method
  - [x] 4.6 Update TimeEntry `stopTimer()` method
    - Ensure cost recalculation is triggered when timer stops
  - [x] 4.7 Ensure cost aggregation tests pass
    - Run ONLY the 4-6 tests written in 4.1
    - Verify aggregation bubbles up correctly

**Acceptance Criteria:**
- The 4-6 tests written in 4.1 pass
- Costs aggregate correctly up the hierarchy
- Aggregation triggers automatically with hour aggregation
- Both cost and revenue aggregate correctly

---

### API Layer

#### Task Group 5: Rate Management API
**Dependencies:** Task Groups 1-4

- [x] 5.0 Complete rate management API
  - [x] 5.1 Write 4-6 focused tests for rate management endpoints
    - Test listing team member rates
    - Test creating/updating user rate
    - Test creating project-specific rate override
    - Test validation of rate values (positive numbers, max 2 decimal places)
  - [x] 5.2 Create UserRateController
    - Location: `app/Http/Controllers/Settings/UserRateController.php`
    - Method: `index(Request $request)` - list rates for all team members
    - Method: `store(Request $request)` - create new rate with effective_date
    - Method: `update(Request $request, UserRate $rate)` - update existing rate
  - [x] 5.3 Create ProjectUserRateController
    - Location: `app/Http/Controllers/ProjectUserRateController.php`
    - Method: `index(Request $request, Project $project)` - list project-specific rates
    - Method: `store(Request $request, Project $project)` - create project rate override
    - Method: `update(Request $request, Project $project, ProjectUserRate $rate)` - update override
    - Method: `destroy(Request $request, Project $project, ProjectUserRate $rate)` - remove override
  - [x] 5.4 Create form request classes for validation
    - `StoreUserRateRequest`: validate internal_rate, billing_rate, effective_date
    - `StoreProjectUserRateRequest`: validate same fields plus user_id
  - [x] 5.5 Add routes for rate management
    - Add to `routes/web.php` under settings group: `/account/settings/rates`
    - Add project rate routes: `/projects/{project}/rates`
  - [x] 5.6 Ensure rate management API tests pass
    - Run ONLY the 4-6 tests written in 5.1

**Acceptance Criteria:**
- The 4-6 tests written in 5.1 pass
- Rate CRUD operations work correctly
- Proper validation enforced
- Routes follow RESTful conventions

---

#### Task Group 6: Profitability Reports API
**Dependencies:** Task Groups 1-5

- [x] 6.0 Complete profitability reports API
  - [x] 6.1 Write 4-6 focused tests for profitability endpoints
    - Test byProject endpoint returns correct profitability metrics
    - Test byTeamMember endpoint with utilization calculation
    - Test byClient endpoint aggregates across projects
    - Test date range filtering
  - [x] 6.2 Create ProfitabilityReportsController
    - Location: `app/Http/Controllers/Reports/ProfitabilityReportsController.php`
    - Follow existing TimeReportsController patterns
    - Method: `index(Request $request)` - render Inertia page with initial data
  - [x] 6.3 Implement `byProject()` endpoint
    - Return: budget_cost, actual_cost, revenue, margin, margin_percent, utilization
    - Include billable vs non-billable cost breakdown
    - Support date range filtering
  - [x] 6.4 Implement `byWorkOrder()` endpoint
    - Return same metrics as byProject
    - Group by work order within projects
  - [x] 6.5 Implement `byTeamMember()` endpoint
    - Return: total_hours, billable_hours, cost, revenue, margin, utilization
    - Utilization = billable_hours / total_hours
    - Support date range filtering
  - [x] 6.6 Implement `byClient()` endpoint
    - Aggregate profitability across all projects for each Party (client)
    - Use Party model relationship to projects
    - Return same profitability metrics
  - [x] 6.7 Add helper methods for profitability calculations
    - `calculateMargin(revenue, cost)`: revenue - cost
    - `calculateMarginPercent(revenue, cost)`: handle division by zero
    - `calculateUtilization(billable, total)`: handle division by zero
  - [x] 6.8 Add routes for profitability reports
    - Base route: `/reports/profitability`
    - JSON endpoints: `by-project`, `by-work-order`, `by-team-member`, `by-client`
  - [x] 6.9 Ensure profitability reports API tests pass
    - Run ONLY the 4-6 tests written in 6.1

**Acceptance Criteria:**
- The 4-6 tests written in 6.1 pass
- All four profitability views return correct data
- Date filtering works consistently
- Margin and utilization calculations are accurate

---

### Frontend Layer

#### Task Group 7: Rate Configuration UI
**Dependencies:** Task Groups 5-6

- [x] 7.0 Complete rate configuration UI
  - [x] 7.1 Write 3-5 focused tests for rate configuration components
    - Test rate table renders team members with rates
    - Test rate editing form validation
    - Test effective_date picker functionality
  - [x] 7.2 Create RatesSettingsPage component
    - Location: `resources/js/pages/account/settings/rates.tsx`
    - Use existing settings page layout pattern
    - Display table of team members with rates
  - [x] 7.3 Create TeamMemberRateTable component
    - Location: `resources/js/components/rates/team-member-rate-table.tsx`
    - Columns: Member Name, Internal Rate, Billing Rate, Effective Date, Actions
    - Editable inline or via modal
  - [x] 7.4 Create RateEditForm component
    - Location: `resources/js/components/rates/rate-edit-form.tsx`
    - Fields: internal_rate (currency input), billing_rate (currency input), effective_date (date picker)
    - Validation: positive numbers, max 2 decimal places
  - [x] 7.5 Create ProjectRateOverridePanel component
    - Location: `resources/js/components/rates/project-rate-override-panel.tsx`
    - Display project-specific rate overrides
    - Add/edit/remove override functionality
    - For use on project settings page
  - [x] 7.6 Add navigation link to rates settings
    - Add "Rates" item to team settings sidebar/navigation
  - [x] 7.7 Ensure rate configuration UI tests pass
    - Run ONLY the 3-5 tests written in 7.1

**Acceptance Criteria:**
- The 3-5 tests written in 7.1 pass
- Rate table displays correctly
- Rate editing works with validation
- Effective date changes are handled properly

---

#### Task Group 8: Budget Entry UI
**Dependencies:** Task Group 7

- [x] 8.0 Complete budget entry UI on forms
  - [x] 8.1 Write 3-5 focused tests for budget entry components
    - Test budget_type dropdown changes form fields
    - Test fixed price shows budget_cost input
    - Test T&M shows budget_hours with calculated cost display
  - [x] 8.2 Create BudgetFieldsGroup component
    - Location: `resources/js/components/budget/budget-fields-group.tsx`
    - Props: budget_type, budget_cost, budget_hours, onChange handlers
    - Conditional rendering based on budget_type selection
  - [x] 8.3 Create BudgetTypeSelect component
    - Location: `resources/js/components/budget/budget-type-select.tsx`
    - Options: Fixed Price, Time & Materials, Monthly Subscription
    - Use Radix UI Select primitive
  - [x] 8.4 Update Project create/edit forms
    - Add BudgetFieldsGroup to project form
    - Location: update existing project form components
  - [x] 8.5 Update WorkOrder create/edit forms
    - Add BudgetFieldsGroup to work order form
    - Location: update existing work order form components
  - [x] 8.6 Add calculated budget display for T&M projects
    - Show: budget_hours x average_billing_rate = estimated_budget_cost
    - Display as read-only calculated value
  - [x] 8.7 Ensure budget entry UI tests pass
    - Run ONLY the 3-5 tests written in 8.1

**Acceptance Criteria:**
- The 3-5 tests written in 8.1 pass
- Budget type selection changes visible fields
- Fixed price and T&M modes work correctly
- Budget values save correctly

---

#### Task Group 9: Profitability Reports UI
**Dependencies:** Task Groups 6-8

- [x] 9.0 Complete profitability reports page
  - [x] 9.1 Write 3-5 focused tests for profitability reports components
    - Test tab switching between views
    - Test profitability table renders correct columns
    - Test margin color coding (green positive, red negative)
  - [x] 9.2 Create ProfitabilityReportsPage component
    - Location: `resources/js/pages/reports/profitability/index.tsx`
    - Use existing Time Reports page as template
    - Tabs: By Project, By Work Order, By Team Member, By Client
    - Date range filter component
  - [x] 9.3 Create ProfitabilitySummaryCards component
    - Location: `resources/js/components/reports/profitability-summary-cards.tsx`
    - Cards: Total Budget, Total Actual Cost, Total Revenue, Total Margin, Avg Margin %
    - Display totals for current view
  - [x] 9.4 Create ProfitabilityTable component
    - Location: `resources/js/components/reports/profitability-table.tsx`
    - Columns: Name, Budget, Actual Cost, Revenue, Margin, Margin %, Utilization
    - Reusable across all tabs with different data
  - [x] 9.5 Create margin color coding utilities
    - Location: `resources/js/lib/profitability-utils.ts`
    - Functions: `getMarginColor(marginPercent)`, `getMarginBgColor(marginPercent)`
    - Green for positive (> 20%), yellow for low (0-20%), red for negative
  - [x] 9.6 Implement ByProjectTab component
    - Fetch data from `/reports/profitability/by-project`
    - Display project profitability with drill-down to work orders
  - [x] 9.7 Implement ByWorkOrderTab component
    - Fetch data from `/reports/profitability/by-work-order`
    - Display work order level profitability
  - [x] 9.8 Implement ByTeamMemberTab component
    - Fetch data from `/reports/profitability/by-team-member`
    - Display team member profitability with utilization
  - [x] 9.9 Implement ByClientTab component
    - Fetch data from `/reports/profitability/by-client`
    - Display client/party level profitability
  - [x] 9.10 Add empty states for each tab
    - Reuse pattern from Time Reports empty states
    - Display appropriate message when no data
  - [x] 9.11 Add navigation link to profitability reports
    - Add "Profitability" item to reports navigation
    - Update sidebar or reports index page
  - [x] 9.12 Ensure profitability reports UI tests pass
    - Run ONLY the 3-5 tests written in 9.1

**Acceptance Criteria:**
- The 3-5 tests written in 9.1 pass
- All four tabs render correctly
- Margin color coding works
- Date filtering updates all views
- Summary cards show accurate totals

---

### Testing

#### Task Group 10: Test Review and Gap Analysis
**Dependencies:** Task Groups 1-9

- [x] 10.0 Review existing tests and fill critical gaps only
  - [x] 10.1 Review tests from Task Groups 1-9
    - Review database layer tests (Groups 1-2): 12 tests
    - Review service layer tests (Groups 3-4): 11 tests
    - Review API layer tests (Groups 5-6): 12 tests
    - Review UI component tests (Groups 7-9): 31 tests
    - Total existing tests: 66 tests
  - [x] 10.2 Analyze test coverage gaps for budget and actuals feature only
    - Identified critical end-to-end workflows lacking coverage
    - Focused ONLY on gaps related to this spec's feature requirements
    - Prioritized integration points between service and API layers
  - [x] 10.3 Write up to 10 additional strategic tests maximum
    - Added integration test: TimeEntry creation triggers cost calculation and aggregation
    - Added integration test: Rate snapshot on time entry preserves historical rate
    - Added API test: Profitability report with mixed billable/non-billable entries
    - Added end-to-end test: Budget vs Actuals comparison accuracy
    - Added integration test: Project-specific rate override correctly applies
    - Added integration test: Profitability by team member shows correct utilization
    - Added integration test: Multiple time entries aggregate correctly across work orders
    - Added integration test: Zero rate handling does not break cost calculation
    - 8 additional tests written (within limit of 10)
  - [x] 10.4 Run feature-specific tests only
    - Ran ONLY tests related to budget and actuals feature
    - Total: 74 tests (43 backend + 31 frontend)
    - All critical workflows pass
    - Did NOT run entire application test suite

**Acceptance Criteria:**
- All feature-specific tests pass (74 tests total)
- Critical user workflows for budget/actuals feature are covered
- 8 additional tests added when filling gaps (within limit of 10)
- Testing focused exclusively on this spec's feature requirements

---

## Execution Order

Recommended implementation sequence:

1. **Database Layer - User Rates** (Task Group 1)
   - Foundation for all cost calculations

2. **Database Layer - Budget/Cost Fields** (Task Group 2)
   - Extends existing models with required fields

3. **Service Layer - Cost Calculation** (Task Group 3)
   - Core business logic for cost computation

4. **Service Layer - Cost Aggregation** (Task Group 4)
   - Mirrors existing hour aggregation pattern

5. **API Layer - Rate Management** (Task Group 5)
   - Enables rate configuration

6. **API Layer - Profitability Reports** (Task Group 6)
   - Provides data for reports UI

7. **Frontend - Rate Configuration** (Task Group 7)
   - Team settings for rates

8. **Frontend - Budget Entry** (Task Group 8)
   - Project/WorkOrder form updates

9. **Frontend - Profitability Reports** (Task Group 9)
   - Main reporting interface

10. **Testing - Gap Analysis** (Task Group 10)
    - Final verification and coverage

---

## Key Technical Notes

### Rate Lookup Priority
1. Check `project_user_rates` for project-specific override with `effective_date <= entry_date`
2. Fall back to `user_rates` for team default with `effective_date <= entry_date`
3. Always use the most recent rate by `effective_date` that is not in the future

### Cost Calculation Formula
- `calculated_cost = hours * cost_rate`
- `calculated_revenue = is_billable ? hours * billing_rate : 0`

### Aggregation Pattern (mirrors existing hour aggregation)
```
TimeEntry.calculated_cost -> Task.actual_cost -> WorkOrder.actual_cost -> Project.actual_cost
TimeEntry.calculated_revenue -> Task.actual_revenue -> WorkOrder.actual_revenue -> Project.actual_revenue
```

### Profitability Formulas
- `margin = revenue - cost`
- `margin_percent = revenue > 0 ? (margin / revenue) * 100 : 0`
- `utilization = total_hours > 0 ? (billable_hours / total_hours) * 100 : 0`
- `budget_variance = budget_cost - actual_cost` (positive = under budget)

### Existing Code to Reference
- `app/Models/TimeEntry.php` - add cost fields, update stopTimer()
- `app/Models/Task.php` - add recalculateActualCost(), mirror recalculateActualHours()
- `app/Models/WorkOrder.php` - add recalculateActualCost()
- `app/Models/Project.php` - add recalculateActualCost()
- `app/Http/Controllers/Reports/TimeReportsController.php` - pattern for ProfitabilityReportsController
- `resources/js/pages/reports/time/index.tsx` - template for profitability reports UI
