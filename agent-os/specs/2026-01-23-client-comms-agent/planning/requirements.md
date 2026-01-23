# Spec Requirements: Client Comms Agent

## Initial Description

Build an AI agent that drafts professional client communications and status updates, with human approval required before sending. This agent will help small service teams maintain consistent, professional client communication without the overhead of manually composing every update.

## Requirements Discussion

### First Round Questions

**Q1:** For storing client communications, should we use the existing CommunicationThread/Message system, or do we need a separate entity for external client-facing messages?
**Answer:** Use internal comms (CommunicationThread/Message), BUT also link to Laravel's external notification system for delivery. Laravel notifications support multiple channels (mail, database, broadcast, custom) via the `via()` method.

**Q2:** What types of client communications should the agent draft? (e.g., status updates, deliverable notifications, request clarifications, milestone announcements)
**Answer:** Include ALL types: status updates, deliverable notifications, request clarifications, and milestone announcements.

**Q3:** How should the agent be triggered - manually (user requests a draft), event-driven (status changes trigger suggestions), or scheduled (weekly summary drafts)?
**Answer:** Implement FULL hybrid approach: manual trigger (button on project/work order pages), event-driven suggestions (status changes), and scheduled runs (weekly summaries).

**Q4:** Should drafted communications go through the existing InboxItem approval queue, or does this need a separate approval workflow?
**Answer:** Use existing InboxItem approval queue.

**Q5:** What delivery channels should be supported initially - in-app only, email integration, or client portal?
**Answer:** Build ALL: in-app communications (CommunicationThread), email integration (direct delivery to Party contacts via Laravel Mail notifications), and client portal consideration for future.

**Q6:** What context should the agent use when drafting communications? (work item details, party preferences, conversation history, SOPs/templates)
**Answer:** Include work item details, status transitions, deliverables, Party contact info, history of conversations on that thread, and SOP/templates related to work order or communication in general.

**Q7:** Is there anything that should explicitly be OUT of scope for this feature?
**Answer:** Exclude: automated sending without human approval, client reply handling / two-way communication parsing, and SMS channels. IN SCOPE: Multi-language support.

### Existing Code to Reference

**Similar Features Identified:**
- Feature: Laravel Notification System - Use `via()` method to specify channels (mail, database, broadcast, custom)
- Feature: On-demand notifications - Can send to non-user recipients via `Notification::route()`
- Feature: Database channel - Stores notification data as JSON in `notifications` table
- Feature: Custom channels - Can be created for specific delivery mechanisms

**Backend Models to Reference:**
- `CommunicationThread` and `Message` models for internal message storage
- `InboxItem` model for approval queue integration
- `Party` and `Contact` models for recipient information
- `AIAgent`, `AgentConfiguration`, `AgentActivityLog` models for agent infrastructure
- `Playbook` model for SOP/template integration

**Existing Agent Infrastructure:**
- Tool Gateway pattern for controlled AI agent operations
- AgentOrchestrator service for workflow management
- ContextBuilder service for gathering context

### Follow-up Questions

No follow-up questions needed - user provided comprehensive answers covering all requirements.

## Visual Assets

### Files Provided:
No visual assets provided.

### Visual Insights:
N/A

## Requirements Summary

### Functional Requirements

**Communication Drafting:**
- Draft status updates for work orders and projects
- Draft deliverable notifications when deliverables are ready for review or delivered
- Draft request clarifications when work items need client input
- Draft milestone announcements when significant progress is achieved
- Support multi-language communication drafting

**Trigger Mechanisms:**
- Manual trigger via button on project and work order detail pages
- Event-driven suggestions when status changes occur (e.g., work order moves to "Review" or "Delivered")
- Scheduled runs for periodic summaries (e.g., weekly project summaries)

**Context Gathering:**
- Pull work item details (title, description, scope, status, success criteria)
- Include status transitions and timeline
- Reference associated deliverables and their states
- Retrieve Party contact information and preferences
- Load conversation history from the relevant CommunicationThread
- Apply relevant SOPs/templates (communication templates, work order type templates)

**Approval Workflow:**
- All drafted communications route to InboxItem approval queue
- Drafts can be reviewed, edited, approved, or rejected
- Only approved communications proceed to delivery
- Rejection allows feedback and re-drafting

**Delivery Channels:**
- Store approved communications in CommunicationThread/Message system
- Send email notifications to Party contacts via Laravel Mail notifications
- Use Laravel's notification system for channel flexibility
- Support on-demand notification delivery to non-user recipients

### Reusability Opportunities

**Existing Infrastructure:**
- CommunicationThread/Message models for message storage
- InboxItem approval queue for human-in-the-loop workflow
- Laravel notification system for multi-channel delivery
- AIAgent and Tool Gateway for agent execution
- ContextBuilder service for gathering work item context
- Playbook system for communication templates

**Backend Patterns to Follow:**
- Existing agent patterns from Dispatcher Agent implementation
- Notification classes (DeliverableStatusChanged, RejectionFeedback, Mention)
- AgentActivityLog for audit trail

### Scope Boundaries

**In Scope:**
- Drafting all communication types (status updates, deliverable notifications, request clarifications, milestone announcements)
- Manual, event-driven, and scheduled triggers
- Human approval via InboxItem queue
- In-app storage (CommunicationThread/Message)
- Email delivery via Laravel Mail notifications
- Multi-language support
- Context gathering from work items, threads, contacts, and templates

**Out of Scope:**
- Automated sending without human approval (all messages require approval)
- Client reply handling / two-way communication parsing
- SMS delivery channel
- Full client portal implementation (noted for future consideration)

### Technical Considerations

**Integration Points:**
- CommunicationThread/Message polymorphic relationships (projects, work orders, tasks)
- Party/Contact models for recipient data
- InboxItem approval queue with source type for agent-drafted communications
- Laravel notification system with mail channel
- Playbook/templates for communication formatting
- AgentActivityLog for tracking all agent actions

**Architecture Patterns:**
- Follow Tool Gateway pattern for agent operations
- Use existing agent infrastructure (AIAgent, AgentConfiguration)
- Implement as domain skill agent following established patterns
- Use ContextBuilder service for consistent context gathering

**Event Triggers to Implement:**
- Work order status transitions (especially to Review, Approved, Delivered states)
- Deliverable status changes
- Project milestone completions
- Scheduled jobs for weekly summaries

**Multi-Language Considerations:**
- Agent should detect or be configured for target language
- Communication templates may need language variants
- Party/Contact may store preferred language
