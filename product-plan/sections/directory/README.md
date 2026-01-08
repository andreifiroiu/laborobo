# Directory Specification

## Overview
The central hub for managing all people and entities involved in work. Tracks Parties (clients, vendors, partners, departments), Contacts (individuals at each party), and Team members (internal users with skills and capacity). Provides context for work assignment and relationship management without requiring full CRM.

## User Flows
- **Browse directory** - User switches between tabs (Parties, Contacts, Team) to view organized lists
- **Search and filter** - User searches across names, emails, and notes; filters by type (Client, Vendor, Partner, etc.), status (Active/Inactive), and tags
- **View details** - User clicks an entry to open side panel showing full details, related items, projects, and communication history
- **Create/edit entry** - User opens side panel form to add new party/contact/team member or edit existing information
- **View related items** - From a Party, user sees linked contacts, projects, comms history; from a Contact, user sees party association and engagement history
- **Manage team skills** - User views and edits team member skill inventory with proficiency levels and capacity tracking
- **Track capacity** - User sees current workload vs available capacity for team members

## UI Requirements
- Tab navigation for three sections: Parties, Contacts, Team
- List view with cards/rows showing: name, type/role, status, primary contact (for parties), and key metadata
- Side panel/drawer for viewing details (slides in from right, keeps list visible)
- Side panel form for creating and editing entries (reuses same drawer)
- Full-text search across names, emails, and notes
- Filter dropdowns for type, status, and clickable tag filters
- Party detail view shows: linked contacts, related projects, comms history, documents
- Contact detail view shows: party association, role, engagement type, communication preferences
- Team member view shows: skills inventory with proficiency levels (1-3), capacity (hours/week), current workload
- Status indicators for Active/Inactive
- Tag system for categorization
- Empty states per tab with "Add first [type]" prompts

## Configuration
- shell: true
