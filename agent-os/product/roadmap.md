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

## Core Operations (Complete)
9. [x] Work Order Intake UI — Forms and interfaces for creating work orders manually with title, description, scope, success criteria, budget estimates, and team member assignment. Modal-based creation from work page, comprehensive detail page with inline editing, RACI management, and workflow transitions. `M`
10. [x] Task Management UI — Task creation within work orders, assignment to users, status tracking (To Do, In Progress, Blocked, Complete), and dependencies. Embedded task list in work order detail, full task detail page with timer, checklist, and workflow transitions. `M`
11. [x] Project Management UI — Project detail pages showing work orders, tasks, timeline, team members, and activity feed. Tree-based navigation on work page, comprehensive detail page with documents, communications, and team management. `M`
12. [x] Deliverable Management UI — Deliverable entities tied to tasks/work orders, file upload system, version history, and draft/final status marking. `M`
13. [x] Time Tracking Integration — Time entry UI for logging hours against tasks/work orders, timer functionality, time log history, and basic time reporting by user and project. `M`
14. [x] Human Checkpoint Workflow — Draft > Review > Approve > Deliver state machine for work items, role-based transition permissions, and approval flow enforcement. Core workflow engine, status enums, WorkflowTransitionService, RACI fields, ReviewerResolver, StatusTransition audit trail, frontend components, role-based permission validation (designated reviewer enforcement), inbox approval auto-transitions, and rejection feedback routing with notifications complete. `L`

## Communications and Context (Complete)
15. [x] Communications Log — Threaded conversation system tied to work items (projects, work orders, tasks), real-time message updates, @mentions, reactions, and context preservation. CommunicationThread, Message, MessageMention, MessageReaction, MessageAttachment models with full CRUD, polymorphic endpoints for projects/work-orders/tasks. `L`
16. [x] Document Management — Document model and FileUploadService implemented. File uploads on projects and deliverables working. Full document management UI with folder organization, document preview with annotations/comments, share links with password protection and expiration, access tracking, and team-wide Documents page. `M`

## Financial Tracking
17. [x] Budget and Actuals — Budget fields in GlobalAISettings, AgentBudgetService for cost tracking. Missing: Budget estimation UI on work orders, actual cost tracking from time logs, budget vs actuals views, margin calculations. `M`
18. [x] Change Order Flow — Change request creation from existing work orders, approval workflow for scope changes, automatic budget adjustment, and change history tracking. `M`

## Search, Notifications, and Reporting
19. [~] Search and Filtering — MentionSearchController for @mentions search implemented. Missing: Comprehensive search across work orders, tasks, deliverables, conversations; advanced filters; saved presets. `M`
20. [x] Notifications System — Notification classes (DeliverableStatusChanged, RejectionFeedback, Mention), NotificationPreference model, settings UI for notification configuration. Real-time notifications and preferences complete. `M`
21. [~] Reporting and Analytics — TimeReportsController with byUser, byProject, actualVsEstimated endpoints. Time reporting UI complete. Missing: Team performance dashboards, project health metrics, profitability analysis, capacity utilization. `L`

## AI Agent Platform
22. [x] AI Agent Foundation and Tool Gateway — Agent abstraction layer with AIAgent, AgentConfiguration, GlobalAISettings, AgentActivityLog, AgentMemory, AgentTemplate, AgentWorkflowState models. ToolGateway, ToolRegistry, AgentPermissionService, ContextBuilder services. Permission system and audit logging complete. `XL`
23. [ ] Dispatcher Agent — AI agent that analyzes incoming requests, extracts structured information, suggests work order structure and task breakdown, and routes to appropriate assignees. `L`
24. [ ] PM Copilot Agent — AI agent that generates project plans from work orders, identifies milestones and dependencies, suggests resource allocation, and proposes timelines. `L`
25. [ ] Domain Skill Agents (Copy and Marketing) — Specialized agent for drafting marketing copy, social media content, blog posts, email templates, and ad copy following brand voice guidelines. `L`
26. [ ] Domain Skill Agents (Tech and Operations) — Agents for drafting technical documentation, operational procedures, troubleshooting guides, and infrastructure plans. `L`
27. [ ] Domain Skill Agents (Design and Analytics) — Agents for design direction suggestions, data pattern analysis, report generation, and project metrics insights. `L`
28. [ ] QA/Compliance Agent — Agent that validates deliverables against SOP checklists, checks for required evidence, flags missing requirements, and scores quality compliance. `M`
29. [ ] Finance Agent — Agent that drafts cost estimates, generates invoice line items from completed work, flags margin problems, and provides profitability analysis. `M`
30. [ ] Client Comms Agent — Agent that drafts professional client communications and status updates with human approval required before sending. `M`
31. [~] Agent Workflow Orchestration — AgentWorkflowState model, AgentOrchestrator service, approve/reject workflow endpoints implemented. Missing: Full agent chaining, context passing between agents, state change triggers. `L`

## Optional Modules
32. [ ] CRM Module — Client and contact management, company profiles, deal pipeline with stages, SLA definitions and tracking, and relationship history. `XL`
33. [ ] Finance Module — Detailed estimate builder, invoice generation and sending, payment tracking, expense management, and advanced profitability reports. `XL`
34. [ ] Helpdesk Module — Ticket management, customer portal, SLA compliance monitoring, ticket routing and escalation rules, and support-specific workflows. `XL`
35. [ ] Public client portal — External-facing portal for clients to view project status, submit requests, approve deliverables, and communicate with the team. `L`

> Notes
> - Items marked [x] are complete with models, migrations, controllers, and UI
> - Items marked [~] are in progress with partial implementation
> - Items marked [ ] are planned but not yet started
> - Foundation items (1-8) establish the data layer and basic UI structure
> - Core Operations (9-14) build the daily work management experience
> - Communications and Context (15-16) enable team collaboration
> - AI Agent Platform (22-31) adds intelligent automation on top of the working core
> - Optional Modules (32-35) extend functionality based on customer needs
>
> ## Implementation Summary (Updated 2026-01-22)
> | Section | Complete | Partial | Planned | Total |
> |---------|----------|---------|---------|-------|
> | Foundation | 8 | 0 | 0 | 8 |
> | Core Operations | 6 | 0 | 0 | 6 |
> | Communications | 2 | 0 | 0 | 2 |
> | Financial Tracking | 0 | 1 | 1 | 2 |
> | Search/Notifications/Reporting | 1 | 2 | 0 | 3 |
> | AI Agent Platform | 1 | 1 | 8 | 10 |
> | Optional Modules | 0 | 0 | 4 | 4 |
> | **Total** | **18** | **4** | **13** | **35** |
