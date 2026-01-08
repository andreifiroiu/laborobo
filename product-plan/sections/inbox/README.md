# Inbox Specification

## Overview
Centralized queue for everything that needs human review or decision. The "3 things need your brain" hub where agent drafts, approval requests, flagged items, and mentions await action.

## User Flows
- **Browse inbox items** - User views items organized by tabs (All, Agent Drafts, Approvals, Flagged, Mentions) with color-coded urgency and AI confidence indicators
- **Review item details** - User clicks item to open side panel showing full content, related work order/project, and available actions
- **Take action** - User approves, rejects with feedback, edits before approving, or defers item for later
- **Bulk process** - User selects multiple items via checkboxes and applies bulk actions (approve all, defer all, archive)
- **Filter and sort** - User filters by type, source, or urgency, and sorts by date or priority
- **Track waiting time** - User sees how long each item has been pending to prioritize reviews

## UI Requirements
- Tab navigation for All, Agent Drafts, Approvals, Flagged, Mentions
- List view with checkboxes for bulk selection
- Each item displays: type badge, source (agent/user), related project/work order, content preview, timestamp, urgency color indicator, AI confidence score
- Side panel for detailed item view with full content and action buttons
- Action buttons: Approve, Reject/Request Changes, Edit, Defer
- Bulk action toolbar when items are selected
- Color-coded urgency indicators (red for urgent, orange for high priority, normal for standard)
- AI confidence scores (high/medium/low) for agent-generated items
- Empty states for each tab when no items pending
- Counter badges showing pending count per tab
- Search and filter controls
- QA Agent pre-validation indicators when available

## Configuration
- shell: true
