# Spec Requirements: My Work View Improvements

## Initial Description
- Improve the "My Work" subsection of the Work page
- Better overview of all projects, work orders, and tasks assigned to the user
- Include items where the user has any RACI role (not just direct assignment)
- Subtabs for each item type (projects, work orders, tasks)
- Open to alternative view proposals

## Requirements Discussion

### First Round Questions

**Q1:** RACI Role Visibility - How should different RACI roles be displayed? Should "Responsible" and "Accountable" items be more prominent than "Consulted" or "Informed" items? Should we show the user's role as a badge on each item?
**Answer:** Yes, make "Accountable" items more prominent than "Informed" items, show the role in a badge, and add filter by role.

**Q2:** Subtab Structure - You mentioned subtabs for Projects, Work Orders, and Tasks. Should there also be an "All" tab that shows everything in a combined view, or keep it strictly separated by type?
**Answer:** Add an "All" subtab similar to the tree view in the All Projects subsection.

**Q3:** Role Priority for Display - When a user has multiple RACI roles on the same item (e.g., both Responsible and Accountable), which role should take priority for display purposes, or should all roles be shown?
**Answer:** Yes, show multiple role badges when user has multiple roles on same item.

**Q4:** Tasks RACI Extension - Currently tasks have a simple assigned user. Should we extend tasks to support full RACI roles, or should tasks only appear in My Work when the user is directly assigned?
**Answer:** Tasks have a simple assigned user. Use this. No need for RACI on tasks.

**Q5:** Default View Behavior - When a user first visits My Work, which subtab should be the default? Should it be the most actionable items (Tasks), or a summary view (All)?
**Answer:** Tasks (most actionable) should be the default subtab.

**Q6:** Filtering and Sorting - What filtering and sorting capabilities should be available? Options include: filter by RACI role type, status, due date range, project; sort by due date, priority, recently updated.
**Answer:** Yes, filters for RACI role type, status, and due date range. Also sort options with default sort by due date.

**Q7:** Summary Metrics - Should there be a summary section showing counts or metrics (e.g., "3 items where you're Accountable", "5 items awaiting your review")?
**Answer:** Yes, show RACI-specific metrics like "3 items where you're Accountable", "5 items awaiting your review".

**Q8:** What should NOT be included - Are there any aspects that should explicitly be excluded from this initial implementation? For example, should we avoid showing items where the user is only "Informed" to reduce noise?
**Answer:** Yes, make "Informed" items optional/toggleable rather than shown by default.

### Existing Code to Reference

No similar existing features were explicitly identified for reference. However, based on the answers:
- The "All Projects" subsection has a tree view that should be referenced for the "All" subtab structure
- Existing RACI implementation on Projects and Work Orders should be leveraged

### Follow-up Questions

No follow-up questions needed - user provided comprehensive answers.

## Visual Assets

### Files Provided:
No visual assets provided.

### Visual Insights:
N/A

## Requirements Summary

### Functional Requirements
- **Subtab Navigation**: Four subtabs - Tasks (default), Work Orders, Projects, and All
- **RACI Role Display**: Show role badges on items where user has RACI roles
- **Multiple Role Support**: Display multiple role badges when user has multiple roles on same item
- **Visual Hierarchy**: Make "Accountable" items more prominent than "Informed" items
- **Task Assignment**: Tasks use simple assigned user model (no RACI extension needed)
- **Tree View for All Tab**: Similar to existing All Projects subsection structure
- **Summary Metrics Section**: Display RACI-specific counts
  - "X items where you're Accountable"
  - "X items awaiting your review"
  - Other relevant metrics
- **Filtering Capabilities**:
  - Filter by RACI role type (Responsible, Accountable, Consulted, Informed)
  - Filter by status
  - Filter by due date range
- **Sorting Options**:
  - Sort by due date (default)
  - Additional sort options as appropriate
- **Informed Items Toggle**: "Informed" items hidden by default, toggleable to show

### Reusability Opportunities
- All Projects tree view component for the "All" subtab
- Existing RACI role badge components (if any)
- Existing filter/sort UI patterns from other views
- Existing tab/subtab navigation patterns

### Scope Boundaries

**In Scope:**
- Improved My Work subsection with four subtabs (Tasks, Work Orders, Projects, All)
- RACI role badges on projects and work orders
- Visual prominence for Accountable items over Informed items
- Summary metrics dashboard section
- Filtering by RACI role, status, due date range
- Sorting with default sort by due date
- Toggle for showing/hiding Informed items (hidden by default)
- Tree view for All tab similar to All Projects

**Out of Scope:**
- RACI role extension for tasks (tasks remain with simple assignment)
- Notification system for RACI role changes
- Email digests or external notifications
- Bulk actions on items
- Custom view layouts or personalization
- Integration with AI agents

### Technical Considerations
- RACI roles exist on Projects and Work Orders, not on Tasks
- Tasks use simple user assignment (assigned_user relationship)
- Need to query items where user has any RACI role (R, A, C, or I)
- "Informed" items should be excluded from default queries but available via toggle
- Multiple RACI roles per user per item is supported
- Due date may come from different fields depending on item type
- Tree view structure needs to aggregate projects > work orders > tasks hierarchy
