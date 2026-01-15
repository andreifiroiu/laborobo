# Product Roadmap

## Foundation (Complete)
1. [x] Work Graph Data Models — Database schema and Eloquent models for Parties, Projects, Work Orders, Tasks, Deliverables with relationships, status enums, and team scoping. `M`
2. [x] Directory System — Parties (clients/vendors) and Contacts management with CRUD operations, search, filtering by type, and relationship to work items. `M`
3. [x] User and Team Management — Multi-tenant team architecture with invitations, user profiles, skills tracking, capacity fields, and team switching. `M`
4. [x] Playbooks System — SOP templates with versioning, checklists, validation types, evidence requirements, and association to work order types. `M`
5. [x] Inbox and Approvals Queue — InboxItem model with source types, urgency levels, QA validation states, and centralized inbox UI for reviewing pending items. `M`
6. [x] AI Infrastructure Foundation — AIAgent, AgentConfiguration, GlobalAISettings, and AgentActivityLog models for tracking agent operations and workspace-level AI settings. `M`
7. [x] Settings and Preferences — WorkspaceSettings, NotificationPreferences, TeamIntegrations, BillingInfo, and AuditLog models with comprehensive settings UI pages. `L`
8. [x] Today Dashboard — Today page showing daily overview with tasks due, approvals pending, recent activity, and quick actions for common workflows. `M`

## Core Operations (In Progress)
9. [~] Work Order Intake UI — Forms and interfaces for creating work orders manually with title, description, scope, success criteria, budget estimates, and team member assignment. Basic index/show pages exist, full CRUD in progress. `M`
10. [~] Task Management UI — Task creation within work orders, assignment to users, status tracking (To Do, In Progress, Blocked, Complete), and dependencies. Models complete, UI in progress. `M`
11. [~] Project Management UI — Project detail pages showing work orders, tasks, timeline, team members, and activity feed. Index page exists, detail views in progress. `M`
12. [ ] Deliverable Management UI — Deliverable entities tied to tasks/work orders, file upload system, version history, and draft/final status marking. `M`
13. [ ] Time Tracking Integration — Time entry UI for logging hours against tasks/work orders, timer functionality, time log history, and basic time reporting by user and project. `M`
14. [ ] Human Checkpoint Workflow — Draft > Review > Approve > Deliver state machine for work items, role-based transition permissions, and approval flow enforcement. `L`

## Communications and Context
15. [ ] Communications Log — Threaded conversation system tied to work items (projects, work orders, tasks), real-time message updates, @mentions, and context preservation. `L`
16. [ ] Document Management — Robust document storage with S3 integration, version control for deliverables, document preview, commenting, and folder organization. `M`

## Financial Tracking
17. [ ] Budget and Actuals — Budget estimation on work orders, actual cost tracking from time logs, budget vs actuals comparison views, margin calculations, and over-budget alerts. `M`
18. [ ] Change Order Flow — Change request creation from existing work orders, approval workflow for scope changes, automatic budget adjustment, and change history tracking. `M`

## Search, Notifications, and Reporting
19. [ ] Search and Filtering — Comprehensive search across work orders, tasks, deliverables, conversations with advanced filters by status, assignee, date, project, and saved presets. `M`
20. [ ] Notifications System — Real-time notifications for work item updates, mentions, approvals needed, with email digests, in-app alerts, and notification preferences. `M`
21. [ ] Reporting and Analytics — Dashboards showing team performance, project health, profitability by project/client, capacity utilization, and trend analysis. `L`

## AI Agent Platform
22. [ ] AI Agent Foundation and Tool Gateway — Agent abstraction layer, tool gateway for controlled agent actions, permission system, comprehensive audit logging, and orchestration engine. `XL`
23. [ ] Dispatcher Agent — AI agent that analyzes incoming requests, extracts structured information, suggests work order structure and task breakdown, and routes to appropriate assignees. `L`
24. [ ] PM Copilot Agent — AI agent that generates project plans from work orders, identifies milestones and dependencies, suggests resource allocation, and proposes timelines. `L`
25. [ ] Domain Skill Agents (Copy and Marketing) — Specialized agent for drafting marketing copy, social media content, blog posts, email templates, and ad copy following brand voice guidelines. `L`
26. [ ] Domain Skill Agents (Tech and Operations) — Agents for drafting technical documentation, operational procedures, troubleshooting guides, and infrastructure plans. `L`
27. [ ] Domain Skill Agents (Design and Analytics) — Agents for design direction suggestions, data pattern analysis, report generation, and project metrics insights. `L`
28. [ ] QA/Compliance Agent — Agent that validates deliverables against SOP checklists, checks for required evidence, flags missing requirements, and scores quality compliance. `M`
29. [ ] Finance Agent — Agent that drafts cost estimates, generates invoice line items from completed work, flags margin problems, and provides profitability analysis. `M`
30. [ ] Client Comms Agent — Agent that drafts professional client communications and status updates with human approval required before sending. `M`
31. [ ] Agent Workflow Orchestration — System for chaining agents together, passing context between agents, triggering agents on state changes, and monitoring multi-agent workflows. `L`

## Optional Modules
32. [ ] CRM Module — Client and contact management, company profiles, deal pipeline with stages, SLA definitions and tracking, and relationship history. `XL`
33. [ ] Finance Module — Detailed estimate builder, invoice generation and sending, payment tracking, expense management, and advanced profitability reports. `XL`
34. [ ] Helpdesk Module — Ticket management, customer portal, SLA compliance monitoring, ticket routing and escalation rules, and support-specific workflows. `XL`

> Notes
> - Items marked [x] are complete with models, migrations, controllers, and UI
> - Items marked [~] are in progress with partial implementation
> - Items marked [ ] are planned but not yet started
> - Foundation items (1-8) establish the data layer and basic UI structure
> - Core Operations (9-14) build the daily work management experience
> - AI Agent Platform (22-31) adds intelligent automation on top of the working core
> - Optional Modules (32-34) extend functionality based on customer needs
