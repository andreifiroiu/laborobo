# Specification: Time Tracking Integration

## Goal
Build a complete time tracking UI that allows users to log hours against tasks via manual entry or real-time timer, view their time history, and access basic reports comparing actual vs estimated time across projects.

## User Stories
- As a team member, I want to log my work hours against tasks so that project time is accurately tracked and actual hours cascade up to work orders and projects.
- As a team member, I want to start/stop a timer while working so that I can track time in real-time without manual calculations.
- As a manager, I want to view time reports by user and project so that I can monitor progress and compare actual vs estimated hours.

## Specific Requirements

**Add is_billable field to TimeEntry model**
- Create migration adding `is_billable` boolean column to `time_entries` table
- Default value should be `true`
- Update TimeEntry model's `$fillable` array to include `is_billable`
- Add `is_billable` to model's `$casts` as boolean
- Update existing TimeEntryController store method to accept and save `is_billable`
- Update TimeEntry::startTimer() to accept optional `is_billable` parameter (defaults to true)

**Global running timer detection and state**
- Add scope `scopeRunningForUser($query, int $userId)` to TimeEntry model that filters for entries with `started_at` not null and `stopped_at` null
- Create a shared Inertia data provider that includes `activeTimer` object in every request's shared data
- `activeTimer` should contain: `id`, `taskId`, `taskTitle`, `projectName`, `startedAt`, `isBillable` (or null if no timer running)
- Query for running timer should be cached briefly to avoid repeated queries per request

**Persistent timer indicator in sidebar header**
- Add `ActiveTimerIndicator` component to `AppSidebarHeader` between breadcrumbs and right edge
- Display when `activeTimer` is present in shared props
- Show elapsed time (updating every second via `setInterval` in React)
- Show task name truncated to fit available space
- Click opens a popover/dropdown with: task name, project name, elapsed time, stop button
- Stop button calls POST to `/work/time-entries/{id}/stop` endpoint
- Timer indicator should pulse/animate subtly to indicate active state (indigo color accent per design standards)

**Manual time entry form component**
- Create `TimeEntryForm` React component accepting `taskId` prop (optional for standalone use)
- Form fields: Task selector (if no taskId prop), Hours (decimal input), Date (date picker, defaults to today), Note (textarea), Billable (toggle, defaults to on)
- Hours input should accept decimal values (e.g., 1.5) with step of 0.25
- Validate hours between 0.01 and 24 on both client and server
- Submit via Inertia POST to `/work/time-entries`
- Show success feedback and reset form on successful submission
- Can be embedded in task detail views or used standalone

**Timer start/stop controls**
- Create `TimerControls` React component accepting `taskId` and optional `activeTimerForTask` props
- When no timer running for this task: show "Start Timer" button with play icon
- When timer running for this task: show "Stop Timer" button with stop icon and elapsed time
- Start calls POST `/work/tasks/{task}/timer/start` with optional `is_billable` parameter
- Stop calls POST `/work/tasks/{task}/timer/stop`
- Integrate controls into task detail views and task list items
- Starting a timer should automatically stop any other running timer (backend already handles this)

**Time entry history list view**
- Create `/work/time-entries` page showing current user's time entries
- Display as table with columns: Date, Task, Project, Hours, Mode (manual/timer badge), Billable, Note, Actions
- Hours displayed in dual format: "1.5h (1:30)"
- Include filters: date range picker, task/project search, billable toggle
- Pagination with 25 entries per page
- Each row has Edit and Delete action buttons
- Edit opens TimeEntryForm in a dialog pre-populated with entry data
- Delete shows confirmation dialog before calling DELETE `/work/time-entries/{id}`

**Time entry CRUD endpoints**
- GET `/work/time-entries` - List current user's entries with filters (date_from, date_to, task_id, billable)
- POST `/work/time-entries` - Create manual entry (existing, add is_billable)
- GET `/work/time-entries/{id}` - Get single entry for edit form
- PATCH `/work/time-entries/{id}` - Update entry (hours, date, note, is_billable)
- DELETE `/work/time-entries/{id}` - Soft delete entry and recalculate task hours
- POST `/work/time-entries/{id}/stop` - Stop a specific running timer (new endpoint for header indicator)
- All endpoints scoped to current user's entries within their team

**Time reports page**
- Create `/reports/time` page with three report views switchable via tabs
- Tab 1 "By User": Table showing users, total hours (daily column and weekly total), with date range selector
- Tab 2 "By Project": Collapsible tree showing Project > Work Order > Task hierarchy with hours at each level
- Tab 3 "Actual vs Estimated": Table showing tasks/work orders with estimated_hours, actual_hours, variance (actual - estimated), variance percentage
- All reports respect team context from current organization
- Include export placeholder buttons (actual export is out of scope)

**Display actual vs estimated on task/work order views**
- Add progress indicator to task detail showing `actual_hours / estimated_hours` with percentage
- Use color coding: green (under 80%), yellow (80-100%), red (over 100%)
- Show same indicator on work order detail views at the work order level
- Display both decimal and hours:minutes format

## Visual Design
No visual mockups provided. Follow existing design patterns from the codebase.

**Timer indicator styling**
- Use indigo accent color for active state per CSS standards
- Subtle pulse animation on the timer icon
- Popover uses existing Radix UI Popover primitive styling
- Elapsed time in monospace font for readability

**Form and table styling**
- Follow existing Card, Table, Dialog, Sheet patterns from `@/components/ui`
- Use existing Button variants (default for primary actions, outline for secondary)
- Badge component for mode (manual/timer) and billable status indicators
- Form inputs use existing Input, Textarea, Switch components

## Existing Code to Leverage

**TimeEntry model (`app/Models/TimeEntry.php`)**
- Already has `startTimer()` static method and `stopTimer()` instance method
- Existing scopes: `forTeam`, `forUser`, `forDate`
- Timer stop already calculates hours from `started_at` to `stopped_at`
- Calls `task->recalculateActualHours()` on stop

**TimeEntryController (`app/Http/Controllers/Work/TimeEntryController.php`)**
- Existing `store`, `startTimer`, `stopTimer` methods provide foundation
- Already handles stopping other active timers when starting new one
- Extend with update, destroy, index, and dedicated stop-by-id methods

**Task and WorkOrder models**
- Both have `estimated_hours` and `actual_hours` fields
- Both have `recalculateActualHours()` methods that cascade upward
- Task has `timeEntries()` relationship

**AppSidebarHeader component (`resources/js/components/app-sidebar-header.tsx`)**
- Current structure: SidebarTrigger + Breadcrumbs in header
- Timer indicator should be added to the right side of this header
- Access shared props via `usePage<SharedData>()`

**Sheet and Dialog components**
- Use `Sheet` for side panels (edit time entry)
- Use `Dialog` for confirmations (delete)
- Follow patterns from `TaskSheet` component in today components

## Out of Scope
- Overtime tracking or calculations
- Break time tracking
- Timesheet exports (PDF, CSV, Excel)
- External calendar integrations
- Payroll features or rate calculations
- Time entry approval workflows
- Automatic rounding rules (store exact time)
- AI estimation mode UI (model supports it but not building UI)
- Editing other users' time entries
- Bulk time entry operations
- Time entry comments or attachments
