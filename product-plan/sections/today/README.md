# Today Specification

## Overview
The Today section is a daily command center that answers "what needs my attention right now?" at a glance. It surfaces approvals waiting, tasks due, blockers, upcoming deadlines, and recent activity, with AI-powered priority suggestions and a daily summary from the PM Copilot.

## User Flows
- **Review Daily Summary** - User sees PM Copilot-generated summary at top showing what needs to happen today
- **Process Approvals** - User clicks on approval item → opens modal/drawer → views details → approves or rejects
- **Act on Tasks Due** - User clicks on task → opens modal/drawer → views details → marks complete or updates
- **Address Blockers** - User clicks on blocked item → opens modal/drawer → views details → resolves or escalates
- **Quick Capture** - User clicks quick capture button → enters request/note/task → submits → taken to detail view to configure further
- **Monitor Activity** - User reviews recent activity feed to see what happened since last login
- **View Suggested Priorities** - AI agents surface prioritized items based on deadlines, dependencies, and capacity

## UI Requirements
- Dashboard grid layout with flexible cards for each component section
- PM Copilot daily summary as prominent top banner/card at the top
- Component cards: Approvals Queue (with count badge), My Tasks Due (sorted by priority), Blockers, Upcoming Deadlines (next 7 days), Recent Activity Feed
- Key metrics displayed subtly: tasks completed today/this week, approvals pending, hours logged today (if time tracking enabled)
- Quick Capture button/input prominently placed for logging new requests, notes, or tasks
- Empty sections show with appropriate messages (e.g., "No approvals pending", "All caught up!")
- All item interactions (approvals, tasks, blockers) open in modal/drawer overlay
- Approvals require viewing details before approve/reject actions are available
- Tasks due are sorted by priority and show overdue items first
- Blockers show items flagged across all projects (missing info, waiting on someone, stuck)

## Configuration
- shell: true
