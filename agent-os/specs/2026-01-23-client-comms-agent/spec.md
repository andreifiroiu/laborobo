# Specification: Client Comms Agent

## Goal
Build an AI agent that drafts professional client-facing communications (status updates, deliverable notifications, clarification requests, milestone announcements) with human approval required before delivery, enabling small service teams to maintain consistent client communication without manual composition overhead.

## User Stories
- As a project manager, I want an AI agent to draft client status updates so that I can maintain professional communication without spending time writing every message
- As a team lead, I want to review and approve AI-drafted communications before they are sent so that I maintain quality control over client interactions

## Specific Requirements

**ClientCommsAgent Class**
- Extend `BaseAgent` class following the same pattern as `PMCopilotAgent`
- Implement custom `instructions()` method with communication-focused system prompt
- Filter tools to only include communication-relevant tools via `tools()` method
- Add confidence determination methods for each communication type (status update, deliverable notification, clarification request, milestone announcement)
- Support multi-language drafting by accepting target language in context or inferring from Party preferences

**Communication Draft Creation**
- Create `Message` records with `author_type: AuthorType::AiAgent` for agent-drafted content
- Use appropriate `MessageType` enum value based on communication purpose (StatusUpdate, Message, etc.)
- Store draft in `CommunicationThread` linked to the relevant work item (Project or WorkOrder)
- Include metadata in message content or separate field tracking draft origin, context used, and confidence level
- Support all four communication types: status updates, deliverable notifications, request clarifications, milestone announcements

**Manual Trigger Mechanism**
- Add "Draft Client Update" action to Project and WorkOrder detail page controllers
- Create `ClientCommsController` with `draftUpdate` action accepting entity type and ID
- Route user to approval queue after draft is created (InboxItem links to Message)
- Allow user to specify communication type and optional notes for context when triggering manually

**Event-Driven Suggestions**
- Listen to model events: WorkOrder status transitions (to Review, Delivered states), Deliverable status changes, Project milestone completions
- Create Laravel event listeners that trigger agent draft suggestions for relevant status changes
- Store suggested drafts as pending InboxItems with `source_type` indicating event-driven origin
- Include event context in the draft (what changed, when, by whom)

**Scheduled Summary Runs**
- Create Laravel scheduled command for weekly project status summaries
- Query all active projects and generate summary drafts for each with recent activity
- Schedule via `app/Console/Kernel.php` or Laravel 11+ scheduler
- Allow team-level configuration for schedule frequency and active/inactive toggle

**InboxItem Approval Integration**
- Create `InboxItem` with `type: InboxItemType::AgentDraft` linking to the draft `Message` via `approvable` morph
- Include related work order/project/task context in InboxItem fields
- Set appropriate urgency based on communication type and context
- Support approve action (triggers delivery), reject action (marks draft as rejected with feedback), and edit action (allows modification before approval)

**Email Delivery via Laravel Notifications**
- Create `ClientCommunicationNotification` class implementing `via()`, `toMail()`, and `toDatabase()` methods
- Use `Notification::route('mail', $email)` for on-demand delivery to Party contacts who are not users
- Retrieve recipient email from Party `contact_email` or primary Contact `email` field
- Template email with work item context, communication content, and team branding

**Context Gathering for Drafts**
- Extend `ContextBuilder` or create `CommsContextBuilder` service to gather communication-specific context
- Include: work item details (title, description, status, progress), recent status transitions, attached deliverables and their states, Party contact info and preferences, conversation history from CommunicationThread, relevant Playbooks with `type: template` or tags matching communication type

**Multi-Language Support**
- Check Party or Contact model for language preference field (may need migration to add `preferred_language` column)
- Pass target language to agent in context; agent drafts in specified language
- Default to English if no preference is set

## Visual Design
No visual assets provided.

## Existing Code to Leverage

**BaseAgent and PMCopilotAgent Classes**
- Located at `app/Agents/BaseAgent.php` and `app/Agents/PMCopilotAgent.php`
- Provides provider configuration, system prompt loading, tool filtering, budget checking, and context management
- Follow same pattern for `ClientCommsAgent`: extend BaseAgent, override `instructions()` and `tools()`, add confidence methods

**CommunicationThread and Message Models**
- Located at `app/Models/CommunicationThread.php` and `app/Models/Message.php`
- Thread has `addMessage()` method accepting author, content, type, and author_type parameters
- Message supports `AuthorType::AiAgent` enum value for AI-drafted content
- Use `MessageType::StatusUpdate` or `MessageType::Message` for client communications

**InboxItem Model and Approval Workflow**
- Located at `app/Models/InboxItem.php`
- Supports `InboxItemType::AgentDraft` for agent-drafted content requiring approval
- Has `approvable` morph relationship to link to the draft Message
- Provides `markAsApproved()` and `markAsRejected()` methods for approval actions

**ContextBuilder Service**
- Located at `app/Services/ContextBuilder.php`
- Builds context from entity hierarchy (Task > WorkOrder > Project > Party > Team)
- Use `buildProjectContext()` and `buildClientContext()` methods as foundation
- Extend with communication-specific context (thread history, templates, contact preferences)

**Laravel Notification Pattern**
- Reference `app/Notifications/DeliverableStatusChangedNotification.php`
- Implements `via()` returning channels, `toArray()` for database storage
- Add `toMail()` method returning `MailMessage` for email delivery to clients

## Out of Scope
- Automated sending without human approval - all communications must be reviewed before delivery
- Client reply handling or two-way communication parsing - agent only drafts outbound messages
- SMS notification channel - only email and in-app communications supported
- Full client portal implementation - noted for future consideration only
- Real-time chat or instant messaging features
- Attachment handling for client communications (files, images)
- Communication analytics or tracking (open rates, click rates)
- Template editor UI for customizing communication templates
- Bulk communication sending to multiple parties at once
- Integration with external CRM or marketing platforms
