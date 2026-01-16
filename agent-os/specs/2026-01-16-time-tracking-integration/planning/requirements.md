# Spec Requirements: Time Tracking Integration

## Initial Description

Time Tracking Integration - Time entry UI for logging hours against tasks/work orders, timer functionality, time log history, and basic time reporting by user and project.

This feature builds on the existing TimeEntry model which supports manual time entry, timer mode with start/stop timestamps, AI estimation mode, and linking time to tasks (which cascade up to work orders and projects). The goal is to create the UI layer that allows users to interact with time tracking throughout the application.

## Requirements Discussion

### First Round Questions

**Q1:** Time Entry Methods - Should this support both manual entry AND timer, or just one approach?
**Answer:** Both manual entry AND timer. Manual for logging past work, timer for real-time tracking.

**Q2:** Task vs Work Order Level - Should time entries be logged at the task level only (current model), or also directly against work orders?
**Answer:** Task-level is sufficient. Users can create generic tasks if needed.

**Q3:** Billable vs Non-billable - Does this need to distinguish between billable and non-billable time?
**Answer:** Yes, add a billable flag. Default to billable.

**Q4:** Time Display Format - Preference between decimal hours (1.5h) or hours:minutes (1:30)?
**Answer:** Decimal hours (1.5h) for data entry, but show both formats where helpful (e.g., "1.5 hours (1:30)").

**Q5:** Timer Persistence - Should a running timer persist across page navigation? Should there be a timer indicator in the header?
**Answer:** Yes, persist across navigation with a timer indicator in the header. Users can click it to see what's running and stop from anywhere.

**Q6:** Rounding - Any automatic rounding rules (e.g., round to nearest 15 minutes)?
**Answer:** Store exact time, no automatic rounding.

**Q7:** Reporting Scope - What reports are needed initially?
**Answer:** Keep minimal:
- Time by user (daily/weekly view)
- Time by project (summary, and detailed, grouped by work order and/or task)
- Actual vs estimated on work orders/tasks

**Q8:** Approval Workflow - Does logged time need approval from managers/admins?
**Answer:** No approval required - users log time freely.

**Q9:** Exclusions - What should NOT be included in this feature?
**Answer:** Overtime tracking, break time, timesheet exports, external integrations, payroll features.

### Existing Code to Reference

**Similar Features Identified:**
- Model: `TimeEntry` - Path: `app/Models/TimeEntry.php`
  - Already has manual and timer modes via `TimeTrackingMode` enum
  - Includes `started_at` and `stopped_at` timestamps for timer
  - Has `startTimer()` and `stopTimer()` methods
  - Links to Task via `task_id` relationship
- Model: `Task` - Path: `app/Models/Task.php`
  - Has `estimated_hours` and `actual_hours` fields
  - Has `timeEntries()` relationship
  - Has `recalculateActualHours()` method that cascades to WorkOrder
- Model: `WorkOrder` - Path: `app/Models/WorkOrder.php`
  - Has `estimated_hours` and `actual_hours` fields
  - `recalculateActualHours()` cascades to Project
- Enum: `TimeTrackingMode` - Path: `app/Enums/TimeTrackingMode.php`
  - Supports: Manual, Timer, AiEstimation modes

**Backend Infrastructure Already Exists:**
- TimeEntry model with all required fields (team_id, user_id, task_id, hours, date, mode, note, started_at, stopped_at)
- Timer start/stop logic implemented
- Automatic hours recalculation cascade from Task to WorkOrder to Project
- Scopes for filtering by team, user, and date

**Backend Additions Needed:**
- Add `is_billable` boolean field to TimeEntry model (new migration)
- Consider adding a query for detecting running timers for the current user

### Follow-up Questions

No follow-up questions were needed.

## Visual Assets

### Files Provided:
No visual assets provided.

### Visual Insights:
Not applicable - no visuals to analyze.

## Requirements Summary

### Functional Requirements

**Time Entry Methods:**
- Manual entry for logging past work with hours and date
- Real-time timer with start/stop functionality
- Both methods create TimeEntry records linked to Tasks

**Billable Tracking:**
- Add billable/non-billable flag to time entries
- Default new entries to billable
- Allow users to toggle billable status when creating/editing entries

**Timer Functionality:**
- Start timer from any task context
- Timer persists across page navigation (stored in database via existing started_at/stopped_at)
- Global timer indicator in application header
- Click indicator to view running timer details
- Stop timer from anywhere via header indicator
- Automatically calculate hours when timer stops (existing logic)

**Time Entry UI:**
- Log time against specific tasks
- Enter hours in decimal format (1.5h)
- Display hours in dual format where helpful: "1.5 hours (1:30)"
- Add optional notes to time entries
- View and edit own time log history
- No approval workflow required

**Reporting:**
- Time by user view (daily and weekly breakdown)
- Time by project summary
- Actual vs estimated comparison on work orders and tasks
- Reports should respect team context

**Cascading Updates:**
- When time entries are added/modified, Task actual_hours auto-recalculates (existing)
- Task updates cascade to WorkOrder (existing)
- WorkOrder updates cascade to Project (existing)

### Reusability Opportunities

- Leverage existing `TimeEntry::startTimer()` and `stopTimer()` methods
- Use existing `recalculateActualHours()` cascade logic
- Use existing `TimeTrackingMode` enum for mode selection
- Follow existing model scope patterns (forTeam, forUser, forDate)
- Reference existing Livewire/Volt component patterns for UI

### Scope Boundaries

**In Scope:**
- Manual time entry UI
- Timer start/stop UI
- Global timer indicator in header
- Time log history view (user's own entries)
- Edit/delete own time entries
- Billable flag on time entries
- Basic reports: time by user, time by project, actual vs estimated
- Decimal hours input with dual-format display

**Out of Scope:**
- Overtime tracking or calculations
- Break time tracking
- Timesheet exports (PDF, CSV, etc.)
- External integrations (calendars, other time tracking tools)
- Payroll features or calculations
- Time entry approval workflows
- Automatic rounding rules
- AI estimation mode UI (model supports it, but not implementing UI for it now)
- Editing other users' time entries

### Technical Considerations

**Model Changes:**
- Add `is_billable` boolean to TimeEntry (migration required)
- Default `is_billable` to true

**UI Components Needed:**
- Time entry form (manual mode) - Livewire/Volt component
- Timer control component (start/stop) - Livewire/Volt component
- Header timer indicator - persistent across navigation
- Time log list/history view
- Time reporting pages (by user, by project)
- Actual vs estimated display on task/work order views

**Data Format:**
- Store exact decimal hours (no rounding)
- Input: decimal hours (1.5)
- Display: dual format where helpful "1.5 hours (1:30)"

**Timer Persistence:**
- Timer state stored in database (existing started_at field)
- Poll or use Livewire events to keep header indicator updated
- Only one active timer per user at a time

**Reporting Queries:**
- Sum hours grouped by user and date range
- Sum hours grouped by project (via Task -> WorkOrder -> Project relationship)
- Compare actual_hours vs estimated_hours on Tasks and WorkOrders
