# Task Breakdown: Client Comms Agent

## Overview
Total Tasks: 47 (across 8 task groups)

This feature implements an AI agent that drafts professional client-facing communications with human approval required before delivery. The agent supports status updates, deliverable notifications, clarification requests, and milestone announcements with multi-language support.

## Task List

### Database Layer

#### Task Group 1: Database Schema and Model Updates
**Dependencies:** None

- [x] 1.0 Complete database layer enhancements
  - [x] 1.1 Write 4-6 focused tests for Message draft functionality and Party preferences
    - Test Message model with draft status fields
    - Test Party preferred_language field
    - Test InboxItem with AgentDraft type linking to Message
    - Test draft metadata JSON storage and retrieval
  - [x] 1.2 Create migration to add draft fields to messages table
    - Add `draft_status` enum column (draft, approved, rejected, sent)
    - Add `draft_metadata` JSON column for context tracking (confidence, origin, communication_type)
    - Add `approved_at` timestamp column
    - Add `approved_by` foreign key to users table
    - Add `rejected_at` timestamp column
    - Add `rejection_reason` text column
    - Add index on `draft_status` for query performance
  - [x] 1.3 Create migration to add language preference to parties table
    - Add `preferred_language` string column with default 'en'
    - Add index on `preferred_language`
  - [x] 1.4 Create CommunicationType enum
    - Values: StatusUpdate, DeliverableNotification, ClarificationRequest, MilestoneAnnouncement
    - Add `label()` method returning human-readable labels
    - Add `description()` method for UI tooltips
  - [x] 1.5 Create DraftStatus enum
    - Values: Draft, Approved, Rejected, Sent
    - Add `label()` method
    - Add `isFinal()` method returning true for Approved, Rejected, Sent
  - [x] 1.6 Update Message model with draft-related fields and methods
    - Add cast for `draft_status` to DraftStatus enum
    - Add cast for `draft_metadata` to JSON/array
    - Add `isDraft()`, `isApproved()`, `isRejected()`, `isSent()` helper methods
    - Add `scopeDrafts()` query scope
    - Add `scopePendingApproval()` query scope
    - Add `markAsApproved(User $approver)` method
    - Add `markAsRejected(string $reason)` method
    - Add `markAsSent()` method
  - [x] 1.7 Update Party model with language preference
    - Add `preferred_language` to fillable array
    - Add `getPreferredLanguageAttribute()` accessor with 'en' default
  - [x] 1.8 Ensure database layer tests pass
    - Run ONLY the 4-6 tests written in 1.1
    - Verify migrations run successfully
    - Do NOT run the entire test suite at this stage

**Acceptance Criteria:**
- The 4-6 tests written in 1.1 pass
- Migrations run without errors
- Message model supports draft workflow
- Party model stores language preference
- CommunicationType and DraftStatus enums function correctly

---

### Agent Infrastructure

#### Task Group 2: ClientCommsAgent Class and Tools
**Dependencies:** Task Group 1

- [x] 2.0 Complete ClientCommsAgent infrastructure
  - [x] 2.1 Write 4-6 focused tests for ClientCommsAgent
    - Test agent instantiation and configuration
    - Test instructions() returns communication-focused system prompt
    - Test tools() filters to communication-relevant tools only
    - Test confidence determination methods for each communication type
  - [x] 2.2 Create ClientCommsAgent class extending BaseAgent
    - Location: `app/Agents/ClientCommsAgent.php`
    - Follow PMCopilotAgent pattern exactly
    - Inject required dependencies via constructor
  - [x] 2.3 Implement instructions() method with communication-focused system prompt
    - Define role as client communication specialist
    - Include guidelines for each communication type (status updates, deliverable notifications, clarification requests, milestone announcements)
    - Add multi-language drafting instructions
    - Include response format guidelines (structured JSON for drafts)
    - Add tone and professionalism guidelines
  - [x] 2.4 Implement tools() method filtering to communication-relevant tools
    - Include: work-order-info, project-info, get-playbooks, party-info
    - Add new tool names when created: draft-communication, get-thread-history
    - Filter from parent tools() using tool name whitelist
  - [x] 2.5 Implement confidence determination methods
    - `determineStatusUpdateConfidence(bool $hasRecentActivity, bool $hasStatusTransitions, float $contextCompleteness): AIConfidence`
    - `determineDeliverableNotificationConfidence(bool $hasDeliverableDetails, bool $hasAcceptanceCriteria): AIConfidence`
    - `determineClarificationConfidence(bool $hasUnclearItems, bool $hasQuestions): AIConfidence`
    - `determineMilestoneConfidence(bool $hasMilestoneData, bool $hasProgressMetrics): AIConfidence`
  - [x] 2.6 Add language handling methods
    - `getTargetLanguage(): string` - returns language from context or Party preference
    - `buildLanguageInstructions(string $language): string` - builds language-specific prompt section
  - [x] 2.7 Ensure agent infrastructure tests pass
    - Run ONLY the 4-6 tests written in 2.1
    - Verify agent can be instantiated
    - Do NOT run the entire test suite at this stage

**Acceptance Criteria:**
- The 4-6 tests written in 2.1 pass
- ClientCommsAgent extends BaseAgent correctly
- Agent has communication-focused instructions
- Tools are properly filtered
- Confidence methods return appropriate levels

---

### Context Building Services

#### Task Group 3: CommsContextBuilder Service
**Dependencies:** Task Groups 1, 2

- [x] 3.0 Complete context building service
  - [x] 3.1 Write 4-6 focused tests for CommsContextBuilder
    - Test work item context assembly
    - Test conversation history extraction
    - Test playbook/template lookup
    - Test Party contact info and preferences retrieval
  - [x] 3.2 Create CommsContextBuilder service class
    - Location: `app/Services/CommsContextBuilder.php`
    - Extend or compose with existing ContextBuilder
    - Accept Project or WorkOrder as primary entity
  - [x] 3.3 Implement work item context gathering
    - `buildWorkItemContext(Project|WorkOrder $entity): array`
    - Include: title, description, status, progress percentage
    - Include: recent status transitions with timestamps
    - Include: attached deliverables and their states
    - Include: key milestones and their completion status
  - [x] 3.4 Implement conversation history extraction
    - `buildThreadHistoryContext(CommunicationThread $thread, int $limit = 10): array`
    - Get recent messages from CommunicationThread
    - Include author info, timestamps, message types
    - Filter out internal-only messages if applicable
  - [x] 3.5 Implement playbook/template lookup
    - `findCommunicationTemplates(CommunicationType $type, array $tags = []): Collection`
    - Query Playbooks with `type: template` or communication-related tags
    - Match templates to communication type
    - Return ordered by relevance
  - [x] 3.6 Implement Party context gathering
    - `buildPartyContext(Party $party): array`
    - Include: contact name, email, preferred language
    - Include: relationship history (projects count, duration)
    - Include: communication preferences if stored
  - [x] 3.7 Create unified context assembly method
    - `buildFullContext(Project|WorkOrder $entity, CommunicationType $type): AgentContext`
    - Combine all context sources
    - Format for agent consumption
    - Include target language from Party preferences
  - [x] 3.8 Ensure context builder tests pass
    - Run ONLY the 4-6 tests written in 3.1
    - Verify context assembly works correctly
    - Do NOT run the entire test suite at this stage

**Acceptance Criteria:**
- The 4-6 tests written in 3.1 pass
- Service gathers comprehensive context
- Playbook templates are correctly matched
- Party preferences are included
- Context formats correctly for agent

---

### Communication Draft Service

#### Task Group 4: Draft Creation and Approval Flow
**Dependencies:** Task Groups 1, 2, 3

- [x] 4.0 Complete draft creation and approval service
  - [x] 4.1 Write 5-7 focused tests for draft creation and approval
    - Test draft creation with all communication types
    - Test InboxItem creation for draft approval
    - Test draft approval flow triggers delivery
    - Test draft rejection with feedback
    - Test draft editing before approval
  - [x] 4.2 Create ClientCommsDraftService class
    - Location: `app/Services/ClientCommsDraftService.php`
    - Inject ClientCommsAgent, CommsContextBuilder, and dependencies
  - [x] 4.3 Implement draft creation method
    - `createDraft(Project|WorkOrder $entity, CommunicationType $type, ?string $userNotes = null): Message`
    - Build context using CommsContextBuilder
    - Execute agent to generate draft content
    - Store as Message with `author_type: AuthorType::AiAgent`
    - Set `draft_status: DraftStatus::Draft`
    - Store metadata (communication_type, confidence, context_summary)
    - Link to CommunicationThread (create if needed)
    - Return created Message
  - [x] 4.4 Implement InboxItem creation for draft approval
    - `createApprovalItem(Message $draft, Project|WorkOrder $entity): InboxItem`
    - Set `type: InboxItemType::AgentDraft`
    - Link to draft Message via `approvable` morph
    - Set related work order/project context
    - Set urgency based on communication type
    - Set AI confidence from draft metadata
  - [x] 4.5 Implement draft approval method
    - `approveDraft(Message $draft, User $approver): void`
    - Validate draft is in Draft status
    - Call `markAsApproved($approver)` on Message
    - Mark InboxItem as approved
    - Dispatch ClientCommunicationDeliveryJob for email delivery
  - [x] 4.6 Implement draft rejection method
    - `rejectDraft(Message $draft, string $reason): void`
    - Call `markAsRejected($reason)` on Message
    - Mark InboxItem as rejected
    - Optionally allow re-drafting with feedback
  - [x] 4.7 Implement draft editing method
    - `updateDraft(Message $draft, string $newContent): Message`
    - Validate draft is still editable (Draft status)
    - Update content while preserving metadata
    - Set `edited_at` timestamp
    - Return updated Message
  - [x] 4.8 Ensure draft service tests pass
    - Run ONLY the 5-7 tests written in 4.1
    - Verify draft workflow functions correctly
    - Do NOT run the entire test suite at this stage

**Acceptance Criteria:**
- The 5-7 tests written in 4.1 pass
- Drafts are created with correct metadata
- InboxItems link properly to drafts
- Approval triggers delivery
- Rejection stores feedback
- Editing preserves draft integrity

---

### Trigger Mechanisms

#### Task Group 5: Manual, Event-Driven, and Scheduled Triggers
**Dependencies:** Task Groups 1-4

- [x] 5.0 Complete trigger mechanisms
  - [x] 5.1 Write 5-7 focused tests for trigger mechanisms
    - Test manual trigger via controller action
    - Test event listener for WorkOrder status change
    - Test event listener for Deliverable status change
    - Test scheduled command for weekly summaries
    - Test trigger creates draft and InboxItem
  - [x] 5.2 Create ClientCommsController with draftUpdate action
    - Location: `app/Http/Controllers/ClientCommsController.php`
    - `draftUpdate(Request $request)` action
    - Accept: entity_type (project|work_order), entity_id, communication_type, notes
    - Validate entity exists and user has access
    - Call ClientCommsDraftService::createDraft()
    - Create InboxItem for approval
    - Return redirect to inbox or draft preview
  - [x] 5.3 Add routes for manual trigger
    - POST `/client-communications/draft` - draftUpdate action
    - GET `/client-communications/preview/{message}` - preview draft (optional)
    - Add to appropriate route group with auth middleware
  - [x] 5.4 Create WorkOrderStatusChangedListener for event-driven drafts
    - Location: `app/Listeners/WorkOrderStatusChangedListener.php`
    - Listen to WorkOrder status transitions (to Review, Delivered states)
    - Check if auto-draft is enabled in team settings
    - Create draft suggestion with `source_type: event_driven`
    - Include event context in draft metadata
  - [x] 5.5 Create DeliverableStatusChangedListener for deliverable notifications
    - Location: `app/Listeners/DeliverableStatusChangedListener.php`
    - Listen to Deliverable status changes (Ready, Delivered)
    - Create draft for deliverable notification
    - Include deliverable details in context
  - [x] 5.6 Register event listeners in EventServiceProvider
    - Map WorkOrderStatusChanged event to listener
    - Map DeliverableStatusChanged event to listener (if not already dispatched)
  - [x] 5.7 Create GenerateWeeklySummariesCommand
    - Location: `app/Console/Commands/GenerateWeeklySummariesCommand.php`
    - Signature: `client-comms:weekly-summaries`
    - Query active projects with recent activity
    - Generate summary draft for each via ClientCommsDraftService
    - Use CommunicationType::StatusUpdate
    - Log generated drafts count
  - [x] 5.8 Schedule weekly summary command
    - Add to `routes/console.php` or `app/Console/Kernel.php`
    - Schedule weekly (e.g., Monday 9am)
    - Allow team-level configuration for frequency toggle
  - [x] 5.9 Ensure trigger mechanism tests pass
    - Run ONLY the 5-7 tests written in 5.1
    - Verify all trigger types work
    - Do NOT run the entire test suite at this stage

**Acceptance Criteria:**
- The 5-7 tests written in 5.1 pass
- Manual trigger creates drafts correctly
- Event listeners create suggestions on status changes
- Scheduled command generates weekly summaries
- All triggers create InboxItems for approval

---

### Email Delivery

#### Task Group 6: Notification Classes and Email Templates
**Dependencies:** Task Groups 1-5

- [x] 6.0 Complete email delivery system
  - [x] 6.1 Write 4-6 focused tests for notification delivery
    - Test ClientCommunicationNotification via() and toMail()
    - Test on-demand notification to Party email
    - Test email template renders correctly
    - Test notification queuing
  - [x] 6.2 Create ClientCommunicationNotification class
    - Location: `app/Notifications/ClientCommunicationNotification.php`
    - Implement `via()` returning ['mail', 'database']
    - Implement `toMail()` returning MailMessage with draft content
    - Implement `toArray()` for database storage
    - Accept Message draft and Party in constructor
    - Use Queueable trait for async delivery
  - [x] 6.3 Implement toMail() with proper formatting
    - Subject line based on communication type
    - Greeting with Party contact name
    - Main content from approved draft
    - Work item context (project/work order name)
    - Team branding (from settings or default)
    - Action button linking to relevant page (if applicable)
  - [x] 6.4 Add routeNotificationForMail to Party model
    - Return `contact_email` or primary Contact `email` field
    - Handle case where Party has no email (return null, skip mail channel)
  - [x] 6.5 Create Blade email template for client communications
    - Location: `resources/views/emails/client-communication.blade.php`
    - Professional, branded layout
    - Sections for: greeting, main content, context, footer
    - Responsive design for email clients
  - [x] 6.6 Create ClientCommunicationDeliveryJob
    - Location: `app/Jobs/ClientCommunicationDeliveryJob.php`
    - Accept Message and Party
    - Use on-demand notification: `Notification::route('mail', $email)->notify()`
    - Update Message status to Sent after successful delivery
    - Handle delivery failures gracefully
  - [x] 6.7 Ensure notification delivery tests pass
    - Run ONLY the 4-6 tests written in 6.1
    - Verify notifications send correctly
    - Do NOT run the entire test suite at this stage

**Acceptance Criteria:**
- The 4-6 tests written in 6.1 pass
- Notifications send via mail and database channels
- Email template renders professionally
- On-demand delivery works for Party recipients
- Message status updates after delivery

---

### Frontend Components

#### Task Group 7: UI Components for Draft Trigger and Preview
**Dependencies:** Task Groups 1-6

- [x] 7.0 Complete frontend UI components
  - [x] 7.1 Write 3-5 focused tests for UI components
    - Test DraftClientUpdateButton renders and triggers action
    - Test DraftPreviewModal displays draft content
    - Test CommunicationTypeSelector options and selection
  - [x] 7.2 Create DraftClientUpdateButton component
    - Location: `resources/js/components/client-comms/DraftClientUpdateButton.tsx`
    - Props: entityType, entityId, onDraftCreated
    - Renders button "Draft Client Update"
    - Opens modal/dropdown on click to select communication type
    - Submits to ClientCommsController::draftUpdate
    - Uses Inertia form submission or fetch API
  - [x] 7.3 Create CommunicationTypeSelector component
    - Location: `resources/js/components/client-comms/CommunicationTypeSelector.tsx`
    - Props: value, onChange, disabled
    - Radix UI Select with all CommunicationType options
    - Include descriptions/tooltips for each type
  - [x] 7.4 Create DraftPreviewModal component
    - Location: `resources/js/components/client-comms/DraftPreviewModal.tsx`
    - Props: draft (Message), isOpen, onClose, onApprove, onReject, onEdit
    - Display draft content with formatting
    - Show metadata: communication type, confidence, created at
    - Show recipient info (Party name, email)
    - Action buttons: Approve, Reject, Edit
    - Edit mode with textarea for content modification
  - [x] 7.5 Create LanguageSelector component (if needed)
    - Location: `resources/js/components/client-comms/LanguageSelector.tsx`
    - Props: value, onChange, availableLanguages
    - Radix UI Select for language override
    - Default to Party's preferred_language
  - [x] 7.6 Add DraftClientUpdateButton to Project and WorkOrder detail pages
    - Update Project detail page to include button in actions area
    - Update WorkOrder detail page to include button in actions area
    - Pass correct entityType and entityId props
  - [x] 7.7 Create AgentDraftInboxItem component for inbox display
    - Location: `resources/js/components/inbox/AgentDraftInboxItem.tsx`
    - Extend existing InboxItem display pattern
    - Show draft preview and metadata
    - Quick actions: View Full Draft, Approve, Reject
    - Link to DraftPreviewModal for full interaction
  - [x] 7.8 Update Inbox page to display AgentDraft type items
    - Add filter/tab for Agent Drafts
    - Use AgentDraftInboxItem component for rendering
    - Include approve/reject actions inline
  - [x] 7.9 Ensure UI component tests pass
    - Run ONLY the 3-5 tests written in 7.1
    - Verify components render and function correctly
    - Do NOT run the entire test suite at this stage

**Acceptance Criteria:**
- The 3-5 tests written in 7.1 pass
- Button appears on Project and WorkOrder pages
- Modal displays draft with actions
- Type selector shows all communication types
- Inbox displays AgentDraft items correctly

---

### Testing

#### Task Group 8: Test Review and Gap Analysis
**Dependencies:** Task Groups 1-7

- [x] 8.0 Review existing tests and fill critical gaps only
  - [x] 8.1 Review tests from Task Groups 1-7
    - Review the 4-6 tests written by database layer (Task 1.1) - 6 tests in `tests/Feature/Communications/MessageDraftTest.php`
    - Review the 4-6 tests written by agent infrastructure (Task 2.1) - 6 tests in `tests/Feature/Agents/ClientCommsAgentTest.php`
    - Review the 4-6 tests written by context builder (Task 3.1) - 6 tests in `tests/Feature/Services/CommsContextBuilderTest.php`
    - Review the 5-7 tests written by draft service (Task 4.1) - 7 tests in `tests/Feature/Services/ClientCommsDraftServiceTest.php`
    - Review the 5-7 tests written by triggers (Task 5.1) - 7 tests in `tests/Feature/ClientComms/ClientCommsTriggerTest.php`
    - Review the 4-6 tests written by notifications (Task 6.1) - 6 tests in `tests/Feature/ClientComms/ClientCommunicationNotificationTest.php`
    - Review the 3-5 tests written by UI components (Task 7.1) - 12 tests in `resources/js/components/client-comms/__tests__/client-comms-components.test.tsx`
    - Total existing tests: 50 tests (38 backend + 12 frontend)
  - [x] 8.2 Analyze test coverage gaps for THIS feature only
    - Identified critical user workflows that lack test coverage
    - Focused ONLY on gaps related to Client Comms Agent feature requirements
    - Did NOT assess entire application test coverage
    - Prioritized end-to-end workflows: draft creation -> approval -> delivery
  - [x] 8.3 Write up to 10 additional strategic tests maximum
    - End-to-end: User triggers draft -> appears in inbox -> approves -> email sent
    - Multi-language: Draft respects Party preferred language in metadata
    - Edge case: Draft approval succeeds gracefully when Party has no email
    - Event-driven: Work order status change includes event context in draft metadata
    - Cannot reject a draft that is already approved
    - Cannot approve a draft that is already rejected
    - Delivery job skips sending when message is not in approved status
    - Delivery job logs warning and skips when Party has no email
    - Total: 8 additional strategic tests in `tests/Feature/ClientComms/ClientCommsIntegrationTest.php`
  - [x] 8.4 Run feature-specific tests only
    - Ran ONLY tests related to Client Comms Agent feature
    - Total: 46 backend tests + 12 frontend tests = 58 tests
    - Did NOT run the entire application test suite
    - All critical workflows pass

**Acceptance Criteria:**
- All feature-specific tests pass (58 tests total: 46 backend + 12 frontend)
- Critical user workflows for Client Comms Agent are covered
- 8 additional tests added when filling gaps (within 10 maximum)
- Testing focused exclusively on this spec's feature requirements

---

## Execution Order

Recommended implementation sequence:

1. **Database Layer (Task Group 1)** - Foundation for all other work
2. **Agent Infrastructure (Task Group 2)** - ClientCommsAgent class and tools
3. **Context Building Services (Task Group 3)** - CommsContextBuilder for draft creation
4. **Draft Creation Service (Task Group 4)** - Core draft and approval workflow
5. **Trigger Mechanisms (Task Group 5)** - Manual, event-driven, and scheduled triggers
6. **Email Delivery (Task Group 6)** - Notification classes and delivery job
7. **Frontend Components (Task Group 7)** - UI for triggering and reviewing drafts
8. **Test Review (Task Group 8)** - Gap analysis and final integration tests

## Notes

- **Agent Pattern**: Follow the existing `PMCopilotAgent` pattern exactly for `ClientCommsAgent`
- **Human Approval Required**: All communications MUST go through InboxItem approval before delivery
- **Multi-Language**: Language preference comes from Party model, defaults to English
- **Event-Driven**: Status change listeners should check team settings before auto-creating drafts
- **Existing Infrastructure**: Leverage CommunicationThread/Message, InboxItem, and Laravel Notifications
- **No Visual Assets**: UI components should follow existing design patterns in the codebase
