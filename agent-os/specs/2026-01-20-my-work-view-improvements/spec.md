# Specification: My Work View Improvements

## Goal
Enhance the "My Work" subsection of the Work page to provide users with a comprehensive overview of all projects, work orders, and tasks where they have any RACI role (Responsible, Accountable, Consulted, Informed) or direct assignment, with subtabs for organized navigation and RACI-aware filtering capabilities.

## User Stories
- As a team member, I want to see all work items where I have any RACI role so that I understand my full scope of responsibilities and involvement
- As a project contributor, I want to filter and sort my work items by RACI role and due date so that I can prioritize my most critical responsibilities

## Specific Requirements

**Subtab Navigation System**
- Four subtabs: Tasks (default), Work Orders, Projects, All
- Tasks subtab is the default view when first visiting My Work
- Tab selection should persist via user preferences (existing `UserPreference` pattern)
- Use existing `ViewTabs` component pattern as reference for subtab styling
- Subtabs should appear below the main view tabs, visually nested

**Tasks Subtab**
- Display all tasks directly assigned to the current user (`assigned_to_id`)
- Tasks do not use RACI roles; use simple assignment model
- Group by status: Urgent/Overdue, In Progress, To Do, Blocked
- Show task title, status badge, due date, work order name, and project name
- Sort by due date (default)

**Work Orders Subtab**
- Display work orders where user has any RACI role (Accountable, Responsible, Consulted, Informed)
- Show RACI role badge(s) on each work order item
- Visual prominence: Accountable items styled more prominently than Informed
- "Informed" items hidden by default with toggle to show
- Display: title, status, priority, due date, project name, and RACI badges

**Projects Subtab**
- Display projects where user has any RACI role (Accountable, Responsible, Consulted, Informed)
- Show RACI role badge(s) on each project item
- Visual prominence: Accountable items styled more prominently than Informed
- "Informed" items hidden by default with toggle to show
- Display: name, status, party name, progress, and RACI badges

**All Subtab (Tree View)**
- Hierarchical tree view similar to existing `ProjectTreeItem` component
- Structure: Projects > Work Orders > Tasks
- Only show items where user has RACI role on project/work order OR is assigned to task
- Show RACI badges at each level for projects and work orders
- Collapsible nodes with expand/collapse functionality
- "Informed" items hidden by default with toggle to show

**RACI Role Badges**
- Create new `RaciBadge` component following `StatusBadge` pattern
- Color scheme for visual hierarchy:
  - Accountable: Purple/violet (highest prominence, border and background)
  - Responsible: Blue/indigo (high prominence)
  - Consulted: Amber/yellow (medium prominence)
  - Informed: Gray/slate (lowest prominence, subtle styling)
- When user has multiple roles, show multiple badges in prominence order
- Badge text: "A" for Accountable, "R" for Responsible, "C" for Consulted, "I" for Informed
- Tooltip on hover showing full role name

**Summary Metrics Section**
- Display at top of My Work view, above subtabs
- Metrics to show:
  - "X items where you're Accountable" (count of projects + work orders)
  - "X items where you're Responsible"
  - "X items awaiting your review" (work orders in `in_review` status where user is Accountable)
  - "X tasks assigned to you" (incomplete tasks)
- Use existing `StatCard` component pattern
- Clicking a metric should filter to show only those items

**Filtering Capabilities**
- Filter by RACI role type: Accountable, Responsible, Consulted, Informed (multi-select)
- Filter by status: All statuses appropriate for the subtab's item type
- Filter by due date range: This week, Next 7 days, Next 30 days, Overdue, Custom range
- Filter controls should appear in a horizontal bar below the subtabs
- Filters apply to the currently active subtab only
- Clear all filters button

**Sorting Options**
- Default sort: Due date (ascending)
- Additional sort options: Priority, Recently updated, Alphabetical
- Sort direction toggle (ascending/descending)
- Sort selector in the filter bar area

**Show Informed Items Toggle**
- Toggle button/switch labeled "Show Informed"
- Default state: OFF (Informed items hidden)
- When enabled, Informed items appear with lower visual prominence
- Toggle state persists via user preferences

## Visual Design
No visual assets provided.

## Existing Code to Leverage

**`resources/js/components/work/my-work-view.tsx`**
- Current My Work implementation to extend
- `StatCard` component for summary metrics display
- `Section` component for grouping items by category
- Existing filtering logic for work orders and tasks by `assignedToId`
- Grid layout patterns for responsive card display

**`resources/js/components/work/project-tree-item.tsx`**
- Tree view component structure for the "All" subtab
- Collapsible node pattern with chevron icons
- Nested hierarchy rendering (Project > WorkOrderList > WorkOrder > Task)
- `TaskTreeItem` component for leaf-level task display
- Border and indentation styling for tree levels

**`resources/js/components/work/status-badge.tsx` and `resources/js/components/ui/badge.tsx`**
- Badge component pattern to replicate for RACI badges
- Color scheme approach using Tailwind classes
- Variant-based styling with class-variance-authority

**`app/Models/Project.php` and `app/Models/WorkOrder.php`**
- RACI fields: `accountable_id`, `responsible_id`, `consulted_ids` (array), `informed_ids` (array)
- Existing relationships: `accountable()`, `responsible()` BelongsTo relationships
- Scope methods to extend for RACI-based filtering

**`app/Http/Controllers/Work/WorkController.php`**
- Current data fetching pattern for Work page
- `UserPreference` usage for persisting view state
- Data transformation pattern for frontend consumption

## Out of Scope
- RACI role extension for tasks (tasks remain with simple `assigned_to_id` assignment only)
- Notification system for RACI role changes
- Email digests or external notifications about work items
- Bulk actions on work items (bulk status change, bulk reassignment)
- Custom view layouts or dashboard personalization
- Integration with AI agents for work suggestions
- Drag-and-drop reordering within My Work views
- Export functionality for work items
- Mobile-specific layouts (use responsive design only)
- Real-time updates via WebSockets (use standard page refresh pattern)
