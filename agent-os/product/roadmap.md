# Product Roadmap
1. [ ] Core Work Graph Models — Create database schema and Eloquent models for Parties, Projects, Work Orders, Tasks, and Deliverables with proper relationships, validation rules, and basic CRUD operations. `M`

2. [ ] Work Order Intake UI — Build forms and interfaces for creating work orders manually, including fields for title, description, scope, success criteria, budget estimates, and assignment to team members. `M`

3. [ ] Task Management & Tracking — Implement task creation within work orders, task assignment to users, status tracking (To Do, In Progress, Blocked, Complete), and task dependencies with visual indicators. `M`

4. [ ] Deliverable Management System — Create deliverable entities tied to tasks/work orders, file upload and attachment system, version history tracking, and ability to mark deliverables as draft or final. `M`

5. [ ] Human Checkpoint Workflow — Implement Draft → Review → Approve → Deliver state machine for work items, role-based permissions for each transition, and approvals inbox showing items requiring review. `L`

6. [ ] Time Tracking Integration — Build time entry UI for logging hours against tasks/work orders, timer functionality for active work sessions, time log history, and basic reporting of time spent by user and project. `M`

7. [ ] SOP Templates System — Create database schema for SOP templates, template builder UI with checklists and evidence requirements, ability to attach templates to work order types, and template library management. `L`

8. [ ] Communications Log — Implement threaded conversation system tied to work items (projects, work orders, tasks), real-time message updates, @mentions and notifications, and conversation history with context preservation. `L`

9. [ ] Project Dashboard & Overview — Build project detail pages showing work orders, tasks, deliverables, timeline, budget vs actuals, team members assigned, and recent activity feed with filterable views. `M`

10. [ ] Budget & Financial Tracking — Add budget estimation to work orders, track actual costs from time logs, budget vs actuals comparison views, margin calculation and display, and alerts for work orders exceeding budget. `M`

11. [ ] Change Order Flow — Implement change request creation from existing work orders, approval workflow for scope changes, automatic budget adjustment after approval, and change order history tracking. `M`

12. [ ] AI Agent Foundation & Tool Gateway — Build agent abstraction layer, tool gateway architecture for controlled agent actions, agent permission system, audit logging for all agent operations, and basic agent orchestration engine. `XL`

13. [ ] Dispatcher Agent — Implement AI agent that analyzes incoming requests, extracts structured information (scope, deliverables, requirements), suggests work order structure and task breakdown, and routes to appropriate team members or agents. `L`

14. [ ] PM Copilot Agent — Create AI agent that generates project plans from work orders, identifies milestones and dependencies, suggests resource allocation, and proposes timelines based on team capacity and historical data. `L`

15. [ ] Domain Skill Agents (Copy & Marketing) — Build specialized agent for drafting marketing copy, social media content, blog posts, email templates, and ad copy, with ability to follow brand voice guidelines from SOPs. `L`

16. [ ] Domain Skill Agents (Tech & Operations) — Implement agents for drafting technical documentation, operational procedures, troubleshooting guides, infrastructure plans, and code review summaries. `L`

17. [ ] Domain Skill Agents (Design & Analytics) — Create agents for suggesting design direction and mockup concepts (text-based), analyzing data patterns, generating reports, and recommending insights from project metrics. `L`

18. [ ] QA/Compliance Agent — Build agent that validates deliverables against SOP checklists, checks for required evidence/artifacts, flags missing requirements before human review, and scores quality compliance. `M`

19. [ ] Finance Agent — Implement agent that drafts cost estimates for work orders, generates invoice line items from completed work, flags margin problems (actuals exceeding budget), and provides profitability analysis. `M`

20. [ ] Client Comms Agent — Create agent that drafts professional client-facing communications, status updates, and responses to inquiries, with human approval required before sending, maintaining consistent tone and completeness. `M`

21. [ ] Agent Workflow Orchestration — Build system for chaining multiple agents together, passing context and artifacts between agents, triggering agents based on work item state changes, and monitoring multi-agent workflow progress. `L`

22. [ ] User & Team Management — Implement user invitation and onboarding, role and permission management (Owner, Manager, Operator), team member profiles with skills and availability, and workspace settings configuration. `M`

23. [ ] Search & Filtering System — Build comprehensive search across work orders, tasks, deliverables, and conversations, advanced filtering by status, assignee, date range, project, tags, and saved filter presets. `M`

24. [ ] Notifications & Activity Feed — Create real-time notification system for work item updates, mentions, approvals needed, and agent completions, with email digests, in-app notifications, and notification preferences. `M`

25. [ ] Reporting & Analytics Dashboard — Build dashboards showing team performance metrics, project health indicators, agent contribution statistics, profitability by project/client, capacity utilization, and trend analysis. `L`

26. [ ] Document Management & Versioning — Implement robust document storage with S3 integration, version control for all deliverables, document preview and commenting, and folder organization within projects. `M`

27. [ ] CRM Module (Optional) — Create client and contact management, company profiles and relationship tracking, deal pipeline with stages, SLA definitions and tracking, and client communication history. `XL`

28. [ ] Finance Module (Optional) — Build detailed estimate builder with line items, invoice generation and sending, payment tracking and reconciliation, expense management, and advanced profitability reports with margin analysis. `XL`

29. [ ] Helpdesk Module (Optional) — Implement ticket management system, customer portal for ticket submission and tracking, SLA compliance monitoring and alerts, ticket routing and escalation rules, and support-specific workflows. `XL`

> Notes
> - Order items by technical dependencies and product architecture
> - Each item should represent an end-to-end (frontend + backend) functional and testable feature
> - Items 1-2 establish the technical foundation (React/Inertia, MySQL)
> - Items 3-13 build the core operations platform without AI
> - Items 14-23 add AI agent capabilities on top of the working operations core
> - Items 24-28 add essential collaboration and visibility features
> - Items 29-31 are optional modules that can be built based on customer demand
> - Redis and S3 are noted as future infrastructure additions but not prioritized in this roadmap
