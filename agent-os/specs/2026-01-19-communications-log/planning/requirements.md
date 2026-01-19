# Spec Requirements: Communications Log

## Initial Description
Build a comprehensive communications log feature that enables team collaboration through contextual message threads attached to work items (Projects, Work Orders, and Tasks), with support for mentions, message types, file attachments, and reactions.

## Requirements Discussion

### First Round Questions

**Q1:** Should communication threads be available for Projects, Work Orders, or both? I'm assuming both based on typical project management patterns.
**Answer:** Yes, add communication threads for Tasks as well (in addition to Projects and Work Orders).

**Q2:** Where should the communications UI be located - in a Sheet panel (slide-out) on work item detail pages, a dedicated communications page, or both?
**Answer:** Both - enhance the Sheet panel pattern across all work items AND create a consolidated "all communications" view. Note: Check the code, there's already some work done for communications consolidation in the Inbox section.

**Q3:** What message types should be supported? I'm thinking: Note, Suggestion, Decision, Question (matching existing patterns).
**Answer:** Keep existing types (Note, Suggestion, Decision, Question) and add:
- Status Update
- Approval Request
- Message (generic)

**Q4:** Should @mentions support user autocomplete and notifications? I'm assuming yes for team collaboration.
**Answer:** Implement ALL of the following:
- Autocomplete for team members when typing "@"
- Notifications for mentioned users
- Visual highlighting of mentions
- Support work item mentions (like @WO-123)

**Q5:** Should messages update in real-time (WebSocket) or on manual refresh?
**Answer:** Build WebSocket foundation and defer real-time until Reverb is set up.

**Q6:** Should users be able to edit/delete their own messages? If so, should there be a time limit?
**Answer:** Allow edit and delete for 10 minute time window. Deletion will be soft-delete.

**Q7:** How should AI agent messages appear in the thread - as distinct from human messages, or integrated seamlessly?
**Answer:** Same thread, but visually signaled as "AI suggestions".

**Q8:** What should be explicitly OUT of scope for this feature?
**Answer:**
- IN SCOPE: File attachments, reactions/emoji
- OUT OF SCOPE: Nested replies, external integrations

### Existing Code to Reference

**Similar Features Identified:**

- **CommunicationThread Model**: Path: `app/Models/CommunicationThread.php`
  - Existing polymorphic relationship via `threadable` morph
  - Already supports Projects and WorkOrders
  - Has `addMessage()` helper method
  - Tracks `message_count` and `last_activity`

- **Message Model**: Path: `app/Models/Message.php`
  - Uses `AuthorType` enum (human, ai_agent)
  - Uses `MessageType` enum (note, suggestion, decision, question)
  - Links to `author_id` (User)

- **CommunicationController**: Path: `app/Http/Controllers/Work/CommunicationController.php`
  - Existing `show()` and `store()` endpoints
  - Currently supports `projects` and `work-orders` types
  - Returns JSON with thread and messages

- **MessageType Enum**: Path: `app/Enums/MessageType.php`
  - Existing types: Note, Suggestion, Decision, Question
  - Has `label()` and `color()` methods

- **AuthorType Enum**: Path: `app/Enums/AuthorType.php`
  - Values: Human, AiAgent
  - Used for distinguishing message authors

- **Inbox Components**: Path: `resources/js/components/inbox/`
  - `inbox-side-panel.tsx` - Sheet pattern for detail view
  - `inbox-tabs.tsx` - Tab navigation with counts
  - `inbox-list.tsx` - List view with selection
  - Has "Mentions" tab already implemented in UI

- **Work Order Detail Page**: Path: `resources/js/pages/work/work-orders/[id].tsx`
  - Lines 725-772: Existing Sheet-based communications panel
  - Shows message list with author, timestamp, content
  - Simple input for new messages

- **Project Detail Page**: Path: `resources/js/pages/work/projects/[id].tsx`
  - Lines 222-276: Similar Sheet-based communications panel
  - Same pattern as Work Order detail

- **Database Migrations**:
  - `2026_01_10_000006_create_communication_threads_table.php`
  - `2026_01_10_000007_create_messages_table.php`

### Follow-up Questions
No follow-up questions needed - user provided comprehensive answers.

## Visual Assets

### Files Provided:
No visual assets provided.

### Visual Insights:
N/A - No visual files found in the visuals folder.

## Requirements Summary

### Functional Requirements

**Work Item Coverage:**
- Communication threads for Projects (existing)
- Communication threads for Work Orders (existing)
- Communication threads for Tasks (NEW - requires adding Task support)

**Message Types (Extended):**
- Note (existing)
- Suggestion (existing)
- Decision (existing)
- Question (existing)
- Status Update (NEW)
- Approval Request (NEW)
- Message (NEW - generic type)

**@Mentions System:**
- Autocomplete dropdown when typing "@" character
- Support mentioning team members by name
- Support mentioning work items by ID (e.g., @WO-123, @T-456, @P-789)
- Visual highlighting of mentions in message content (styled differently)
- Create notifications for mentioned users
- Parse and store mentions for efficient querying

**File Attachments:**
- Allow attaching files to messages
- Reuse existing document/file upload patterns
- Support common file types (images, PDFs, documents)
- Display file previews/thumbnails in thread

**Reactions/Emoji:**
- Allow users to add emoji reactions to messages
- Show reaction counts grouped by emoji
- Allow toggling own reactions on/off

**Message Editing/Deletion:**
- Allow edit within 10-minute window from creation
- Allow delete within 10-minute window from creation
- Soft-delete for deleted messages (preserve data, hide from UI)
- Show "edited" indicator on modified messages

**AI Agent Messages:**
- Display in same thread as human messages
- Visual distinction (badge, icon, or styling) to indicate "AI suggestion"
- Use existing `AuthorType::AiAgent` enum value

**UI Locations:**

1. **Sheet Panel (per work item):**
   - Enhance existing pattern on Project detail page
   - Enhance existing pattern on Work Order detail page
   - Add new pattern to Task detail page
   - Show message list, input, message type selector
   - Support all new features (mentions, attachments, reactions)

2. **Consolidated "All Communications" View:**
   - Investigate existing Inbox "Mentions" tab for potential reuse
   - Create dedicated communications page or section
   - Filter by work item type (Project, Work Order, Task)
   - Filter by message type
   - Search across all communications
   - Show context (which work item each message belongs to)

**Real-Time Foundation:**
- Structure code to support WebSocket updates
- Use Laravel events for message creation/updates
- Defer actual WebSocket implementation until Reverb is configured
- Enable polling as interim solution if needed

### Reusability Opportunities

**Backend:**
- Extend existing `CommunicationThread` and `Message` models
- Extend existing `CommunicationController` to support Tasks
- Extend `MessageType` enum with new values
- Reuse `AuthorType` enum for AI distinction

**Frontend:**
- Reuse `Sheet` component pattern from existing implementations
- Reference `inbox-side-panel.tsx` for side panel patterns
- Reference `inbox-tabs.tsx` for tabbed navigation
- Extend existing message display components

**Patterns to Follow:**
- Polymorphic relationships (morphTo/morphMany) for threadable entities
- Inertia.js page props pattern for data loading
- React Hook Form for message input validation
- TanStack Query patterns for data fetching (if applicable)

### Scope Boundaries

**In Scope:**
- Communication threads for Projects, Work Orders, Tasks
- Extended message types (7 total)
- @mentions with autocomplete (users and work items)
- Notifications for mentioned users
- File attachments on messages
- Emoji reactions on messages
- Edit/delete with 10-minute window
- Soft-delete for message deletion
- AI agent message visual distinction
- Sheet panel enhancement on all work item detail pages
- Consolidated communications view
- WebSocket-ready architecture (events, structure)

**Out of Scope:**
- Nested replies / threaded conversations
- External integrations (Slack, email, etc.)
- Real-time WebSocket implementation (deferred until Reverb setup)
- Message search within individual threads (only global search)
- Read receipts / typing indicators
- Message pinning
- Scheduled messages

### Technical Considerations

**Database Changes Needed:**
- Add `Task` support to `CommunicationController::getModel()`
- Add `communicationThread` relationship to `Task` model
- Add new message types to `MessageType` enum
- Create `message_mentions` table for storing parsed mentions
- Create `message_attachments` table (or extend documents)
- Create `message_reactions` table
- Add `edited_at` and `deleted_at` columns to messages table

**API Endpoints to Add/Modify:**
- `GET /work/tasks/{id}/communications` - Get task thread
- `POST /work/tasks/{id}/communications` - Add message to task
- `PATCH /work/communications/messages/{id}` - Edit message
- `DELETE /work/communications/messages/{id}` - Soft-delete message
- `POST /work/communications/messages/{id}/reactions` - Add reaction
- `DELETE /work/communications/messages/{id}/reactions/{emoji}` - Remove reaction
- `GET /communications` - Consolidated view (all threads)
- `GET /api/mentions/search` - Autocomplete for mentions

**Frontend Components Needed:**
- Enhanced `CommunicationsPanel` component (reusable across all work items)
- `MentionInput` component with autocomplete
- `MessageItem` component with reactions, edit/delete actions
- `MessageAttachment` component for file display
- `ReactionPicker` component
- `CommunicationsPage` for consolidated view

**Events for WebSocket Readiness:**
- `MessageCreated` event
- `MessageUpdated` event
- `MessageDeleted` event
- `ReactionAdded` event
- `ReactionRemoved` event

**Existing Code Patterns to Follow:**
- Use Radix UI primitives for accessible components
- Use Tailwind CSS v4 for styling
- Use Zod for validation schemas
- Use Inertia.js for page navigation
- Use Laravel Pint for PHP code formatting
- Use existing test patterns with Pest
