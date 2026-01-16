# Task Breakdown: Time Tracking Integration

## Overview
Total Tasks: 6 Phases, 28 Major Tasks

This feature adds a complete time tracking UI that allows users to log hours against tasks via manual entry or real-time timer, view their time history, and access basic reports comparing actual vs estimated time across projects.

## Task List

### Phase 1: Database & Backend Foundation

#### Task Group 1: Database Schema Updates
**Dependencies:** None

- [x] 1.0 Complete is_billable migration and model updates
  - [x] 1.1 Write 3-4 focused tests for is_billable functionality
    - Test that TimeEntry can be created with is_billable = true
    - Test that TimeEntry can be created with is_billable = false
    - Test that is_billable defaults to true when not specified
    - Test that startTimer() accepts optional is_billable parameter
  - [x] 1.2 Create migration adding `is_billable` boolean column to `time_entries` table
    - File: `database/migrations/2026_01_16_123118_add_is_billable_to_time_entries_table.php`
    - Default value: true
    - Not nullable
  - [x] 1.3 Update TimeEntry model
    - File: `app/Models/TimeEntry.php`
    - Add `is_billable` to `$fillable` array
    - Add `is_billable` to `$casts` as boolean
  - [x] 1.4 Update TimeEntry::startTimer() method
    - Accept optional `bool $isBillable = true` parameter
    - Pass `is_billable` to create() call
  - [x] 1.5 Run migration and verify tests pass
    - Run `php artisan migrate`
    - Run only the 3-4 tests written in 1.1

**Acceptance Criteria:**
- Migration runs successfully
- TimeEntry model accepts and persists is_billable field
- startTimer() respects is_billable parameter
- All 3-4 tests pass

---

#### Task Group 2: Running Timer Scope and Detection
**Dependencies:** Task Group 1

- [x] 2.0 Complete running timer detection infrastructure
  - [x] 2.1 Write 3-4 focused tests for running timer scope
    - Test scopeRunningForUser returns entries with started_at and null stopped_at
    - Test scopeRunningForUser excludes entries with stopped_at set
    - Test scopeRunningForUser filters by user_id correctly
    - Test scopeRunningForUser returns empty when no active timers
  - [x] 2.2 Add `scopeRunningForUser` to TimeEntry model
    - File: `app/Models/TimeEntry.php`
    - Filter: `started_at` not null AND `stopped_at` is null
    - Filter by `user_id` parameter
  - [x] 2.3 Run tests and verify scope works correctly

**Acceptance Criteria:**
- Scope correctly identifies running timers for a specific user
- All 3-4 tests pass

---

#### Task Group 3: API Endpoints
**Dependencies:** Task Group 2

- [x] 3.0 Complete time entry CRUD API endpoints
  - [x] 3.1 Write 6-8 focused tests for API endpoints
    - Test index returns paginated entries for current user
    - Test index filters by date range
    - Test index filters by billable status
    - Test store creates entry with is_billable field
    - Test update modifies hours, date, note, is_billable
    - Test destroy soft deletes and recalculates task hours
    - Test stopById stops a specific timer by ID
    - Test stopById fails for timer not owned by user
  - [x] 3.2 Implement index method in TimeEntryController
    - File: `app/Http/Controllers/Work/TimeEntryController.php`
    - GET `/work/time-entries`
    - Accept filters: date_from, date_to, task_id, billable
    - Paginate with 25 entries per page
    - Eager load task.workOrder.project relationships
    - Return Inertia page with entries data
  - [x] 3.3 Update store method to accept is_billable
    - Add `is_billable` to validation rules (boolean, defaults true)
    - Pass is_billable to TimeEntry::create()
  - [x] 3.4 Implement show method for edit form
    - GET `/work/time-entries/{id}`
    - Return single entry data
    - Authorize user owns entry
  - [x] 3.5 Implement update method
    - PATCH `/work/time-entries/{id}`
    - Accept: hours, date, note, is_billable
    - Recalculate task hours after update
    - Authorize user owns entry
  - [x] 3.6 Implement destroy method
    - DELETE `/work/time-entries/{id}`
    - Soft delete entry
    - Recalculate task hours after delete
    - Authorize user owns entry
  - [x] 3.7 Implement stopById method for header timer
    - POST `/work/time-entries/{id}/stop`
    - Stop specific timer by ID
    - Authorize user owns entry
    - Used by header timer indicator
  - [x] 3.8 Update startTimer method to accept is_billable
    - Add `is_billable` to validation (optional, defaults true)
    - Pass to TimeEntry::startTimer()
  - [x] 3.9 Register routes in routes/work.php
    - File: `routes/work.php`
    - GET `/work/time-entries` -> index
    - GET `/work/time-entries/{timeEntry}` -> show
    - PATCH `/work/time-entries/{timeEntry}` -> update
    - DELETE `/work/time-entries/{timeEntry}` -> destroy
    - POST `/work/time-entries/{timeEntry}/stop` -> stopById
  - [x] 3.10 Run tests and verify all endpoints work

**Acceptance Criteria:**
- All CRUD operations work correctly
- Proper authorization enforced (users can only access own entries)
- Task hours recalculated on create/update/delete
- All 6-8 tests pass

---

### Phase 2: Timer State Management

#### Task Group 4: Inertia Shared Data Provider
**Dependencies:** Task Group 3

- [x] 4.0 Complete active timer shared data provider
  - [x] 4.1 Write 2-3 focused tests for shared data provider
    - Test activeTimer is null when no running timer
    - Test activeTimer contains correct data when timer running
    - Test activeTimer includes task and project information
  - [x] 4.2 Create shared data middleware or service
    - Add to `app/Http/Middleware/HandleInertiaRequests.php` share() method
    - Query for current user's running timer using scopeRunningForUser
    - Use brief caching to avoid repeated queries per request
  - [x] 4.3 Define activeTimer shape in shared data
    - Include: id, taskId, taskTitle, projectName, startedAt, isBillable
    - Return null if no timer running
  - [x] 4.4 Update TypeScript SharedData interface
    - File: `resources/js/types/index.d.ts`
    - Add `activeTimer: ActiveTimer | null` to SharedData
    - Define ActiveTimer interface with required fields
  - [x] 4.5 Test shared data appears in page props

**Acceptance Criteria:**
- activeTimer available in all Inertia page props
- Timer data includes task and project context
- Null returned when no active timer
- All 2-3 tests pass

---

### Phase 3: Frontend Components

#### Task Group 5: Timer Controls Component
**Dependencies:** Task Group 4

- [x] 5.0 Complete timer controls component
  - [x] 5.1 Write 3-4 focused tests for TimerControls component
    - Test renders "Start Timer" when no active timer for task
    - Test renders "Stop Timer" with elapsed time when timer active
    - Test start button calls correct endpoint
    - Test stop button calls correct endpoint
  - [x] 5.2 Create TimerControls React component
    - File: `resources/js/components/time-tracking/timer-controls.tsx`
    - Props: taskId (required), activeTimerForTask (optional ActiveTimer)
    - Use Play and Square icons from lucide-react
  - [x] 5.3 Implement start timer functionality
    - POST to `/work/tasks/{task}/timer/start` via Inertia
    - Include optional is_billable parameter
    - Disable button during request
  - [x] 5.4 Implement stop timer functionality
    - POST to `/work/tasks/{task}/timer/stop` via Inertia
    - Show elapsed time updating every second (setInterval)
    - Format elapsed time as HH:MM:SS
  - [x] 5.5 Add elapsed time display with live updates
    - Calculate from startedAt timestamp
    - Update every second using setInterval
    - Use monospace font for readability
  - [x] 5.6 Run component tests

**Acceptance Criteria:**
- Start/stop buttons render based on timer state
- Elapsed time updates in real-time
- Inertia requests fire correctly
- All 3-4 tests pass

---

#### Task Group 6: Manual Time Entry Form
**Dependencies:** Task Group 4

- [x] 6.0 Complete manual time entry form component
  - [x] 6.1 Write 3-4 focused tests for TimeEntryForm component
    - Test form renders all required fields
    - Test form submits with valid data
    - Test hours validation (0.01 - 24 range)
    - Test form resets after successful submission
  - [x] 6.2 Create TimeEntryForm React component
    - File: `resources/js/components/time-tracking/time-entry-form.tsx`
    - Props: taskId (optional for standalone use), onSuccess callback
  - [x] 6.3 Implement form fields
    - Task selector (searchable dropdown) - only if no taskId prop
    - Hours input (decimal, step 0.25, min 0.01, max 24)
    - Date picker (defaults to today)
    - Note textarea (optional, max 500 chars)
    - Billable toggle (defaults to on)
  - [x] 6.4 Implement client-side validation
    - Hours required, numeric, 0.01-24 range
    - Date required
    - Task required (if no taskId prop)
  - [x] 6.5 Implement form submission
    - POST to `/work/time-entries` via Inertia
    - Show success feedback (toast or inline message)
    - Reset form on success
  - [x] 6.6 Run component tests

**Acceptance Criteria:**
- Form renders with all fields
- Validation prevents invalid submissions
- Successful submission resets form
- Can be used standalone or embedded in task views
- All 3-4 tests pass

---

### Phase 4: Header Integration

#### Task Group 7: Active Timer Indicator
**Dependencies:** Task Groups 5, 6

- [x] 7.0 Complete header timer indicator
  - [x] 7.1 Write 2-3 focused tests for ActiveTimerIndicator
    - Test indicator hidden when no active timer
    - Test indicator shows task name and elapsed time
    - Test stop button in popover stops timer
  - [x] 7.2 Create ActiveTimerIndicator React component
    - File: `resources/js/components/time-tracking/active-timer-indicator.tsx`
    - Access shared props via usePage<SharedData>()
    - Render only when activeTimer is present
  - [x] 7.3 Implement indicator display
    - Show elapsed time (updating every second)
    - Show task name truncated to fit
    - Use indigo accent color
    - Add subtle pulse animation for active state
  - [x] 7.4 Implement popover/dropdown
    - Use Radix UI Popover primitive
    - Show: task name (full), project name, elapsed time
    - Include stop button
    - Stop calls POST `/work/time-entries/{id}/stop`
  - [x] 7.5 Integrate into AppSidebarHeader
    - File: `resources/js/components/app-sidebar-header.tsx`
    - Position between breadcrumbs and right edge
    - Use flex layout with ml-auto for right alignment
  - [x] 7.6 Run component tests

**Acceptance Criteria:**
- Indicator appears when timer is running
- Elapsed time updates in real-time
- Popover shows full timer details
- Stop button works from anywhere in app
- All 2-3 tests pass

---

### Phase 5: Pages and Views

#### Task Group 8: Time Entry History Page
**Dependencies:** Task Groups 5, 6, 7

- [x] 8.0 Complete time entry history page
  - [x] 8.1 Write 3-4 focused tests for time entries page
    - Test page loads with user's time entries
    - Test date range filter works
    - Test edit dialog opens with correct data
    - Test delete confirmation and removal works
  - [x] 8.2 Create time entries index page
    - File: `resources/js/pages/work/time-entries/index.tsx`
    - Route: `/work/time-entries`
    - Use AppLayout with breadcrumbs
  - [x] 8.3 Implement data table
    - Columns: Date, Task, Project, Hours, Mode, Billable, Note, Actions
    - Hours in dual format: "1.5h (1:30)"
    - Mode as badge (manual/timer)
    - Billable as badge
    - Actions: Edit, Delete buttons
  - [x] 8.4 Implement filters
    - Date range picker (from/to)
    - Task/project search input
    - Billable toggle filter
    - Apply filters via URL query params
  - [x] 8.5 Implement pagination
    - 25 entries per page
    - Use existing pagination component pattern
  - [x] 8.6 Implement edit functionality
    - Open TimeEntryForm in Sheet/Dialog
    - Pre-populate with entry data
    - Submit via PATCH to update endpoint
  - [x] 8.7 Implement delete functionality
    - Show confirmation Dialog
    - DELETE to destroy endpoint on confirm
    - Refresh list after delete
  - [x] 8.8 Run page tests

**Acceptance Criteria:**
- Page displays user's time entries
- Filters narrow down results
- Pagination works correctly
- Edit and delete functionality works
- All 3-4 tests pass

---

#### Task Group 9: Time Reports Page
**Dependencies:** Task Group 8

- [x] 9.0 Complete time reports page with tabs
  - [x] 9.1 Write 4-6 focused tests for reports page
    - Test page loads with default "By User" tab
    - Test "By User" tab shows user hours by date
    - Test "By Project" tab shows hierarchical project data
    - Test "Actual vs Estimated" tab shows variance data
    - Test date range filter applies to reports
    - Test reports respect team context
  - [x] 9.2 Create TimeReportsController
    - File: `app/Http/Controllers/Reports/TimeReportsController.php`
    - Methods: index, byUser, byProject, actualVsEstimated
    - Accept date range parameters
    - Scope to current team
  - [x] 9.3 Create reports index page
    - File: `resources/js/pages/reports/time/index.tsx`
    - Route: `/reports/time`
    - Use Tabs component for switching views
    - Use AppLayout with breadcrumbs
  - [x] 9.4 Implement "By User" tab
    - Table showing users
    - Daily columns based on date range
    - Weekly total column
    - Date range selector
  - [x] 9.5 Implement "By Project" tab
    - Collapsible tree structure
    - Levels: Project > Work Order > Task
    - Hours displayed at each level
    - Sum rolls up from tasks
  - [x] 9.6 Implement "Actual vs Estimated" tab
    - Table with columns: Name, Estimated Hours, Actual Hours, Variance, Variance %
    - Show tasks and work orders
    - Color code variance: green (under), yellow (close), red (over)
  - [x] 9.7 Add export placeholder buttons
    - Add disabled "Export" buttons for each tab
    - Tooltip: "Coming soon"
  - [x] 9.8 Register routes in routes/web.php
    - File: `routes/web.php`
    - GET `/reports/time` -> index
    - GET `/reports/time/by-user` -> byUser (API)
    - GET `/reports/time/by-project` -> byProject (API)
    - GET `/reports/time/actual-vs-estimated` -> actualVsEstimated (API)
  - [x] 9.9 Run page tests

**Acceptance Criteria:**
- Three report views accessible via tabs
- "By User" shows daily/weekly breakdown
- "By Project" shows hierarchical view
- "Actual vs Estimated" shows variance with color coding
- Date range filters work
- Reports respect team context
- All 4-6 tests pass

---

#### Task Group 10: Task/Work Order Progress Indicators
**Dependencies:** Task Group 8

- [x] 10.0 Complete actual vs estimated progress indicators
  - [x] 10.1 Write 2-3 focused tests for HoursProgressIndicator
    - Test shows correct percentage and colors
    - Test handles zero estimated hours gracefully
    - Test displays both decimal and time format
  - [x] 10.2 Create HoursProgressIndicator component
    - File: `resources/js/components/time-tracking/hours-progress-indicator.tsx`
    - Props: actualHours, estimatedHours
    - Calculate percentage: (actual / estimated) * 100
  - [x] 10.3 Implement progress bar and colors
    - Green: under 80%
    - Yellow: 80-100%
    - Red: over 100%
    - Show: "X.X / Y.Y hours (ZZ%)"
    - Secondary format: "(H:MM / H:MM)"
  - [x] 10.4 Integrate into task detail views
    - Locate existing task detail page/component
    - Add HoursProgressIndicator where appropriate
    - Pass task.actual_hours and task.estimated_hours
  - [x] 10.5 Integrate into work order detail views
    - Locate existing work order detail page/component
    - Add HoursProgressIndicator where appropriate
    - Pass workOrder.actual_hours and workOrder.estimated_hours
  - [x] 10.6 Run component tests

**Acceptance Criteria:**
- Progress indicator shows on task details
- Progress indicator shows on work order details
- Color coding reflects percentage thresholds
- Both time formats displayed
- All 2-3 tests pass

---

### Phase 6: Integration and Testing

#### Task Group 11: Integration Testing
**Dependencies:** All previous task groups

- [x] 11.0 Complete integration testing and gap analysis
  - [x] 11.1 Review all tests from Task Groups 1-10
    - Verified all 50 tests from groups 1-10 are passing (36 backend + 14 frontend)
    - No flaky or failing tests identified
    - Total count exceeds initial estimate of 30-40 tests
  - [x] 11.2 Identify critical integration gaps
    - Identified gaps in end-to-end cascade workflows
    - Timer start -> stop -> entry appears in history (needed coverage)
    - Manual entry -> hours cascade to work order and project (needed coverage)
    - Header timer indicator cross-page persistence (needed coverage)
  - [x] 11.3 Write up to 10 additional integration tests if needed
    - Added 6 focused integration tests in `tests/Feature/TimeEntry/TimeTrackingIntegrationTest.php`:
      1. Complete timer workflow: start, stop, entry appears in history
      2. Manual entry cascades hours to task, work order, and project
      3. Time entry deletion cascades hours recalculation through hierarchy
      4. Multiple time entries on same task sum correctly
      5. stopById from header indicator stops timer and recalculates hours
      6. activeTimer shared data reflects running timer across pages
  - [x] 11.4 Run full feature test suite
    - All 56 tests pass (42 backend + 14 frontend)
    - Backend: 42 tests, 303 assertions
    - Frontend: 14 tests
  - [x] 11.5 Verify all acceptance criteria from spec
    - All spec requirements verified and implemented (see verification below)

**Acceptance Criteria:**
- All feature-specific tests pass: YES (56 tests)
- Critical user workflows are covered: YES (6 integration tests added)
- No more than 10 additional tests added: YES (6 added)
- Feature meets all spec requirements: YES (verified below)

---

## Spec Requirements Verification

### is_billable field
- [x] Migration adds `is_billable` boolean column with default true
- [x] TimeEntry model includes `is_billable` in fillable and casts
- [x] TimeEntryController store accepts and saves `is_billable`
- [x] TimeEntry::startTimer() accepts optional `is_billable` parameter

### Global running timer detection
- [x] `scopeRunningForUser` scope implemented on TimeEntry
- [x] Shared Inertia data includes `activeTimer` object
- [x] activeTimer contains: id, taskId, taskTitle, projectName, startedAt, isBillable

### Persistent timer indicator in header
- [x] ActiveTimerIndicator component in AppSidebarHeader
- [x] Displays when activeTimer is present
- [x] Shows elapsed time updating every second
- [x] Shows task name truncated to fit
- [x] Click opens popover with full details
- [x] Stop button calls POST `/work/time-entries/{id}/stop`
- [x] Indigo accent color with pulse animation

### Manual time entry form
- [x] TimeEntryForm component with optional taskId prop
- [x] Form fields: Hours (decimal 0.25 step), Date, Note, Billable toggle
- [x] Hours validation 0.01-24 on client and server
- [x] Submit via Inertia POST
- [x] Success feedback and form reset

### Timer start/stop controls
- [x] TimerControls component with taskId and activeTimerForTask props
- [x] Start/Stop buttons with play/stop icons
- [x] Elapsed time display with live updates
- [x] Start/Stop endpoints called correctly

### Time entry history list view
- [x] `/work/time-entries` page with data table
- [x] Columns: Date, Task, Project, Hours (dual format), Mode, Billable, Note, Actions
- [x] Filters: date range, billable toggle
- [x] Pagination with 25 entries per page
- [x] Edit and Delete functionality

### Time entry CRUD endpoints
- [x] GET `/work/time-entries` - List with filters
- [x] POST `/work/time-entries` - Create with is_billable
- [x] GET `/work/time-entries/{id}` - Show for edit
- [x] PATCH `/work/time-entries/{id}` - Update
- [x] DELETE `/work/time-entries/{id}` - Soft delete with hours recalculation
- [x] POST `/work/time-entries/{id}/stop` - Stop specific timer
- [x] All endpoints scoped to current user

### Time reports page
- [x] `/reports/time` with three tab views
- [x] "By User" tab with daily/weekly breakdown
- [x] "By Project" tab with hierarchical tree
- [x] "Actual vs Estimated" tab with variance and color coding
- [x] Date range filters
- [x] Team context respected
- [x] Export placeholder buttons

### Progress indicators
- [x] HoursProgressIndicator component
- [x] Shows actual/estimated with percentage
- [x] Color coding: green (<80%), yellow (80-100%), red (>100%)
- [x] Both decimal and H:MM formats displayed

---

## Execution Order

Recommended implementation sequence:

1. **Phase 1: Database & Backend Foundation**
   - Task Group 1: Database Schema Updates (is_billable migration)
   - Task Group 2: Running Timer Scope
   - Task Group 3: API Endpoints

2. **Phase 2: Timer State Management**
   - Task Group 4: Inertia Shared Data Provider

3. **Phase 3: Frontend Components**
   - Task Group 5: Timer Controls Component
   - Task Group 6: Manual Time Entry Form
   (These can be done in parallel)

4. **Phase 4: Header Integration**
   - Task Group 7: Active Timer Indicator

5. **Phase 5: Pages and Views**
   - Task Group 8: Time Entry History Page
   - Task Group 9: Time Reports Page
   - Task Group 10: Task/Work Order Progress Indicators
   (Groups 8 and 10 can be done in parallel)

6. **Phase 6: Integration and Testing**
   - Task Group 11: Integration Testing

---

## Key Files Summary

### Backend Files
- `database/migrations/2026_01_16_123118_add_is_billable_to_time_entries_table.php` (new)
- `database/migrations/2026_01_16_104029_add_soft_deletes_to_time_entries_table.php` (new)
- `app/Models/TimeEntry.php` (modify)
- `app/Http/Controllers/Work/TimeEntryController.php` (modify)
- `app/Policies/TimeEntryPolicy.php` (new)
- `app/Http/Controllers/Reports/TimeReportsController.php` (new)
- `app/Http/Middleware/HandleInertiaRequests.php` (modify)
- `routes/work.php` (modify)
- `routes/web.php` (modify)

### Frontend Files
- `resources/js/types/index.d.ts` (modify)
- `resources/js/types/work.d.ts` (modify)
- `resources/js/components/time-tracking/timer-controls.tsx` (new)
- `resources/js/components/time-tracking/time-entry-form.tsx` (new)
- `resources/js/components/time-tracking/active-timer-indicator.tsx` (new)
- `resources/js/components/time-tracking/hours-progress-indicator.tsx` (new)
- `resources/js/components/app-sidebar-header.tsx` (modify)
- `resources/js/pages/work/time-entries/index.tsx` (new)
- `resources/js/pages/reports/time/index.tsx` (new)

### Test Files
- `tests/Feature/TimeEntry/IsBillableTest.php` (new) - 4 tests
- `tests/Feature/TimeEntry/RunningTimerScopeTest.php` (new) - 4 tests
- `tests/Feature/TimeEntry/TimeEntryApiTest.php` (new) - 8 tests
- `tests/Feature/TimeEntry/SharedTimerDataTest.php` (new) - 3 tests
- `tests/Feature/TimeEntry/TimeEntriesPageTest.php` (new) - 4 tests
- `tests/Feature/TimeEntry/TimeReportsTest.php` (new) - 6 tests
- `tests/Feature/TimeEntry/TimeTrackingIntegrationTest.php` (new) - 6 tests
- `tests/Feature/Work/TimeEntryControllerTest.php` (existing) - 7 tests
- `resources/js/components/time-tracking/__tests__/timer-controls.test.tsx` (new) - 4 tests
- `resources/js/components/time-tracking/__tests__/time-entry-form.test.tsx` (new) - 4 tests
- `resources/js/components/time-tracking/__tests__/active-timer-indicator.test.tsx` (new) - 3 tests
- `resources/js/components/time-tracking/__tests__/hours-progress-indicator.test.tsx` (new) - 3 tests

**Total Tests: 56 (42 backend + 14 frontend)**
