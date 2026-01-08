# Work Specification

## Overview
The Work section is the core work graph where all projects, work orders, tasks, and deliverables are managed. It provides multiple views (All Projects, My Work, By Status, Calendar, Archive) with an expandable tree navigation that lets users drill down through the hierarchy (Projects → Work Orders → Tasks → Deliverables) while also allowing detail pages for deeper focus.

## User Flows
- **Browse hierarchy** - User navigates through expandable tree (Projects → Work Orders → Tasks → Deliverables), expanding/collapsing items inline to reveal children
- **View details** - User clicks item to open full details page with all properties, related items, and side panel for comms thread
- **Switch views** - User switches between views using tabs at top (All Projects, My Work, By Status, Calendar, Archive), with quick access via collapsible submenus in left navigation panel
- **Create work items** - User quick-adds new project/work order/task inline (just title), then clicks to open and fill in full details
- **Track time** - User chooses between manual time entry, start/stop timer, or AI automatic estimation that suggests time based on activity patterns
- **Manage comms** - User views and adds to comms thread in persistent side panel when viewing project or work order details
- **Attach resources** - User attaches SOPs/checklists from Playbooks, links artifacts/deliverables, adds acceptance criteria
- **AI assistance** - Dispatcher creates work orders from requests, PM Copilot suggests task breakdowns, QA Agent validates against acceptance criteria

## UI Requirements
- Expandable tree structure showing Projects → Work Orders → Tasks → Deliverables hierarchy with expand/collapse controls
- Option to click any item to open dedicated details page with full information
- Tab navigation at top for switching between views: All Projects, My Work, By Status (Kanban), Calendar, Archive
- Collapsible submenus in left navigation panel for quick view switching
- Quick-add inline forms (title only) with option to open full detail form for complete configuration
- Persistent side panel for comms thread when viewing project or work order details
- Time tracking options: manual entry fields, start/stop timer widget, and AI estimation suggestions
- Status badges and progress indicators throughout (project status, work order status, task status)
- Priority labels (Low, Medium, High, Urgent) with visual coding
- Budget and time tracking displays (estimated vs. actual hours)
- Artifact/deliverable attachments with version history
- Dependencies and blockers clearly indicated
- AI-generated suggestions highlighted (task breakdowns, work orders from requests, validation results)
- Search and filter controls for each view
- Default to user's last-used view on entry
- Empty states with helpful prompts for creating first project/work order

## Configuration
- shell: true
